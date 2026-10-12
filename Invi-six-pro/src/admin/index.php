<?php

declare(strict_types=1);

$pageTitle = 'Admin';

require_once __DIR__ . '/../components/head.php';

requireStaff();

/*
|--------------------------------------------------------------------------
| Solicitações
|--------------------------------------------------------------------------
*/

$statement = $usersPdo->query(
    "
    SELECT
        id,
        name,
        activated_at,
        NOT EXISTS (SELECT 1 FROM users WHERE users.r6_nickname = access_requests.r6_nickname) AS needs_activation,
        r6_nickname,
        r6_tracker_url,
        r6_rank,
        status,
        reviewed_at,
        created_at
    FROM access_requests
    ORDER BY
        CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END,
        created_at DESC
    "
);

$requests = $statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Usuários
|--------------------------------------------------------------------------
*/

$statement = $usersPdo->query(
    "
    SELECT
        users.id,
        users.name,
        users.r6_nickname,
        users.role,
        users.status,
        users.created_at,
        players.r6_rank,
        players.r6_tracker_url
    FROM users
    LEFT JOIN players
        ON players.user_id = users.id
    ORDER BY
        CASE users.role
            WHEN 'admin' THEN 1
            WHEN 'staff' THEN 2
            ELSE 3
        END,
        users.created_at DESC
    "
);

$users = $statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Contadores
|--------------------------------------------------------------------------
*/

$totalRequests = count($requests);

$pendingRequests = 0;
$approvedRequests = 0;
$rejectedRequests = 0;

foreach ($requests as $request) {

    if ($request['status'] === 'pending') {
        $pendingRequests++;
    }

    if ($request['status'] === 'approved') {
        $approvedRequests++;
    }

    if ($request['status'] === 'rejected') {
        $rejectedRequests++;
    }
}

$totalUsers = count($users);


require_once __DIR__ . '/../components/header.php';

?>

