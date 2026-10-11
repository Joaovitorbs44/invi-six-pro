<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
\App\Config\Env::load(dirname(__DIR__));
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('UTC');
if (getenv('VERCEL') === '1') $_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/includes/security.php';
applicationSecurityHeaders();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store');
set_exception_handler(static function (Throwable $error): void {
    error_log('Application request failed: ' . get_class($error));
    http_response_code(503);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Serviço temporariamente indisponível.']);
    } else echo 'Serviço temporariamente indisponível. Tente novamente mais tarde.';
});
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || preg_match('~[\\\\\x00-\x20%]|//|(?:^|/)\.~', $path)) {
    http_response_code(404);
    exit('Página não encontrada.');
}
$path = $path === '/' ? '/' : rtrim($path, '/');
foreach ($_POST as $value) {
    if (!is_string($value)) {
        http_response_code(400);
        exit('Requisição inválida.');
    }
}
if (str_starts_with($path, '/assets/')) {
    $root = realpath(dirname(__DIR__) . '/public/assets');
    $file = realpath(dirname(__DIR__) . '/public' . $path);
    $types = ['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','woff'=>'font/woff','woff2'=>'font/woff2'];
    $extension = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : '';
    if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file) || !isset($types[$extension])) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }
    if (PHP_SAPI === 'cli-server' && getenv('VERCEL') !== '1') return false;
    header('Content-Type: ' . $types[$extension]);
    readfile($file);
    exit;
}
$routes = [
    '/' => 'pages/home.php', '/login' => 'auth/login.php', '/register' => 'auth/register.php',
    '/logout' => 'auth/logout.php', '/solicitacao' => 'auth/access-status.php',
    '/solicitacao/status' => 'auth/access-status-json.php', '/primeiro-acesso/senha' => 'auth/first-access-password.php',
    '/ranking' => 'app/ranking.php', '/perfil' => 'app/profile.php', '/partidas' => 'app/history.php',
    '/matchmaking' => 'app/matchmaking.php', '/matchmaking/join' => 'app/join.php', '/matchmaking/leave' => 'app/leave.php',
    '/match/action' => 'app/match-action.php', '/match/snapshot' => 'app/match-snapshot.php',
    '/support' => 'app/support.php', '/support/action' => 'app/support-action.php', '/support/snapshot' => 'app/support-snapshot.php',
    '/punishments' => 'app/punishments.php', '/admin' => 'admin/index.php', '/admin/support' => 'admin/support.php',
    '/admin/punishments' => 'admin/punishments.php', '/admin/seasons' => 'admin/seasons.php', '/admin/matches' => 'admin/matches.php',
    '/admin/access/approve' => 'admin/access/approve.php', '/admin/access/reject' => 'admin/access/reject.php',
    '/admin/access/delete' => 'admin/access/delete.php', '/admin/user/delete' => 'admin/user/delete.php',
    '/admin/user/password/reset' => 'admin/user/password/reset.php',
];
if (preg_match('~^/(?:partida|match)/([1-9][0-9]*)$~', $path, $match)) {
    $_GET['match_id'] = $match[1];
    $routes[$path] = 'app/match.php';
}
if (!isset($routes[$path])) {
    http_response_code(404);
    exit('Página não encontrada.');
}
require __DIR__ . '/' . $routes[$path];
