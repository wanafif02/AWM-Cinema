<?php
// CLI only. API key is never sent to the browser frontend.
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI only");
}
require dirname(__DIR__) . "/api/bootstrap.php";
echo "Administrator API key: " . secret("admin-api") . PHP_EOL;