<section class="admin17 min-h-[calc(100vh-64px)] bg-surface">

    <div class="mx-auto max-w-7xl px-6 py-12">

        <!-- Cabeçalho -->

        <div class="flex items-end justify-between gap-8 border-b border-border pb-8">

            <div>

                <p class="text-[12px] font-medium uppercase tracking-wider text-[#FF3D2C]">
                    ADMINISTRAÇÃO
                </p>

                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-foreground">
                    Gerenciar Lobby
                </h1>

                <p class="mt-3 max-w-xl text-xs leading-5 text-muted">
                    Analise solicitações, gerencie jogadores e controle o acesso ao lobby.
                </p>

            </div>

            <div class="text-right">

                <p class="text-[12px] uppercase tracking-wider text-muted">
                    Administrador
                </p>

                <p class="mt-1 text-xs font-medium text-foreground">
                    <?= e(
                        $_SESSION['user_r6_nickname']
                        ?? $_SESSION['user_name']
                        ?? 'Admin'
                    ) ?>
                </p>

            </div>

        </div>
        <!-- Alertas -->



        <!-- Navegação interna -->

        <div class="mt-8 flex items-center gap-1 rounded-lg border border-border bg-surface p-1">

            <button
                type="button"
                data-admin-tab="requests"
                class="admin-tab rounded-md bg-primary px-5 py-2.5 text-[12px] font-medium text-on-primary transition"
            >
                Solicitações
            </button>

            <button
                type="button"
                data-admin-tab="users"
                class="admin-tab rounded-md px-5 py-2.5 text-[12px] font-medium text-muted transition hover:text-foreground"
            >
                Usuários
            </button>

        </div>

        <!-- ============================================================ -->
        <!-- SOLICITAÇÕES -->
        <!-- ============================================================ -->

        <div data-admin-panel="requests">

            <!-- Resumo -->

            <div class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-border bg-border lg:grid-cols-4">

                <div class="bg-surface p-5">

                    <p class="text-[12px] uppercase tracking-wider text-muted">
                        Total
                    </p>

                    <p class="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                        <?= $totalRequests ?>
                    </p>

                </div>

                <div class="bg-surface p-5">

                    <p class="text-[12px] uppercase tracking-wider text-muted">
                        Pendentes
                    </p>

                    <div class="mt-3 flex items-center gap-2">

                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>

                        <p class="text-2xl font-semibold tracking-tight text-foreground">
                            <?= $pendingRequests ?>
                        </p>

                    </div>

                </div>

                <div class="bg-surface p-5">

                    <p class="text-[12px] uppercase tracking-wider text-muted">
                        Aprovadas
                    </p>

                    <div class="mt-3 flex items-center gap-2">

                        <span class="h-2 w-2 rounded-full bg-green-500"></span>

                        <p class="text-2xl font-semibold tracking-tight text-foreground">
                            <?= $approvedRequests ?>
                        </p>

                    </div>

                </div>

                <div class="bg-surface p-5">

                    <p class="text-[12px] uppercase tracking-wider text-muted">
                        Rejeitadas
                    </p>

                    <div class="mt-3 flex items-center gap-2">

                        <span class="h-2 w-2 rounded-full bg-red-500"></span>

                        <p class="text-2xl font-semibold tracking-tight text-foreground">
                            <?= $rejectedRequests ?>
                        </p>

                    </div>

                </div>

            </div>

            <!-- Filtros -->

            <div class="mt-10 flex items-center justify-between gap-6">

                <div>

                    <h2 class="text-sm font-semibold text-foreground">
                        Jogadores
                    </h2>

                    <p class="mt-1 text-[12px] text-muted">
                        Solicitações recebidas pelo lobby.
                    </p>

                </div>

                <div
                    class="flex items-center gap-1 rounded-lg border border-border bg-surface p-1"
                    aria-label="Filtrar solicitações"
                >

                    <button
                        type="button"
                        data-request-filter="all"
                        class="request-filter rounded-md bg-primary px-4 py-2 text-[12px] font-medium text-on-primary transition"
                    >
                        Todas
                    </button>

                    <button
                        type="button"
                        data-request-filter="pending"
                        class="request-filter rounded-md px-4 py-2 text-[12px] font-medium text-muted transition hover:text-foreground"
                    >
                        Pendentes
                    </button>

                    <button
                        type="button"
                        data-request-filter="approved"
                        class="request-filter rounded-md px-4 py-2 text-[12px] font-medium text-muted transition hover:text-foreground"
                    >
                        Aprovadas
                    </button>

                    <button
                        type="button"
                        data-request-filter="rejected"
                        class="request-filter rounded-md px-4 py-2 text-[12px] font-medium text-muted transition hover:text-foreground"
                    >
                        Rejeitadas
                    </button>

                </div>

            </div>

            <!-- Lista -->

            <div
                id="request-list"
                class="mt-5 space-y-3"
            >

                <?php if ($requests === []) { ?>

                    <div class="rounded-lg border border-dashed border-border px-6 py-16 text-center">

                        <p class="text-sm font-medium text-foreground">
                            Nenhuma solicitação.
                        </p>

                        <p class="mt-2 text-xs text-muted">
                            Novos pedidos de acesso aparecerão aqui.
                        </p>

                    </div>

                <?php } ?>

                <?php foreach ($requests as $request) { ?>

                    <?php

                    $status = $request['status'];

                    $statusLabel = match ($status) {
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        default => 'Desconhecido',
                    };

                    $statusClasses = match ($status) {
                        'pending' =>
                            'bg-amber-50 text-amber-700 border-amber-200',

                        'approved' =>
                            'bg-green-50 text-green-700 border-green-200',

                        'rejected' =>
                            'bg-red-50 text-red-700 border-red-200',

                        default =>
                            'bg-surface text-muted border-border',
                    };

                    ?>

                    <article
                        data-request-card
                        data-status="<?= e($status) ?>"
                        class="rounded-lg border border-border bg-surface transition hover:border-border"
                    >

                        <div class="flex items-center gap-6 p-5">

                            <div class="min-w-0 flex-1">

                                <div class="flex items-center gap-3">

                                    <p class="truncate text-sm font-semibold text-foreground">
                                        <?= e($request['r6_nickname']) ?>
                                    </p>

                                    <span
                                        class="badge19 border <?= $statusClasses ?>"
                                    >
                                        <?= e($statusLabel) ?>
                                    </span>

                                </div>

                                <p class="mt-1 truncate text-[12px] text-muted">
                                    <?= e($request['name']) ?>
                                </p>

                            </div>

                            <div class="hidden w-32 md:block">

                                <p class="text-[12px] uppercase tracking-wider text-muted">
                                    Rank
                                </p>

                                <p class="mt-1 text-xs font-medium text-foreground">
                                    <?= e($request['r6_rank']) ?>
                                </p>

                            </div>

                            <div class="hidden w-40 lg:block">

                                <p class="text-[12px] uppercase tracking-wider text-muted">
                                    Solicitação
                                </p>

                                <p class="mt-1 text-xs text-muted">
                                    <?= e(
                                        brazilDate($request['created_at'])
                                    ) ?>
                                </p>
                                <?php if ($request['reviewed_at']): ?><p class="mt-1 text-[12px] text-muted">Revisão: <?= e(brazilDate($request['reviewed_at'])) ?></p><?php endif; ?>

                            </div>

                            <a
                                href="<?= e($request['r6_tracker_url']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hidden text-[12px] font-medium text-muted transition hover:text-[#FF3D2C] sm:block"
                            >
                                R6 Tracker <?= faIcon('arrow-up-right-from-square', 'fa-sm') ?>
                            </a>

                            <?php if ($status === 'pending') { ?>

                                <div class="flex items-center gap-2">
                                    <?php if (isAdmin() && $request['activated_at'] === null && $request['needs_activation']) { ?>
                                    <button type="button" data-delete-kind="request" data-delete-id="<?= (int) $request['id'] ?>" data-player="<?= e($request['r6_nickname']) ?>" class="h-9 rounded-[4px] border border-border px-3 text-[12px] text-red-600 hover:bg-red-50">Excluir solicitação</button>
                                    <?php } ?>

                                    <button
                                        type="button"
                                        data-reject-request="<?= (int) $request['id'] ?>"
                                        data-player="<?= e($request['r6_nickname']) ?>"
                                        class="h-9 rounded-[4px] border border-border bg-surface px-4 text-[12px] font-medium text-muted transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                    >
                                        Rejeitar
                                    </button>

                                    <button
                                        type="button"
                                        data-approve-request="<?= (int) $request['id'] ?>"
                                        data-player="<?= e($request['r6_nickname']) ?>"
                                        class="h-9 rounded-[4px] bg-[#FF3D2C] px-4 text-[12px] font-medium text-on-primary transition hover:bg-[#E93425]"
                                    >
                                        Aprovar
                                    </button>

                                </div>

                            <?php } else { ?>

                                <div class="w-[142px] text-right">

                                    <?php if (isAdmin() && $status === 'approved' && $request['activated_at'] === null && $request['needs_activation']) { ?>
                                    <button type="button" data-delete-kind="request" data-delete-id="<?= (int) $request['id'] ?>" data-player="<?= e($request['r6_nickname']) ?>" class="h-9 rounded-[4px] border border-border px-3 text-[12px] text-red-600 hover:bg-red-50">Excluir solicitação</button>
                                    <?php } else { ?>
                                    <span class="text-[12px] text-muted">
                                        Analisado
                                    </span>
                                    <?php } ?>

                                </div>

                            <?php } ?>

                        </div>

                    </article>

                <?php } ?>

            </div>

        </div>

        <!-- ============================================================ -->
        <!-- USUÁRIOS -->
        <!-- ============================================================ -->

        <div
            data-admin-panel="users"
            class="hidden"
        >

            <div class="mt-8 rounded-lg border border-border bg-surface p-5">

                <p class="text-[12px] uppercase tracking-wider text-muted">
                    Usuários cadastrados
                </p>

                <p class="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                    <?= $totalUsers ?>
                </p>

            </div>

            <div class="mt-10 flex items-end justify-between gap-6">

                <div>

                    <h2 class="text-sm font-semibold text-foreground">
                        Usuários
                    </h2>

                    <p class="mt-1 text-[12px] text-muted">
                        Gerencie as contas que possuem acesso ao Lobby R6.
                    </p>

                </div>

                <div class="ui-field w-full max-w-xs"><label class="ui-field-label" for="user-search">Buscar usuário</label>
