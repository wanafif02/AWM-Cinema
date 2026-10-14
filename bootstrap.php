<?php
declare(strict_types=1);
// API middleware and reusable helpers. No database logic in the frontend.
ini_set("display_errors", "0");
date_default_timezone_set("UTC");
class ApiError extends Exception
{
    public int $status;
    public function __construct(int $status, string $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}
function abortApi(int $status, string $message): void
{
    throw new ApiError($status, $message);
}
function respond(int $status, $data): void
{
    http_response_code($status);
    echo json_encode(
        [
            "success" => $status < 400,
            $status < 400 ? "data" : "error" => $data,
            "request_id" => $GLOBALS["requestId"],
        ],
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
    );
}
function config(): array
{
    $base = require dirname(__DIR__) . "/config.example.php";
    $local = dirname(__DIR__) . "/config.local.php";
    return is_file($local) ? array_replace($base, require $local) : $base;
}
function database(): PDO
{
    $c = config();
    $pdo = new PDO(
        "mysql:host={$c["db_host"]};port={$c["db_port"]};dbname={$c["db_name"]};charset=utf8mb4",
        $c["db_user"],
        $c["db_password"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
    $pdo->exec("SET time_zone='+00:00'");
    return $pdo;
}
function query(PDO $db, string $sql, array $args = []): PDOStatement
{
    $s = $db->prepare($sql);
    $s->execute($args);
    return $s;
}
function record(PDO $db, string $table, int $id): array
{
    $r = query($db, "SELECT * FROM `$table` WHERE id=?", [$id])->fetch();
    if (!$r) {
        abortApi(404, "Record not found");
    }
    return $r;
}
function cleanUser(array $u): array
{
    unset($u["password_hash"]);
    return $u;
}
function strValue($v, string $label, int $max = 200): string
{
    if (!is_string($v) || trim($v) === "" || strlen(trim($v)) > $max) {
        abortApi(422, "$label is required (max $max characters)");
    }
    return trim($v);
}
function intValue($v, string $label, int $min = 1, int $max = 1000000): int
{
    if (
        is_bool($v) ||
        !preg_match('/^\d+$/', (string) $v) ||
        strlen((string) $v) > 10
    ) {
        abortApi(422, "$label must be an integer");
    }
    $n = (int) $v;
    if ($n < $min || $n > $max) {
        abortApi(422, "$label must be between $min and $max");
    }
    return $n;
}
function emailValue($v): string
{
    $v = strtolower(strValue($v, "email", 254));
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
        abortApi(422, "Invalid email");
    }
    return $v;
}
function passwordValue($v): string
{
    $v = strValue($v, "password", 72);
    if (strlen($v) < 12) {
        abortApi(422, "Password must have 12–72 characters");
    }
    return $v;
}
function brandValue($v): string
{
    if (!in_array($v, ["AWM"], true)) {
        abortApi(422, "Invalid brand");
    }
    return $v;
}
function timeValue($v): string
{
    if (
        !is_string($v) ||
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:\d{2})$/',
            $v,
        )
    ) {
        abortApi(
            422,
            "Use ISO time with timezone, e.g. 2026-12-01T20:00:00+08:00",
        );
    }
    try {
        $d = new DateTimeImmutable($v);
        $errors = DateTimeImmutable::getLastErrors();
        if ($errors && ($errors["warning_count"] || $errors["error_count"])) {
            throw new Exception();
        }
        return $d->setTimezone(new DateTimeZone("UTC"))->format("Y-m-d H:i:s");
    } catch (Throwable $e) {
        abortApi(422, "Invalid date/time");
    }
}
function secret(string $name): string
{
    $dir = dirname(__DIR__) . "/storage";
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $path = $dir . "/" . $name . ".key";
    $f = fopen($path, "c+");
    if (!$f) {
        abortApi(503, "Storage folder must be writable");
    }
    flock($f, LOCK_EX);
    $key = trim(stream_get_contents($f));
    if ($key === "") {
        $key = bin2hex(random_bytes(32));
        rewind($f);
        fwrite($f, $key);
        fflush($f);
        @chmod($path, 0600);
    }
    flock($f, LOCK_UN);
    fclose($f);
    return $key;
}
function b64(string $s): string
{
    return rtrim(strtr(base64_encode($s), "+/", "-_"), "=");
}
function jwt(array $u): string
{
    $head = b64(json_encode(["alg" => "HS256", "typ" => "JWT"]));
    $payload = b64(
        json_encode([
            "sub" => (string) $u["id"],
            "iat" => time(),
            "exp" => time() + 3600,
            "iss" => "awm-cinema",
            "aud" => "awm-frontends",
        ]),
    );
    return $head .
        "." .
        $payload .
        "." .
        b64(hash_hmac("sha256", $head . "." . $payload, secret("jwt"), true));
}
function currentUser(PDO $db): array
{
    $auth = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
    if (!$auth && function_exists("getallheaders")) {
        $h = getallheaders();
        $auth = $h["Authorization"] ?? ($h["authorization"] ?? "");
    }
    if (
        !preg_match(
            '/^Bearer ([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)$/',
            $auth,
            $m,
        )
    ) {
        abortApi(401, "Login required");
    }
    [$head, $payload, $signature] = explode(".", $m[1]);
    if (
        !hash_equals(
            b64(
                hash_hmac(
                    "sha256",
                    $head . "." . $payload,
                    secret("jwt"),
                    true,
                ),
            ),
            $signature,
        )
    ) {
        abortApi(401, "Invalid token");
    }
    $decode = fn($v) => json_decode(
        base64_decode(strtr($v, "-_", "+/"), true) ?: "",
        true,
    );
    $h = $decode($head);
    $claims = $decode($payload);
    if (
        !is_array($h) ||
        ($h["alg"] ?? "") !== "HS256" ||
        ($h["typ"] ?? "") !== "JWT" ||
        !is_array($claims) ||
        ($claims["exp"] ?? 0) <= time() ||
        ($claims["iss"] ?? "") !== "awm-cinema" ||
        ($claims["aud"] ?? "") !== "awm-frontends"
    ) {
        abortApi(401, "Expired or invalid token");
    }
    $u = query($db, "SELECT * FROM users WHERE id=? AND active=1", [
        (int) ($claims["sub"] ?? 0),
    ])->fetch();
    if (!$u) {
        abortApi(401, "Account unavailable");
    }
    return $u;
}
function manage(array $u, string $brand): void
{
    if ($u["role"] === "admin") {
        return;
    }
    if (
        !in_array($u["role"], ["organiser", "staff"], true) ||
        $u["brand"] !== $brand
    ) {
        abortApi(403, "You cannot manage this cinema brand");
    }
}
function ownBooking(PDO $db, array $u, array $b): void
{
    if ($u["role"] === "customer") {
        if ((int) $b["user_id"] !== (int) $u["id"]) {
            abortApi(403, "This booking belongs to another customer");
        }
    } else {
        manage($u, record($db, "events", (int) $b["event_id"])["brand"]);
    }
}
function rateLimit(): void
{
    $path =
        dirname(__DIR__) .
        "/storage/rate-" .
        hash("sha256", $_SERVER["REMOTE_ADDR"] ?? "local") .
        ".json";
    $f = fopen($path, "c+");
    if (!$f) {
        abortApi(503, "Storage folder is not writable");
    }
    flock($f, LOCK_EX);
    $bucket = json_decode(stream_get_contents($f), true) ?: [
        "start" => time(),
        "count" => 0,
    ];
    if (time() - $bucket["start"] >= 60) {
        $bucket = ["start" => time(), "count" => 0];
    }
    $bucket["count"]++;
    rewind($f);
    ftruncate($f, 0);
    fwrite($f, json_encode($bucket));
    flock($f, LOCK_UN);
    fclose($f);
    if ($bucket["count"] > 120) {
        header("Retry-After: 60");
        abortApi(429, "Too many requests. Wait one minute.");
    }
}
function body(): array
{
    if ((int) ($_SERVER["CONTENT_LENGTH"] ?? 0) > 65536) {
        abortApi(413, "Request body too large");
    }
    $raw = file_get_contents("php://input", false, null, 0, 65537);
    if (strlen($raw) > 65536) {
        abortApi(413, "Request body too large");
    }
    if ($raw === "") {
        return [];
    }
    if (stripos($_SERVER["CONTENT_TYPE"] ?? "", "application/json") !== 0) {
        abortApi(415, "JSON content type required");
    }
    $object = json_decode($raw);
    if (json_last_error() !== JSON_ERROR_NONE) {
        abortApi(400, "Malformed JSON");
    }
    if (!is_object($object)) {
        abortApi(422, "JSON object required");
    }
    return (array) $object;
}
