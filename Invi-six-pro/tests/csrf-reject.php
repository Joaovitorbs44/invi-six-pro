<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/csrf.php';
$_SESSION = ['csrf_token' => str_repeat('a',64)];
$_POST = ['_token' => str_repeat('b',64)];
ob_start();
register_shutdown_function(static function (): void {
    ob_end_clean();
    if (http_response_code() !== 419) {
        fwrite(STDERR,"Invalid CSRF accepted.\n");
        exit(1);
    }
    echo "Invalid CSRF rejected with HTTP 419.\n";
});
verifyCsrf();
