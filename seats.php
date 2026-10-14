<?php

declare(strict_types=1);

// Physical hall seats, historical assignments and current screening reservations.
// event_seats(event_id,seat_id) is UNIQUE at database level.

function rowLabel(int $number): string
{
    $label = "";
    while ($number > 0) {
        $number--;
        $label = chr(65 + ($number % 26)) . $label;
        $number = intdiv($number, 26);
    }
    return $label;
}

function seatLabels(PDO $db, int $bookingId): array
{
    return array_column(
        query(
            $db,
            "SELECT s.label FROM booking_seats bs JOIN seats s ON s.id=bs.seat_id " .
                "WHERE bs.booking_id=? ORDER BY s.row_index,s.seat_number",
            [$bookingId],
        )->fetchAll(),
        "label",
    );
}

function hallSeats(PDO $db, int $venueId): array
{
    return query(
        $db,
        "SELECT * FROM seats WHERE venue_id=? ORDER BY row_index,seat_number",
        [$venueId],
    )->fetchAll();
}

function generateSeats(PDO $db, array $user, int $venueId): array
{
    $db->beginTransaction();
    $venue = query($db, "SELECT * FROM venues WHERE id=? FOR UPDATE", [
        $venueId,
    ])->fetch();
    if (!$venue || !$venue["active"]) {
        abortApi(404, "Active hall not found");
    }
    manage($user, $venue["brand"]);
    if (
        query($db, "SELECT id FROM seats WHERE venue_id=? LIMIT 1", [
            $venueId,
        ])->fetch()
    ) {
        abortApi(
            409,
            "This hall already has a layout. Edit seat availability instead.",
        );
    }
    for ($i = 0; $i < (int) $venue["capacity"]; $i++) {
        $row = intdiv($i, 10) + 1;
        $number = ($i % 10) + 1;
        query(
            $db,
            "INSERT INTO seats(venue_id,row_index,seat_number,label) VALUES(?,?,?,?)",
            [$venueId, $row, $number, rowLabel($row) . $number],
        );
    }
    $db->commit();
    return hallSeats($db, $venueId);
}

function screeningSeats(PDO $db, int $eventId): array
{
    $event = record($db, "events", $eventId);
    if (!$event["active"]) {
        abortApi(404, "Screening inactive");
    }
    $venue = record($db, "venues", (int) $event["venue_id"]);
    $seats = hallSeats($db, (int) $event["venue_id"]);
    if (!$seats) {
        abortApi(
            409,
            "Seat layout is not ready. Ask the organiser to generate the hall layout.",
        );
    }
    $sold = array_map(
        "intval",
        array_column(
            query($db, "SELECT seat_id FROM event_seats WHERE event_id=?", [
                $eventId,
            ])->fetchAll(),
            "seat_id",
        ),
    );
    $open = $event["starts_at"] > gmdate("Y-m-d H:i:s");
    foreach ($seats as $index => &$seat) {
        $seat["status"] = in_array((int) $seat["id"], $sold, true)
            ? "booked"
            : (!$open || !$seat["active"] || $index >= (int) $event["capacity"]
                ? "unavailable"
                : "available");
    }
    unset($seat);
    return ["event" => $event, "venue" => $venue, "seats" => $seats];
}