<input type="search" id="user-search"
                        placeholder="Buscar usuário..."
                        autocomplete="off"
                       
                    >

                </div>

            </div>

            <div class="mt-5 space-y-3">

                <?php if ($users === []) { ?>

                    <div class="rounded-lg border border-dashed border-border px-6 py-16 text-center">

                        <p class="text-sm font-medium text-foreground">
                            Nenhum usuário cadastrado.
                        </p>

                    </div>

                <?php } ?>

                <?php foreach ($users as $user) { ?>

                    <?php

                    $userStatusLabel = match ($user['status']) {
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                        'banned' => 'Bloqueado',
                        default => 'Desconhecido',
                    };

                    $userStatusClasses = match ($user['status']) {
                        'active' =>
                            'border-green-200 bg-green-50 text-green-700',

                        'inactive' =>
                            'border-border bg-surface text-muted',

                        'banned' =>
                            'border-red-200 bg-red-50 text-red-700',

                        default =>
                            'border-border bg-surface text-muted',
                    };

                    $roleLabel = match ($user['role']) {
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        default => 'Jogador',
                    };

                    ?>

                    <article
                        data-user-card
                        data-user-search="<?= e(
                            strtolower(
                                $user['name']
                                . ' '
                                . $user['r6_nickname']
                            )
                        ) ?>"
                        class="rounded-lg border border-border bg-surface transition hover:border-border"
                    >

                        <div class="flex items-center gap-6 p-5">

                            <div class="min-w-0 flex-1">

                                <div class="flex items-center gap-3">

                                    <p class="truncate text-sm font-semibold text-foreground">
                                        <?= e($user['r6_nickname']) ?>
                                    </p>

                                    <span class="badge19 badge19-<?= e($user['role']) ?>">
                                        <?= e($roleLabel) ?>
                                    </span>

                                    <span class="badge19 badge19-<?= e($user['status']) ?>">
                                        <?= e($userStatusLabel) ?>
                                    </span>

                                </div>

                                <p class="mt-1 truncate text-[12px] text-muted">
                                    <?= e($user['name']) ?>
                                </p>

                            </div>

                            <div class="hidden w-32 md:block">

                                <p class="text-[12px] uppercase tracking-wider text-muted">
                                    Rank R6
                                </p>

                                <p class="mt-1 text-xs font-medium text-foreground">
                                    <?= e(
                                        $user['r6_rank']
                                        ?? '—'
                                    ) ?>
                                </p>

                            </div>

                            <div class="hidden w-40 lg:block">

                                <p class="text-[12px] uppercase tracking-wider text-muted">
                                    Criado em
                                </p>

                                <p class="mt-1 text-xs text-muted">
                                    <?= e(
                                        brazilDate($user['created_at'])
                                    ) ?>
                                </p>

                            </div>

                            <?php if (
                                $user['r6_tracker_url'] !== null
                                && $user['r6_tracker_url'] !== ''
                            ) { ?>

                                <a
                                    href="<?= e($user['r6_tracker_url']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="hidden text-[12px] font-medium text-muted transition hover:text-[#FF3D2C] sm:block"
                                >
                                    R6 Tracker <?= faIcon('arrow-up-right-from-square', 'fa-sm') ?>
                                </a>

                            <?php } ?>

                            <?php if (isAdmin()): ?><button
                                type="button"
                                data-reset-password="<?= (int) $user['id'] ?>"
                                data-player="<?= e($user['r6_nickname']) ?>"
                                class="h-9 whitespace-nowrap rounded-[4px] border border-border bg-surface px-4 text-[12px] font-medium text-muted transition hover:border-[#FF3D2C]/30 hover:bg-[#FF3D2C]/5 hover:text-[#FF3D2C]"
                            >
                                Redefinir senha
                            </button><?php endif; ?>
                            <?php if (isAdmin() && $user['role'] === 'player') { ?>
                            <button type="button" data-delete-kind="player" data-delete-id="<?= (int) $user['id'] ?>" data-player="<?= e($user['r6_nickname']) ?>" class="h-9 whitespace-nowrap rounded-[4px] border border-red-200 px-4 text-[12px] font-medium text-red-600 hover:bg-red-50">Remover jogador</button>
                            <?php } ?>

                        </div>

                    </article>

                <?php } ?>

            </div>

        </div>

    </div>

