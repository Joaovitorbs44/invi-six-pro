<?php
declare(strict_types=1);

function enforceRateLimit(string $scope, int $limit, int $window, bool $byIp = true): void
{
    $db = \App\Config\Database::connection();
    $key = hash('sha256', $scope . '|' . ($byIp ? ($_SERVER['REMOTE_ADDR'] ?? 'local') : 'account'));
    $now = time();
    $query = $db->prepare('INSERT INTO app_rate_limits(key_hash,window_end,attempts) VALUES(?,?,1)
        ON CONFLICT(key_hash) DO UPDATE SET
        window_end=CASE WHEN app_rate_limits.window_end<=? THEN excluded.window_end ELSE app_rate_limits.window_end END,
        attempts=CASE WHEN app_rate_limits.window_end<=? THEN 1 ELSE LEAST(app_rate_limits.attempts+1,?) END RETURNING attempts');
    $query->execute([$key, $now + $window, $now, $now, $limit + 1]);
    if ((int)$query->fetchColumn() <= $limit) return;
    http_response_code(429);
    header('Retry-After: ' . $window);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Muitas tentativas. Aguarde antes de tentar novamente.']);
    } else {
        echo 'Muitas tentativas. Aguarde antes de tentar novamente.';
    }
    exit;
}
