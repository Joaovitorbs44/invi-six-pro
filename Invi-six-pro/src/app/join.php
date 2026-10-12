<?php

declare(strict_types=1);

require_once __DIR__
    . '/../components/head.php';

requireAuth();
require_once __DIR__ . '/../includes/matchmaking.php';
require_once __DIR__ . '/../includes/matches.php';

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

$statement = $usersPdo->prepare(
    '
    SELECT
        players.id,
        players.r6_nickname,
        users.status
    FROM players
    INNER JOIN users
        ON users.id = players.user_id
    WHERE players.user_id = :user_id
    LIMIT 1
    '
);

$statement->execute([
    ':user_id' => userId(),
]);

$player =
    $statement->fetch();

// Admin accounts created by the CLI do not have a player profile yet.
// Create it only on the first explicit queue entry, using the selected rank.
if (!$player && isAdmin()) {
    $rank = (string) ($_POST['r6_rank'] ?? '');
    if (!in_array($rank, matchmakingRanks(), true)) {
        flash('warning', 'Selecione seu rank atual antes de entrar na fila.');
        redirect('/matchmaking');
    }

    try {
        $createPlayer = $usersPdo->prepare("INSERT INTO players (user_id, r6_nickname, r6_rank)
            SELECT id, r6_nickname, :rank FROM users
            WHERE id = :user AND role = 'admin' AND status = 'active'
            ON CONFLICT(user_id) DO NOTHING");
        $createPlayer->execute([':rank' => $rank, ':user' => userId()]);
        $statement->execute([':user_id' => userId()]);
        $player = $statement->fetch();
    } catch (Throwable $exception) {
        flash('error', 'Não foi possível preparar seu perfil de jogador. Tente novamente.');
        redirect('/matchmaking');
    }
}

if (!$player) {

    flash(
        'error',
        'Perfil de jogador não encontrado.'
    );

    redirect(
        '/matchmaking'
    );
}

/*
|--------------------------------------------------------------------------
| Conta ativa
|--------------------------------------------------------------------------
*/

if (
    $player['status'] !== 'active'
) {

    flash(
        'error',
        'Sua conta não está ativa.'
    );

    redirect(
        '/matchmaking'
    );
}

$playerId =
    (int) $player['id'];

$activeMatch = $pdo->prepare("SELECT 1 FROM match_players mp
    INNER JOIN matches m ON m.id = mp.match_id
    WHERE mp.player_id = :player AND m.status NOT IN ('finished', 'cancelled') LIMIT 1");
$activeMatch->execute([':player' => $playerId]);
if ($activeMatch->fetchColumn()) {
    flash('warning', 'Você já está em uma partida. Aguarde a conclusão antes de entrar na fila.');
    redirect('/matchmaking');
}

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
| Entrar na fila
|--------------------------------------------------------------------------
*/

$transactionStarted = false;

try {

    /*
    |--------------------------------------------------------------------------
    | Bloquear escrita durante operação
    |--------------------------------------------------------------------------
    */

    beginWriteTransaction($pdo);

    $transactionStarted = true;

    expireReadyChecksInTransaction($pdo);
    if (lobbyFormationBusy($pdo)) {
        throw new RuntimeException('Uma partida está em formação. Aguarde antes de entrar na fila.');
    }

    $activeMatch->execute([':player' => $playerId]);
    $accountState = $usersPdo->prepare('SELECT status FROM users WHERE id=?');
    $accountState->execute([userId()]);
    if ($accountState->fetchColumn() !== 'active') throw new RuntimeException('Sua conta não está ativa.');
    if ($activeMatch->fetchColumn()) throw new RuntimeException('Você já está em uma partida.');
    if (competitiveRestriction($pdo, (int) userId())) throw new RuntimeException('Você possui restrição competitiva. Consulte suas punições ou o suporte.');
    if (!$pdo->query("SELECT id FROM seasons WHERE status='active' AND starts_at<=CURRENT_TIMESTAMP AND ends_at>CURRENT_TIMESTAMP LIMIT 1")->fetchColumn()) throw new RuntimeException('Nenhuma temporada está ativa. Aguarde a abertura pela administração.');

    /*
    |--------------------------------------------------------------------------
    | Verificar se já está na fila
    |--------------------------------------------------------------------------
    */

    $statement =
        $pdo->prepare(
            '
            SELECT id
            FROM matchmaking_queue
            WHERE player_id = :player_id
            LIMIT 1
            '
        );

    $statement->execute([
        ':player_id' =>
            $playerId,
    ]);

    if ($statement->fetch()) {

        $pdo->exec(
            'ROLLBACK'
        );

        $transactionStarted =
            false;

        flash(
            'warning',
            'Você já está na fila.'
        );

        redirect(
            '/matchmaking'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Quantidade de jogadores
    |--------------------------------------------------------------------------
    */

    $queueCount =
        (int) $pdo
            ->query(
                '
                SELECT COUNT(*)
                FROM matchmaking_queue
                '
            )
            ->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Limite
    |--------------------------------------------------------------------------
    */

    if ($queueCount >= competitiveQueueTarget()) {

        $pdo->exec(
            'ROLLBACK'
        );

        $transactionStarted =
            false;

        flash(
            'warning',
            'A fila já está completa.'
        );

        redirect(
            '/matchmaking'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Adicionar jogador
    |--------------------------------------------------------------------------
    */

    $statement =
        $pdo->prepare(
            '
            INSERT INTO matchmaking_queue (
                player_id
            )
            VALUES (
                :player_id
            )
            '
        );

    $statement->execute([
        ':player_id' =>
            $playerId,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $newMatchId = createLobbyMatch($pdo);
    $pdo->exec('COMMIT');

    $transactionStarted =
        false;

    /*
    |--------------------------------------------------------------------------
    | Registrar cooldown
    |--------------------------------------------------------------------------
    */

    $_SESSION[$cooldownKey] =
        time();

    flash(
        'success',
        $newMatchId ? 'Lobby completo. Confirme sua presença em 60 segundos.' : 'Você entrou na fila.'
    );

} catch (Throwable $exception) {

    if ($transactionStarted) {

        try {

            $pdo->exec(
                'ROLLBACK'
            );

        } catch (Throwable) {
        }
    }

    flash(
        'error',
        $exception instanceof RuntimeException && !($exception instanceof PDOException) ? $exception->getMessage() : 'Não foi possível entrar na fila.'
    );
}

/*
|--------------------------------------------------------------------------
| Retornar
|--------------------------------------------------------------------------
*/

redirect(isset($newMatchId) && $newMatchId && !$transactionStarted ? '/partida/'.(int)$newMatchId : '/matchmaking');