</section>

<!-- ================================================================ -->
<!-- MODAL APROVAR -->
<!-- ================================================================ -->

<div
    id="approve-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/20 px-6 backdrop-blur-[2px]"
    aria-hidden="true"
>

    <div class="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl">

        <div class="flex items-start justify-between gap-6">

            <div>

                <p class="text-[12px] font-medium uppercase tracking-wider text-[#FF3D2C]">
                    APROVAR ACESSO
                </p>

                <h2 class="mt-2 text-xl font-semibold tracking-tight text-foreground">
                    Liberar jogador
                </h2>

            </div>

            <button
                type="button"
                data-close-approve
                class="text-lg leading-none text-muted transition hover:text-foreground"
                aria-label="Fechar"
            >
                <?= faIcon('xmark') ?>
            </button>

        </div>

        <p class="mt-4 text-xs leading-5 text-muted">
            Confirme a aprovação de
            <strong
                id="approve-player-name"
                class="font-medium text-foreground"
            ></strong>.
        </p>

        <form
            action="/admin/access/approve"
            method="POST"
            class="mt-6"
        >

            <?= csrfField() ?>

            <input
                type="hidden"
                name="request_id"
                id="approve-request-id"
            >

            <p class="text-xs leading-5 text-muted">Depois da aprovação, a página do jogador será atualizada e ele poderá criar a própria senha pelo site.</p>

            <div class="mt-6 grid grid-cols-2 gap-2">

                <button
                    type="button"
                    data-close-approve
                    class="flex h-11 items-center justify-center rounded-[4px] border border-border bg-surface text-[12px] font-medium text-foreground transition hover:border-border"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="flex h-11 items-center justify-center rounded-[4px] bg-[#FF3D2C] text-[12px] font-medium text-on-primary transition hover:bg-[#E93425]"
                >
                    Aprovar acesso
                </button>

            </div>

        </form>

    </div>

