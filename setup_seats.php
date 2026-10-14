<?php

declare(strict_types=1);

// Run once from CLI after backing up your database. Safe to run again.
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI only");
}
require dirname(__DIR__) . "/api/bootstrap.php";
require dirname(__DIR__) . "/api/seats.php";
$db = database();
try {
    // DDL auto-commits in MySQL. Existing tables/data are retained.
    $schema = file_get_contents(
        dirname(__DIR__) . "/database/seats_schema.sql",
    );
    foreach (explode(";", $schema) as $statement) {
        if (trim($statement) !== "") {
            $db->exec($statement);
        }
    }
    foreach (
        query($db, "SELECT * FROM venues WHERE active=1")->fetchAll()
        as $v
    ) {
        if (!hallSeats($db, (int) $v["id"])) {
            generateSeats($db, ["role" => "admin"], (int) $v["id"]);
        }
    }
    foreach (
        query(
            $db,
            "SELECT * FROM bookings WHERE status='confirmed' ORDER BY id",
        )->fetchAll()
        as $b
    ) {
        $db->beginTransaction();
        $e = query($db, "SELECT * FROM events WHERE id=? FOR UPDATE", [
            $b["event_id"],
        ])->fetch();
        $assigned = array_map(
            "intval",
            array_column(
                query(
                    $db,
                    "SELECT seat_id FROM booking_seats WHERE booking_id=?",
                    [$b["id"]],
                )->fetchAll(),
                "seat_id",
            ),
        );
        $reserved = array_map(
            "intval",
            array_column(
                query($db, "SELECT seat_id FROM event_seats WHERE event_id=?", [
                    $e["id"],
                ])->fetchAll(),
                "seat_id",
            ),
        );
        if (count($assigned) > (int) $b["quantity"]) {
            abortApi(409, "Existing seat count exceeds booking quantity");
        }
        foreach ($assigned as $id) {
            $existing = query(
                $db,
                "SELECT booking_id FROM event_seats WHERE event_id=? AND seat_id=?",
                [$e["id"], $id],
            )->fetch();
            if ($existing && (int) $existing["booking_id"] !== (int) $b["id"]) {
                abortApi(409, "Existing seat mapping conflict");
            }
            if (!$existing) {
                query(
                    $db,
                    "INSERT INTO event_seats(event_id,seat_id,booking_id) VALUES(?,?,?)",
                    [$e["id"], $id, $b["id"]],
                );
                $reserved[] = $id;
            }
        }
        foreach (hallSeats($db, (int) $e["venue_id"]) as $position => $seat) {
            if (count($assigned) >= (int) $b["quantity"]) {
                break;
            }
            $id = (int) $seat["id"];
            if (
                $position >= (int) $e["capacity"] ||
                !$seat["active"] ||
                in_array($id, $reserved, true)
            ) {
                continue;
            }
            query(
                $db,
                "INSERT INTO booking_seats(booking_id,seat_id) VALUES(?,?)",
                [$b["id"], $id],
            );
            query(
                $db,
                "INSERT INTO event_seats(event_id,seat_id,booking_id) VALUES(?,?,?)",
                [$e["id"], $id, $b["id"]],
            );
            $assigned[] = $id;
            $reserved[] = $id;
        }
        if (count($assigned) !== (int) $b["quantity"]) {
            abortApi(
                409,
                "Legacy booking exceeds available seats. No partial booking mapping committed.",
            );
        }
        $db->commit();
    }
    echo "Seat tables and default layouts ready. Legacy confirmed bookings assigned seats automatically.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Setup stopped: " . $error->getMessage() . "\n");
    exit(1);
}
