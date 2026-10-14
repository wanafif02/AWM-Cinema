<?php
declare(strict_types=1);
require __DIR__ . "/bootstrap.php";
require __DIR__ . "/seats.php";
require __DIR__ . "/endpoints.php";
$GLOBALS["requestId"] = bin2hex(random_bytes(8));
$started = microtime(true);
$db = null;
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");
header("X-Request-ID: " . $GLOBALS["requestId"]);
try {
    $config = config();
    $origin = $_SERVER["HTTP_ORIGIN"] ?? "";
    $self =
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
            ? "https"
            : "http") .
        "://" .
        ($_SERVER["HTTP_HOST"] ?? "localhost");
    if (
        $origin !== "" &&
        $origin !== $self &&
        !in_array($origin, $config["allowed_origins"], true)
    ) {
        abortApi(403, "Frontend origin not allowed");
    }
    if ($origin !== "") {
        header("Access-Control-Allow-Origin: " . $origin);
        header("Vary: Origin");
        header(
            "Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key",
        );
        header(
            "Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS",
        );
    }
    secret("jwt");
    rateLimit();
    $method = $_SERVER["REQUEST_METHOD"];
    $route = trim($_GET["route"] ?? "", "/");
    if ($method === "OPTIONS") {
        respond(200, ["message" => "Preflight accepted"]);
    } else {
        $data = body();
        $db = database();
        [$status, $result] = dispatch($db, $method, $route, $data);
        respond($status, $result);
    }
} catch (ApiError $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    respond($e->status, ["message" => $e->getMessage()]);
} catch (PDOException $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    $code = (string) $e->getCode();
    error_log(
        "Cinema DB error request=" . $GLOBALS["requestId"] . " code=" . $code,
    );
    respond(in_array($code, ["23000", "40001"], true) ? 409 : 503, [
        "message" =>
            $code === "23000"
                ? "Duplicate record or relationship constraint conflict"
                : ($code === "40001"
                    ? "Concurrent update conflict. Refresh and try again."
                    : "Database unavailable. Import database/awm_cinema.sql and check configuration."),
    ]);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log(
        "Cinema error request=" .
            $GLOBALS["requestId"] .
            " type=" .
            get_class($e),
    );
    respond(500, ["message" => "Internal error. Check Apache error log."]);
} finally {
    $log = json_encode([
        "time" => gmdate("c"),
        "request_id" => $GLOBALS["requestId"],
        "method" => $_SERVER["REQUEST_METHOD"],
        "route" => substr($_GET["route"] ?? "", 0, 100),
        "status" => http_response_code(),
        "duration_ms" => round((microtime(true) - $started) * 1000, 2),
    ]);
    @file_put_contents(
        dirname(__DIR__) . "/storage/requests.log",
        $log . PHP_EOL,
        FILE_APPEND | LOCK_EX,
    );
}