</div>

<!-- ================================================================ -->
<!-- MODAL REJEITAR -->
<!-- ================================================================ -->

<div
    id="reject-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/20 px-6 backdrop-blur-[2px]"
    aria-hidden="true"
>

    <div class="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl">

        <div class="flex items-start justify-between gap-6">

            <div>

                <p class="text-[12px] font-medium uppercase tracking-wider text-red-500">
                    REJEITAR SOLICITAÇÃO
                </p>

                <h2 class="mt-2 text-xl font-semibold tracking-tight text-foreground">
                    Rejeitar jogador
                </h2>

            </div>

            <button
                type="button"
                data-close-reject
                class="text-lg leading-none text-muted transition hover:text-foreground"
                aria-label="Fechar"
            >
                <?= faIcon('xmark') ?>
            </button>

        </div>

        <p class="mt-4 text-xs leading-5 text-muted">
            Confirme a rejeição da solicitação de
            <strong
                id="reject-player-name"
                class="font-medium text-foreground"
            ></strong>.
        </p>

        <form
            action="/admin/access/reject"
            method="POST"
            class="mt-6"
        >

            <?= csrfField() ?>

            <input
                type="hidden"
                name="request_id"
                id="reject-request-id"
            >

            <div class="grid grid-cols-2 gap-2">

                <button
                    type="button"
                    data-close-reject
                    class="flex h-11 items-center justify-center rounded-[4px] border border-border bg-surface text-[12px] font-medium text-foreground transition hover:border-border"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="flex h-11 items-center justify-center rounded-[4px] bg-red-500 text-[12px] font-medium text-white transition hover:bg-red-600"
                >
                    Rejeitar
                </button>

            </div>

        </form>

    </div>

