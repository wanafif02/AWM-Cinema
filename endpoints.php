<?php

declare(strict_types=1);

// ASSIGNMENT REQUIRED RESOURCE APIs:
// /users    : administrator user CRUD (JWT + API key for direct mutations)
// /events   : movie screening CRUD (public reads; admin/staff writes)
// /venues   : cinema hall CRUD (public reads; admin/staff writes)
// /bookings : customer booking CRUD/cancellation; staff ticket desk
// Additional resources: /seats, /events/{id}/seats, /operations/halls.
function dispatch(PDO $db, string $method, string $route, array $data): array
{
    if (str_starts_with($route, "admin/users")) {
        $admin = currentUser($db);
        if ($admin["role"] !== "admin") {
            abortApi(403, "Administrator required");
        }
        $_SERVER["HTTP_X_API_KEY"] = secret("admin-api");
        $route = substr($route, 6);
    }
    if (
        $route === "seats" ||
        str_starts_with($route, "seats/") ||
        preg_match('#^events/[1-9]\d*/seats$#', $route)
    ) {
        return seatDispatch($db, $method, $route, $data);
    }
    if ($route === "bookings" && $method === "POST") {
        return createSeatBooking($db, $data);
    }
    if ($route === "operations/halls" && $method === "GET") {
        return [200, hallOperations($db)];
    }
    if ($route === "health" && $method === "GET") {
        return [200, ["status" => "ok", "database" => "connected"]];
    }
    if ($route === "auth/register" && $method === "POST") {
        query(
            $db,
            "INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)",
            [
                strValue($data["name"] ?? null, "name"),
                emailValue($data["email"] ?? null),
                password_hash(
                    passwordValue($data["password"] ?? null),
                    PASSWORD_DEFAULT,
                ),
                "customer",
            ],
        );
        return [
            201,
            cleanUser(record($db, "users", (int) $db->lastInsertId())),
        ];
    }
    if ($route === "auth/login" && $method === "POST") {
        $u = query($db, "SELECT * FROM users WHERE email=? AND active=1", [
            emailValue($data["email"] ?? null),
        ])->fetch();
        $password = strValue($data["password"] ?? null, "password", 72);
        if (!$u || !password_verify($password, $u["password_hash"])) {
            abortApi(401, "Incorrect email or password");
        }
        return [
            200,
            [
                "access_token" => jwt($u),
                "expires_in" => 3600,
                "token_type" => "Bearer",
                "user" => cleanUser($u),
            ],
        ];
    }
    if ($route === "auth/me" && $method === "GET") {
        return [200, cleanUser(currentUser($db))];
    }
    if (
        !preg_match(
            '#^(users|events|venues|bookings)(?:/([1-9]\d*))?(?:/(qr))?$#',
            $route,
            $m,
        )
    ) {
        abortApi(404, "Endpoint not found");
    }
    $table = $m[1];
    $id = isset($m[2]) ? (int) $m[2] : null;
    $action = $m[3] ?? null;
    if (!in_array($method, ["GET", "POST", "PUT", "PATCH", "DELETE"], true)) {
        abortApi(405, "Method not allowed");
    }
    $u =
        $method === "GET" && in_array($table, ["events", "venues"], true)
            ? null
            : currentUser($db);
    // REQUIRED API: /users — administrator-only user resource.
    if ($table === "users") {
        if ($u["role"] !== "admin") {
            abortApi(403, "Administrator required");
        }
        if (
            $method !== "GET" &&
            !hash_equals(secret("admin-api"), $_SERVER["HTTP_X_API_KEY"] ?? "")
        ) {
            abortApi(403, "Administrator API key required");
        }
    }
    if ($action) {
        if ($table !== "bookings" || $method !== "GET") {
            abortApi(405, "Invalid QR operation");
        }
        $b = record($db, "bookings", $id);
        ownBooking($db, $u, $b);
        if ($b["status"] !== "confirmed") {
            abortApi(409, "Cancelled booking has no ticket");
        }
        if (!function_exists("curl_init")) {
            abortApi(503, "Enable PHP cURL in XAMPP php.ini");
        }
        $url =
            "https://quickchart.io/qr?" .
            http_build_query([
                "text" => "cinema-demo:" . $b["reference"],
                "size" => 250,
                "format" => "png",
            ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $image = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (
            $code !== 200 ||
            !is_string($image) ||
            substr($image, 0, 8) !== "\x89PNG\r\n\x1a\n" ||
            strlen($image) > 1000000
        ) {
            abortApi(
                502,
                "QR provider unavailable. Booking is saved; retry QR later.",
            );
        }
        return [
            200,
            [
                "provider" => "QuickChart",
                "reference" => $b["reference"],
                "image_data_url" =>
                    "data:image/png;base64," . base64_encode($image),
            ],
        ];
    }
    if ($method === "GET") {
        if (!$id) {
            return [200, listRecords($db, $table, $u)];
        }
        $row = record($db, $table, $id);
        if ($table === "bookings") {
            ownBooking($db, $u, $row);
            $row["seat_labels"] = seatLabels($db, (int) $row["id"]);
        } elseif (!$row["active"]) {
            abortApi(404, "Record inactive");
        }
        if ($table === "users") {
            $row = cleanUser($row);
        }
        if ($table === "events") {
            $row["available_tickets"] =
                (int) $row["capacity"] -
                (int) query(
                    $db,
                    "SELECT COALESCE(SUM(quantity),0) FROM bookings WHERE event_id=? AND status='confirmed'",
                    [$id],
                )->fetchColumn();
        }
        if ($table === "events") {
            $row["available_tickets"] = availableSeatCount($db, $row);
        }
        return [200, $row];
    }
    if ($method === "POST" && $id) {
        abortApi(405, "POST requires collection URL");
    }
    if (in_array($method, ["PUT", "PATCH", "DELETE"], true) && !$id) {
        abortApi(405, "Record ID required");
    }
    // Mutations lock the target row; hall changes and bookings share locks with screenings.
    $db->beginTransaction();
    $row = $id
        ? query($db, "SELECT * FROM `$table` WHERE id=? FOR UPDATE", [
            $id,
        ])->fetch()
        : null;
    if ($id && !$row) {
        abortApi(404, "Record not found");
    }
    // POST bookings is handled exclusively by createSeatBooking above.
    // REQUIRED API: /bookings — update/delete means cancellation.
    if ($table === "bookings") {
        ownBooking($db, $u, $row);
        if ($method !== "DELETE" && $data !== ["status" => "cancelled"]) {
            abortApi(422, "Booking update accepts only status=cancelled");
        }
        $event = query($db, "SELECT * FROM events WHERE id=? FOR UPDATE", [
            $row["event_id"],
        ])->fetch();
        if (
            $row["status"] !== "cancelled" &&
            $event["starts_at"] <= gmdate("Y-m-d H:i:s")
        ) {
            abortApi(409, "Cannot cancel after screening starts");
        }
        query($db, "UPDATE bookings SET status='cancelled' WHERE id=?", [$id]);
        query($db, "DELETE FROM event_seats WHERE booking_id=?", [$id]);
        $row = record($db, $table, $id);
        $row["seat_labels"] = seatLabels($db, $id);
        $db->commit();
        return [200, $row];
    }
    if ($row && isset($row["brand"]) && $table !== "users") {
        manage($u, $row["brand"]);
    }
    if ($row && $table !== "users" && !$row["active"]) {
        abortApi(409, "Inactive resource cannot be changed");
    }
    if ($method === "DELETE") {
        if ($table === "users" && (int) $u["id"] === $id) {
            abortApi(409, "Cannot deactivate yourself");
        }
        if (
            $table === "events" &&
            query(
                $db,
                "SELECT id FROM bookings WHERE event_id=? AND status='confirmed' LIMIT 1",
                [$id],
            )->fetch()
        ) {
            abortApi(409, "Cancel bookings before removing screening");
        }
        if (
            $table === "venues" &&
            query(
                $db,
                "SELECT id FROM events WHERE venue_id=? AND active=1 LIMIT 1",
                [$id],
            )->fetch()
        ) {
            abortApi(409, "Remove active screenings before removing hall");
        }
        query($db, "UPDATE `$table` SET active=0 WHERE id=?", [$id]);
        $db->commit();
        return [200, ["id" => $id, "active" => false]];
    }
    $merged = array_replace($row ?? [], $data);
    if ($table === "events" && $row) {
        foreach (["starts_at", "ends_at"] as $field) {
            if (!array_key_exists($field, $data)) {
                $merged[$field] = str_replace(" ", "T", $row[$field]) . "Z";
            }
        }
    }
    // REQUIRED API: /venues — cinema hall creation/update.
    if ($table === "venues") {
        $values = [
            "name" => strValue($merged["name"] ?? null, "name"),
            "brand" => brandValue($merged["brand"] ?? null),
            "location" => strValue($merged["location"] ?? null, "location"),
            "capacity" => intValue(
                $merged["capacity"] ?? null,
                "capacity",
                1,
                1000,
            ),
        ];
        manage($u, $values["brand"]);
        if (
            $row &&
            $values["capacity"] != (int) $row["capacity"] &&
            query($db, "SELECT id FROM seats WHERE venue_id=? LIMIT 1", [
                $id,
            ])->fetch()
        ) {
            abortApi(409, "Hall has a seat layout. Keep capacity unchanged.");
        }
        if ($row) {
            $screenings = query(
                $db,
                "SELECT id,capacity FROM events WHERE venue_id=? AND active=1 FOR UPDATE",
                [$id],
            )->fetchAll();
            foreach ($screenings as $e) {
                if ((int) $e["capacity"] > $values["capacity"]) {
                    abortApi(409, "Hall capacity below an existing screening");
                }
            }
            if (
                $values["brand"] !== $row["brand"] &&
                query($db, "SELECT id FROM events WHERE venue_id=? LIMIT 1", [
                    $id,
                ])->fetch()
            ) {
                abortApi(409, "Cannot change brand of a used hall");
            }
        }
        // REQUIRED API: /events — screening/schedule/inventory validation.
    } elseif ($table === "events") {
        $venueId = intValue($merged["venue_id"] ?? null, "venue_id");
        $v = query($db, "SELECT * FROM venues WHERE id=? FOR UPDATE", [
            $venueId,
        ])->fetch();
        if (!$v || !$v["active"]) {
            abortApi(422, "Active hall required");
        }
        $brand = brandValue($merged["brand"] ?? null);
        manage($u, $brand);
        if ($brand !== $v["brand"]) {
            abortApi(422, "Screening and hall brands must match");
        }
        $values = [
            "title" => strValue($merged["title"] ?? null, "title"),
            "genre" => strValue($merged["genre"] ?? null, "genre", 80),
            "duration_minutes" => intValue(
                $merged["duration_minutes"] ?? null,
                "duration_minutes",
                1,
                600,
            ),
            "brand" => $brand,
            "venue_id" => $venueId,
            "starts_at" => timeValue($merged["starts_at"] ?? null),
            "ends_at" => timeValue($merged["ends_at"] ?? null),
            "capacity" => intValue(
                $merged["capacity"] ?? null,
                "capacity",
                1,
                (int) $v["capacity"],
            ),
            "price_cents" => intValue(
                $merged["price_cents"] ?? null,
                "price_cents",
                0,
                100000,
            ),
        ];
        if (
            $values["starts_at"] <= gmdate("Y-m-d H:i:s") ||
            $values["ends_at"] <= $values["starts_at"]
        ) {
            abortApi(422, "Future start and later end required");
        }
        if (
            query(
                $db,
                "SELECT id FROM events WHERE venue_id=? AND id<>? AND active=1 AND starts_at<? AND ends_at>? LIMIT 1",
                [$venueId, $id ?? 0, $values["ends_at"], $values["starts_at"]],
            )->fetch()
        ) {
            abortApi(409, "This hall has an overlapping screening");
        }
        if ($row) {
            $sold = (int) query(
                $db,
                "SELECT COALESCE(SUM(quantity),0) FROM bookings WHERE event_id=? AND status='confirmed'",
                [$id],
            )->fetchColumn();
            if ($values["capacity"] < $sold) {
                abortApi(409, "Capacity below tickets sold");
            }
            foreach (
                hallSeats($db, (int) $row["venue_id"])
                as $position => $seat
            ) {
                if (
                    $position >= $values["capacity"] &&
                    query(
                        $db,
                        "SELECT seat_id FROM event_seats WHERE event_id=? AND seat_id=?",
                        [$id, $seat["id"]],
                    )->fetch()
                ) {
                    abortApi(409, "Capacity would exclude a reserved seat");
                }
            }
            if (
                query($db, "SELECT id FROM bookings WHERE event_id=? LIMIT 1", [
                    $id,
                ])->fetch()
            ) {
                foreach (
                    ["venue_id", "brand", "starts_at", "ends_at", "price_cents"]
                    as $field
                ) {
                    if ((string) $values[$field] !== (string) $row[$field]) {
                        abortApi(
                            409,
                            "Booked screening time/hall/price cannot change",
                        );
                    }
                }
            }
        }
    } else {
        $role = $merged["role"] ?? "customer";
        if (
            !in_array($role, ["admin", "organiser", "staff", "customer"], true)
        ) {
            abortApi(422, "Invalid role");
        }
        $brand = in_array($role, ["organiser", "staff"], true)
            ? brandValue($merged["brand"] ?? null)
            : null;
        if ($row && (int) $u["id"] === $id && $role !== "admin") {
            abortApi(409, "Cannot demote yourself");
        }
        $values = [
            "name" => strValue($merged["name"] ?? null, "name"),
            "email" => emailValue($merged["email"] ?? null),
            "role" => $role,
            "brand" => $brand,
        ];
        if ($method === "POST" || array_key_exists("password", $data)) {
            $values["password_hash"] = password_hash(
                passwordValue($data["password"] ?? null),
                PASSWORD_DEFAULT,
            );
        }
    }
    $fields = array_keys($values);
    if ($method === "POST") {
        query(
            $db,
            "INSERT INTO `$table` (`" .
                implode("`,`", $fields) .
                "`) VALUES(" .
                implode(",", array_fill(0, count($fields), "?")) .
                ")",
            array_values($values),
        );
        $id = (int) $db->lastInsertId();
    } else {
        query(
            $db,
            "UPDATE `$table` SET " .
                implode(",", array_map(fn($f) => "`$f`=?", $fields)) .
                " WHERE id=?",
            array_merge(array_values($values), [$id]),
        );
    }
    $result = record($db, $table, $id);
    if ($table === "users") {
        $result = cleanUser($result);
    }
    $db->commit();
    return [$method === "POST" ? 201 : 200, $result];
}
function listRecords(PDO $db, string $table, ?array $u): array
{
    $page = intValue($_GET["page"] ?? 1, "page", 1, 100000);
    $limit = intValue($_GET["limit"] ?? 12, "limit", 1, 100);
    $where = [];
    $args = [];
    if ($table !== "bookings") {
        $where[] = "r.active=1";
    }
    if (in_array($table, ["events", "venues"], true)) {
        if (!empty($_GET["brand"])) {
            $where[] = "r.brand=?";
            $args[] = brandValue($_GET["brand"]);
        }
        if (!empty($_GET["search"])) {
            $where[] = $table === "events" ? "r.title LIKE ?" : "r.name LIKE ?";
            $args[] = "%" . strValue($_GET["search"], "search") . "%";
        }
    }
    if ($table === "bookings") {
        if ($u["role"] === "customer") {
            $where[] = "r.user_id=?";
            $args[] = $u["id"];
        } elseif ($u["role"] !== "admin") {
            $where[] = "e.brand=?";
            $args[] = $u["brand"];
        }
        if (!empty($_GET["brand"])) {
            $where[] = "e.brand=?";
            $args[] = brandValue($_GET["brand"]);
        }
        if (!empty($_GET["status"])) {
            if (!in_array($_GET["status"], ["confirmed", "cancelled"], true)) {
                abortApi(422, "Invalid booking status");
            }
            $where[] = "r.status=?";
            $args[] = $_GET["status"];
        }
    }
    $sort = $_GET["sort"] ?? "id";
    $allow = $table === "events" ? ["id", "starts_at", "price_cents"] : ["id"];
    if (!in_array($sort, $allow, true)) {
        abortApi(422, "Invalid sort");
    }
    $order = $_GET["order"] ?? "asc";
    if (!in_array($order, ["asc", "desc"], true)) {
        abortApi(422, "Invalid order");
    }
    $join =
        $table === "bookings"
            ? " JOIN events e ON e.id=r.event_id JOIN venues v ON v.id=e.venue_id"
            : "";
    $filter = $where ? " WHERE " . implode(" AND ", $where) : "";
    $total = (int) query(
        $db,
        "SELECT COUNT(*) FROM `$table` r" . $join . $filter,
        $args,
    )->fetchColumn();
    $fields = "r.*";
    if ($table === "bookings") {
        $fields .= ",e.title,e.brand,e.starts_at,v.name AS venue_name";
    }
    if ($table === "events") {
        $fields .=
            ",r.capacity-(SELECT COALESCE(SUM(b.quantity),0) FROM bookings b WHERE b.event_id=r.id AND b.status='confirmed') AS available_tickets";
    }
    $rows = query(
        $db,
        "SELECT $fields FROM `$table` r" .
            $join .
            $filter .
            " ORDER BY r.`$sort` $order LIMIT $limit OFFSET " .
            ($page - 1) * $limit,
        $args,
    )->fetchAll();
    if ($table === "users") {
        $rows = array_map("cleanUser", $rows);
    }
    if ($table === "events") {
        foreach ($rows as &$item) {
            $item["available_tickets"] = availableSeatCount($db, $item);
        }
    }
    unset($item);
    if ($table === "bookings") {
        foreach ($rows as &$item) {
            $item["seat_labels"] = seatLabels($db, (int) $item["id"]);
        }
    }
    unset($item);
    return [
        "items" => $rows,
        "pagination" => ["page" => $page, "limit" => $limit, "total" => $total],
    ];
}