function seatDispatch(
    PDO $db,
    string $method,
    string $route,
    array $body,
): array {
    if (preg_match('#^events/([1-9]\d*)/seats$#', $route, $m)) {
        if ($method !== "GET") {
            abortApi(405, "Use GET for screening seats");
        }
        return [200, screeningSeats($db, (int) $m[1])];
    }
    $user = currentUser($db);
    if ($user["role"] === "customer") {
        abortApi(403, "Seat management requires organiser/staff or admin");
    }
    if ($route === "seats" && $method === "GET") {
        $venue = record(
            $db,
            "venues",
            intValue($_GET["venue_id"] ?? null, "venue_id"),
        );
        manage($user, $venue["brand"]);
        return [
            200,
            ["venue" => $venue, "items" => hallSeats($db, (int) $venue["id"])],
        ];
    }
    if ($route === "seats" && $method === "POST") {
        return [
            201,
            [
                "items" => generateSeats(
                    $db,
                    $user,
                    intValue($body["venue_id"] ?? null, "venue_id"),
                ),
            ],
        ];
    }
    if (
        preg_match('#^seats/([1-9]\d*)$#', $route, $m) &&
        in_array($method, ["PATCH", "PUT", "DELETE"], true)
    ) {
        $id = (int) $m[1];
        $active =
            $method === "DELETE"
                ? 0
                : intValue($body["active"] ?? null, "active", 0, 1);
        $initial = record($db, "seats", $id);
        $db->beginTransaction();
        // Shared hall lock serialises seat changes with new ticket bookings.
        $venue = query($db, "SELECT * FROM venues WHERE id=? FOR UPDATE", [
            $initial["venue_id"],
        ])->fetch();
        manage($user, $venue["brand"]);
        $seat = query($db, "SELECT * FROM seats WHERE id=? FOR UPDATE", [
            $id,
        ])->fetch();
        if (
            !$active &&
            query(
                $db,
                "SELECT es.seat_id FROM event_seats es JOIN events e ON e.id=es.event_id " .
                    "WHERE es.seat_id=? AND e.ends_at>UTC_TIMESTAMP() LIMIT 1",
                [$id],
            )->fetch()
        ) {
            abortApi(
                409,
                "This seat is reserved for an ongoing or future screening. Cancel that booking first.",
            );
        }
        query($db, "UPDATE seats SET active=? WHERE id=?", [$active, $id]);
        $db->commit();
        return [200, record($db, "seats", $id)];
    }
    abortApi(405, "Unsupported seat operation");
}

function createSeatBooking(PDO $db, array $body): array
{
    $user = currentUser($db);
    if ($user["role"] !== "customer") {
        abortApi(403, "Use a customer account to book tickets");
    }
    $eventId = intValue($body["event_id"] ?? null, "event_id");
    $initial = record($db, "events", $eventId);
    $db->beginTransaction();
    query($db, "SELECT id FROM venues WHERE id=? FOR UPDATE", [
        $initial["venue_id"],
    ])->fetch();
    $event = query($db, "SELECT * FROM events WHERE id=? FOR UPDATE", [
        $eventId,
    ])->fetch();
    if ((int) $event["venue_id"] !== (int) $initial["venue_id"]) {
        abortApi(409, "Screening changed. Refresh and try again.");
    }
    if (!$event["active"] || $event["starts_at"] <= gmdate("Y-m-d H:i:s")) {
        abortApi(409, "Screening is unavailable");
    }
    // Old confirmed bookings must be mapped before accepting new seat reservations.
    $unmapped = query(
        $db,
        'SELECT b.id FROM bookings b WHERE b.event_id=? AND b.status="confirmed" ' .
            "AND (SELECT COUNT(*) FROM booking_seats bs WHERE bs.booking_id=b.id)<>b.quantity LIMIT 1",
        [$eventId],
    )->fetch();
    if ($unmapped) {
        abortApi(
            409,
            "Run tools/setup_seats.php to assign seats to legacy bookings first",
        );
    }
    $layout = screeningSeats($db, $eventId);
    $available = array_values(
        array_filter($layout["seats"], fn($s) => $s["status"] === "available"),
    );
    if (array_key_exists("seat_ids", $body)) {
        if (
            !is_array($body["seat_ids"]) ||
            count($body["seat_ids"]) < 1 ||
            count($body["seat_ids"]) > 10
        ) {
            abortApi(422, "Choose 1–10 seats");
        }
        $ids = array_map(
            fn($id) => intValue($id, "seat_id"),
            $body["seat_ids"],
        );
        if (count(array_unique($ids)) !== count($ids)) {
            abortApi(422, "Duplicate seat selection");
        }
        $availableIds = array_map("intval", array_column($available, "id"));
        foreach ($ids as $id) {
            if (!in_array($id, $availableIds, true)) {
                abortApi(
                    409,
                    "A selected seat is no longer available. Refresh the seat map.",
                );
            }
        }
    } else {
        // Backwards compatibility for older quantity-based frontends/Postman requests.
        $quantity = intValue($body["quantity"] ?? null, "quantity", 1, 10);
        if (count($available) < $quantity) {
            abortApi(409, "Not enough tickets available");
        }
        $ids = array_map(
            "intval",
            array_column(array_slice($available, 0, $quantity), "id"),
        );
    }
    $quantity = count($ids);
    query(
        $db,
        "INSERT INTO bookings(user_id,event_id,quantity,total_cents,reference,status) VALUES(?,?,?,?,?,?)",
        [
            $user["id"],
            $eventId,
            $quantity,
            $quantity * (int) $event["price_cents"],
            bin2hex(random_bytes(16)),
            "confirmed",
        ],
    );
    $bookingId = (int) $db->lastInsertId();
    foreach ($ids as $id) {
        query(
            $db,
            "INSERT INTO booking_seats(booking_id,seat_id) VALUES(?,?)",
            [$bookingId, $id],
        );
        query(
            $db,
            "INSERT INTO event_seats(event_id,seat_id,booking_id) VALUES(?,?,?)",
            [$eventId, $id, $bookingId],
        );
    }
    $result = record($db, "bookings", $bookingId);
    $result["seat_labels"] = seatLabels($db, $bookingId);
    $db->commit();
    return [201, $result];
}

