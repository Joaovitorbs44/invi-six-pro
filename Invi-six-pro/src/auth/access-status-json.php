<?php
declare(strict_types=1);
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../includes/account-setup.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); header('Allow: GET'); echo json_encode(['status' => 'unavailable']); exit;
}
require_once __DIR__.'/../includes/rate-limit.php';
enforceRateLimit('claim-status',120,60);
$request = findClaimRequest($pdo, claimCookieToken());
if (!$request) clearClaimCookie();
session_write_close();
echo json_encode(['status' => $request ? $request['status'] : 'unavailable']);