</div>

<!-- ================================================================ -->
<!-- MODAL REDEFINIR SENHA -->
<!-- ================================================================ -->

<div
    id="reset-password-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/20 px-6 backdrop-blur-[2px]"
    aria-hidden="true"
>

    <div class="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl">

        <div class="flex items-start justify-between gap-6">

            <div>

                <p class="text-[12px] font-medium uppercase tracking-wider text-[#FF3D2C]">
                    REDEFINIR SENHA
                </p>

                <h2 class="mt-2 text-xl font-semibold tracking-tight text-foreground">
                    Nova senha
                </h2>

            </div>

            <button
                type="button"
                data-close-reset-password
                class="text-lg leading-none text-muted transition hover:text-foreground"
                aria-label="Fechar"
            >
                <?= faIcon('xmark') ?>
            </button>

        </div>

        <p class="mt-4 text-xs leading-5 text-muted">
            Defina uma nova senha para
            <strong
                id="reset-password-player-name"
                class="font-medium text-foreground"
            ></strong>.
        </p>

        <form
            action="/admin/user/password/reset"
            method="POST"
            class="mt-6"
        >

            <?= csrfField() ?>

            <input
                type="hidden"
                name="user_id"
                id="reset-password-user-id"
            >

            <div class="flex items-center justify-between">

                

                <button
                    type="button"
                    id="generate-reset-password"
                    class="text-[12px] font-medium text-[#FF3D2C] transition hover:text-[#E93425]"
                >
                    Gerar senha
                </button>

            </div>

            <div class="relative mt-2">

                <div class="ui-field"><label class="ui-field-label" for="reset-password-input">Nova senha</label><input
                    type="password"
                    name="password"
                    id="reset-password-input"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Mínimo de 8 caracteres"
                   
                ></div>

                <button
                    type="button"
                    id="toggle-reset-password"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-[12px] font-medium text-muted transition hover:text-foreground"
                >
                    Mostrar
                </button>

            </div>

            <p class="mt-2 text-[12px] leading-4 text-muted">
                A senha atual será substituída imediatamente.
            </p>

            <div class="mt-6 grid grid-cols-2 gap-2">

                <button
                    type="button"
                    data-close-reset-password
                    class="flex h-11 items-center justify-center rounded-[4px] border border-border bg-surface text-[12px] font-medium text-foreground transition hover:border-border"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="flex h-11 items-center justify-center rounded-[4px] bg-[#FF3D2C] text-[12px] font-medium text-on-primary transition hover:bg-[#E93425]"
                >
                    Redefinir senha
                </button>

            </div>

        </form>

    </div>

</div>

<script src="<?= e(assetUrl('/assets/js/admin.js')) ?>" defer></script>

<?php

require_once __DIR__ . '/../components/admin-delete-modal.php';
require_once __DIR__ . '/../components/footer.php';
