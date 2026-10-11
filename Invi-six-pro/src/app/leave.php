<?php

declare(strict_types=1);

require_once __DIR__
    . '/../components/head.php';

requireAuth();

/*
|--------------------------------------------------------------------------
| Somente POST
|--------------------------------------------------------------------------
*/

if (!isPost()) {

    http_response_code(405);

    header('Allow: POST');

    exit(
        'Método não permitido.'
    );
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

verifyCsrf();

/*
|--------------------------------------------------------------------------
| Somente jogadores
|--------------------------------------------------------------------------
*/

if (
    userRole() === 'staff'
) {

    http_response_code(403);

    exit(
        'Acesso não autorizado.'
    );
}

/*
|--------------------------------------------------------------------------
| Buscar jogador
|--------------------------------------------------------------------------
*/

$statement =
    $usersPdo->prepare(
        '
        SELECT
            players.id,
            players.r6_nickname
        FROM players
        INNER JOIN users
            ON users.id = players.user_id
        WHERE players.user_id = :user_id
        LIMIT 1
        '
    );

$statement->execute([
    ':user_id' =>
        userId(),
]);

$player =
    $statement->fetch();

if (!$player) {

    flash(
        'error',
        'Perfil de jogador não encontrado.'
    );

    redirect(
        '/matchmaking'
    );
}

$playerId =
    (int) $player['id'];

/*
|--------------------------------------------------------------------------
| Cooldown
|--------------------------------------------------------------------------
*/

$cooldownKey =
    'matchmaking_action_'
    . $playerId;

$lastAction =
    (int) (
        $_SESSION[$cooldownKey]
        ?? 0
    );

$elapsed =
    time() - $lastAction;

if (
    $lastAction > 0 &&
    $elapsed < 5
) {

    $remaining =
        5 - $elapsed;

    flash(
        'warning',
        'Aguarde '
        . $remaining
        . ' segundo'
        . (
            $remaining === 1
                ? ''
                : 's'
        )
        . ' antes de alterar sua posição na fila.'
    );

    redirect(
        '/matchmaking'
    );
}

/*
|--------------------------------------------------------------------------
| Remover da fila
|--------------------------------------------------------------------------
*/

try {
    beginWriteTransaction($pdo);

    $statement =
        $pdo->prepare(
            '
            DELETE FROM matchmaking_queue
            WHERE player_id = :player_id
            '
        );

    $statement->execute([
        ':player_id' =>
            $playerId,
    ]);
    $pdo->exec('COMMIT');

    /*
    |--------------------------------------------------------------------------
    | Não estava na fila
    |--------------------------------------------------------------------------
    */

    if (
        $statement->rowCount() === 0
    ) {

        flash(
            'warning',
            'Você não está na fila.'
        );

        redirect(
            '/matchmaking'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar cooldown
    |--------------------------------------------------------------------------
    */

    $_SESSION[$cooldownKey] =
        time();

    flash(
        'success',
        'Você saiu da fila.'
    );

} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    flash(
        'error',
        'Não foi possível sair da fila.'
    );
}

/*
|--------------------------------------------------------------------------
| Retornar
|--------------------------------------------------------------------------
*/

redirect(
    '/matchmaking'
);