function availableSeatCount(PDO $db, array $event): int
{
    if (!$event["active"] || $event["starts_at"] <= gmdate("Y-m-d H:i:s")) {
        return 0;
    }
    return (int) query(
        $db,
        "SELECT COUNT(*) FROM seats s LEFT JOIN event_seats es ON es.event_id=? AND es.seat_id=s.id " .
            "WHERE s.venue_id=? AND s.active=1 AND ((s.row_index-1)*10+s.seat_number)<=? AND es.seat_id IS NULL",
        [$event["id"], $event["venue_id"], $event["capacity"]],
    )->fetchColumn();
}

// MAT FAIRUZ — staff hall activity API; admin also authorised.
function hallOperations(PDO $db): array
{
    $user = currentUser($db);
    if (!in_array($user["role"], ["admin", "staff", "organiser"], true)) {
        abortApi(403, "Staff or administrator required");
    }
    $where = $user["role"] === "admin" ? "" : " AND v.brand=?";
    $args = $user["role"] === "admin" ? [] : [$user["brand"]];
    $halls = query(
        $db,
        "SELECT v.* FROM venues v WHERE v.active=1" . $where . " ORDER BY v.id",
        $args,
    )->fetchAll();
    foreach ($halls as &$hall) {
        $hall["current"] =
            query(
                $db,
                "SELECT * FROM events WHERE venue_id=? AND active=1 AND starts_at<=UTC_TIMESTAMP() AND ends_at>UTC_TIMESTAMP() ORDER BY starts_at LIMIT 1",
                [$hall["id"]],
            )->fetch() ?:
            null;
        $hall["next"] =
            query(
                $db,
                "SELECT * FROM events WHERE venue_id=? AND active=1 AND starts_at>UTC_TIMESTAMP() ORDER BY starts_at LIMIT 1",
                [$hall["id"]],
            )->fetch() ?:
            null;
        $hall["status"] = $hall["current"] ? "ongoing" : "ready";
        $hall["active_seats"] = (int) query(
            $db,
            "SELECT COUNT(*) FROM seats WHERE venue_id=? AND active=1",
            [$hall["id"]],
        )->fetchColumn();
        $hall["blocked_seats"] = (int) query(
            $db,
            "SELECT COUNT(*) FROM seats WHERE venue_id=? AND active=0",
            [$hall["id"]],
        )->fetchColumn();
        $hall["tickets_sold"] = $hall["current"]
            ? (int) query(
                $db,
                "SELECT COALESCE(SUM(quantity),0) FROM bookings WHERE event_id=? AND status='confirmed'",
                [$hall["current"]["id"]],
            )->fetchColumn()
            : 0;
    }
    unset($hall);
    return ["items" => $halls, "server_time" => gmdate("c")];
}
