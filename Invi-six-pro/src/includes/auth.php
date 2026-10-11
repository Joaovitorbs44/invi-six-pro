<?php

declare(strict_types=1);
require_once __DIR__ . '/../database/connection.php';

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    $hasSession = isset(
        $_SESSION['user_id'],
        $_SESSION['user_role']
    );
    if (!$hasSession) return false;
    $now=time();
    if ($now-(int)($_SESSION['last_activity']??$now)>1800 || $now-(int)($_SESSION['authenticated_at']??$now)>43200) {
        unset($_SESSION['user_id'],$_SESSION['user_role'],$_SESSION['authenticated_at'],$_SESSION['last_activity']);
        return false;
    }
    $_SESSION['last_activity']=$now;
    global $pdo;
    $accounts = $pdo instanceof PDO ? accountsDatabase($pdo) : null;
    if ($accounts instanceof PDO) {
        $statement = $accounts->prepare('SELECT role, status, session_version FROM users WHERE id = ?');
        $statement->execute([(int) $_SESSION['user_id']]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['status'] !== 'active' || (int)$user['session_version'] !== (int)($_SESSION['session_version'] ?? -1)) {
            unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['user_r6_nickname']);
            return false;
        }
        $_SESSION['user_role'] = $user['role'];
    }
    return true;
}

/*
|--------------------------------------------------------------------------
| Usuário atual
|--------------------------------------------------------------------------
*/

function userId(): ?int
{
    if (!isLoggedIn()) {
        return null;
    }

    return (int) $_SESSION['user_id'];
}

/*
|--------------------------------------------------------------------------
| Role atual
|--------------------------------------------------------------------------
*/

function userRole(): ?string
{
    if (!isLoggedIn()) {
        return null;
    }

    return (string) $_SESSION['user_role'];
}

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

function isAdmin(): bool
{
    return isLoggedIn()
        && userRole() === 'admin';
}

/*
|--------------------------------------------------------------------------
| Staff
|--------------------------------------------------------------------------
|
| Admin também possui todas as permissões de Staff.
|
*/

function isStaff(): bool
{
    return isLoggedIn()
        && in_array(
            userRole(),
            [
                'staff',
                'admin',
            ],
            true
        );
}

/*
|--------------------------------------------------------------------------
| Exigir login
|--------------------------------------------------------------------------
*/

function requireAuth(): void
{
    if (isLoggedIn()) {
        return;
    }

    flash(
        'error',
        'Você precisa entrar para acessar esta página.'
    );
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'code'=>'session_expired','message'=>'Sua sessão expirou. Entre novamente.']);
        exit;
    }
    $destination = safeLoginDestination($_SERVER['REQUEST_URI'] ?? null);
    if ($destination) $_SESSION['login_destination'] = $destination;
    redirect('/login');
}

/*
|--------------------------------------------------------------------------
| Exigir Staff
|--------------------------------------------------------------------------
*/

function requireStaff(): void
{
    requireAuth();

    if (!isStaff()) {

        http_response_code(403);

        exit(
            'Você não possui permissão para acessar esta página.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Exigir Admin
|--------------------------------------------------------------------------
*/

function requireAdmin(): void
{
    requireAuth();

    if (!isAdmin()) {

        http_response_code(403);

        exit(
            'Você não possui permissão para acessar esta página.'
        );
    }
}
