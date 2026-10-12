<?php
declare(strict_types=1);
require_once __DIR__ . '/../database/connection.php';

function claimCookieOptions(int $expires): array
{
    return ['expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'];
}
function claimCookieName(): string{return in_array(getenv('APP_ENV'),['preview','testing'],true)?'invisix_preview_claim':'lobby_access_claim';}

function setClaimCookie(string $token): void
{
    setcookie(claimCookieName(), $token, claimCookieOptions(time() + 30 * 86400));
}

function clearClaimCookie(): void
{
    setcookie(claimCookieName(), '', claimCookieOptions(time() - 3600));
    unset($_COOKIE[claimCookieName()]);
}

function claimCookieToken(): string
{
    $token = $_COOKIE[claimCookieName()] ?? '';
    return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) ? $token : '';
}

function findClaimRequest(PDO $pdo, string $token): array|false
{
    $pdo = accountsDatabase($pdo);
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) return false;
    $statement = $pdo->prepare('SELECT * FROM access_requests WHERE claim_token_hash = ? LIMIT 1');
    $statement->execute([hash('sha256', $token)]);
    return $statement->fetch(PDO::FETCH_ASSOC);
}

function setupLog(PDO $pdo, string $type, int $requestId, ?int $accountId = null, ?int $staffId = null): void
{
    $pdo = accountsDatabase($pdo);
    $statement = $pdo->prepare('INSERT INTO account_logs (type, description, user_id, staff_user_id, additional_data) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$type, 'Fluxo de acesso: ' . $type, $accountId, $staffId, json_encode(['request_id' => $requestId])]);
}

function setupAccountExists(PDO $pdo, array $request): bool
{
    $pdo = accountsDatabase($pdo);
    $statement = $pdo->prepare('SELECT id FROM users WHERE r6_nickname = ? LIMIT 1');
    $statement->execute([$request['r6_nickname']]);
    return (bool) $statement->fetch();
}

function approveAccessRequest(PDO $pdo, int $requestId, int $staffId): void
{
    $pdo = accountsDatabase($pdo);
    beginWriteTransaction($pdo);
    try {
        validateDatabaseActor($pdo,$staffId,true);
        $statement = $pdo->prepare('SELECT * FROM access_requests WHERE id = ?');
        $statement->execute([$requestId]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request || $request['status'] !== 'pending' || setupAccountExists($pdo, $request)) {
            throw new RuntimeException('Esta solicitação não pode ser aprovada.');
        }
        $statement = $pdo->prepare("UPDATE access_requests SET status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'");
        $statement->execute([$staffId, $requestId]);
        setupLog($pdo, 'access_approved', $requestId, null, $staffId);
        $pdo->exec('COMMIT');
    } catch (Throwable $exception) {
        $pdo->exec('ROLLBACK');
        throw $exception;
    }
}

function completeClaimAccess(PDO $pdo, string $nickname, string $token, string $password): void
{
    $pdo = accountsDatabase($pdo);
    // Avoid bcrypt's silent truncation while keeping PASSWORD_DEFAULT/login compatible.
    if (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new RuntimeException('Use pelo menos 8 caracteres e no máximo 72 bytes na senha.');
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    beginWriteTransaction($pdo);
    try {
        $request = findClaimRequest($pdo, $token);
        if ($request) {
            $statement = $pdo->prepare('SELECT id FROM access_requests WHERE id = ? AND r6_nickname = ?');
            $statement->execute([$request['id'], trim($nickname)]);
            if (!$statement->fetchColumn()) $request = false;
        }
        if ($request && ($request['status'] !== 'approved' || $request['activated_at'] !== null || setupAccountExists($pdo, $request))) $request = false;
        if (!$request) throw new RuntimeException('Esta solicitação não está disponível para ativação.');
        $statement = $pdo->prepare("INSERT INTO users (name,r6_nickname,password_hash,role,status) VALUES (?, ?, ?, 'player','active')");
        $statement->execute([$request['name'], $request['r6_nickname'], $passwordHash]);
        $accountId = (int) $pdo->query('SELECT lastval()')->fetchColumn();
        $statement = $pdo->prepare('INSERT INTO players (user_id,r6_nickname,r6_tracker_url,r6_rank) VALUES (?, ?, ?, ?)');
        $statement->execute([$accountId, $request['r6_nickname'], $request['r6_tracker_url'], $request['r6_rank']]);
        $statement = $pdo->prepare('UPDATE access_requests SET activated_at = CURRENT_TIMESTAMP, claim_token_hash = NULL WHERE id = ?');
        $statement->execute([$request['id']]);
        setupLog($pdo, 'account_activated', (int) $request['id'], $accountId);
        $pdo->exec('COMMIT');
    } catch (Throwable $exception) {
        $pdo->exec('ROLLBACK');
        throw $exception;
    }
}
