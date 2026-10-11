<?php

declare(strict_types=1);

require_once __DIR__ . '/../components/head.php';

/*
|--------------------------------------------------------------------------
| Somente POST
|--------------------------------------------------------------------------
|
| Logout é uma ação que altera o estado da sessão.
| Por segurança, não permitimos logout através de GET.
|
*/

if (!isPost()) {

    http_response_code(405);

    header('Allow: POST');

    exit('Método não permitido.');
}

/*
|--------------------------------------------------------------------------
| Verificar autenticação
|--------------------------------------------------------------------------
*/

if (!isLoggedIn()) {

    redirect('/login');
}

/*
|--------------------------------------------------------------------------
| Verificar CSRF
|--------------------------------------------------------------------------
*/

verifyCsrf();

/*
|--------------------------------------------------------------------------
| Limpar dados da sessão
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| Remover cookie da sessão
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {

    $cookieParameters =
        session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' =>
                time() - 42000,

            'path' =>
                $cookieParameters['path'],

            'domain' =>
                $cookieParameters['domain'],

            'secure' =>
                $cookieParameters['secure'],

            'httponly' =>
                $cookieParameters['httponly'],

            'samesite' =>
                $cookieParameters['samesite']
                ?? 'Lax',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Destruir sessão
|--------------------------------------------------------------------------
*/

session_destroy();

/*
|--------------------------------------------------------------------------
| Iniciar nova sessão limpa
|--------------------------------------------------------------------------
|
| Precisamos de uma nova sessão porque queremos guardar o toast que será
| mostrado depois do redirecionamento para o login.
|
*/

session_start();

session_regenerate_id(true);

/*
|--------------------------------------------------------------------------
| Toast
|--------------------------------------------------------------------------
*/

flash(
    'success',
    'Você saiu da sua conta com sucesso.'
);

/*
|--------------------------------------------------------------------------
| Redirecionar
|--------------------------------------------------------------------------
*/

redirect('/login');