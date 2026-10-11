<?php

declare(strict_types=1);

$pageTitle = 'Solicitar entrada';

require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../includes/account-setup.php';

if (isLoggedIn()) {
    require __DIR__ . '/../components/connected.php';
    exit;
}

$ranks = [
    'Cobre',
    'Bronze',
    'Prata',
    'Ouro',
    'Platina',
    'Esmeralda',
    'Diamante',
    'Campeão',
];

/*
|--------------------------------------------------------------------------
| Processar solicitação
|--------------------------------------------------------------------------
*/

if (isPost()) {

    verifyCsrf();
    require_once __DIR__.'/../includes/rate-limit.php';
    enforceRateLimit('register',10,600);

    $name = trim(
        (string) ($_POST['name'] ?? '')
    );

    $r6Nickname = trim(
        (string) ($_POST['r6_nickname'] ?? '')
    );

    $r6TrackerUrl = trim(
        (string) ($_POST['r6_tracker_url'] ?? '')
    );

    $r6Rank = trim(
        (string) ($_POST['r6_rank'] ?? '')
    );

    /*
    |--------------------------------------------------------------------------
    | Salvar dados temporariamente
    |--------------------------------------------------------------------------
    */

    $_SESSION['old'] = [
        'name' => $name,
        'r6_nickname' => $r6Nickname,
        'r6_tracker_url' => $r6TrackerUrl,
        'r6_rank' => $r6Rank,
    ];

    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        flash(
            'error',
            'Informe seu nome.'
        );

        redirect('/register');
    }

    if (
        mb_strlen($name) < 2 ||
        mb_strlen($name) > 100
    ) {

        flash(
            'error',
            'O nome deve possuir entre 2 e 100 caracteres.'
        );

        redirect('/register');
    }

    if ($r6Nickname === '') {

        flash(
            'error',
            'Informe seu nick no Rainbow Six Siege.'
        );

        redirect('/register');
    }

    if (
        mb_strlen($r6Nickname) < 2 ||
        mb_strlen($r6Nickname) > 50
    ) {

        flash(
            'error',
            'O nick do Rainbow Six Siege deve possuir entre 2 e 50 caracteres.'
        );

        redirect('/register');
    }

    if ($r6TrackerUrl === '') {

        flash(
            'error',
            'Informe o link do seu perfil no R6 Tracker.'
        );

        redirect('/register');
    }

    if (
        filter_var(
            $r6TrackerUrl,
            FILTER_VALIDATE_URL
        ) === false
    ) {

        flash(
            'error',
            'Informe um link válido do R6 Tracker.'
        );

        redirect('/register');
    }

    if (!in_array(strtolower((string)parse_url($r6TrackerUrl, PHP_URL_SCHEME)), ['http','https'], true)) {
        flash('error', 'Use um link HTTP ou HTTPS do R6 Tracker.');
        redirect('/register');
    }
    $trackerHost = strtolower(
        (string) parse_url(
            $r6TrackerUrl,
            PHP_URL_HOST
        )
    );

    if (
        $trackerHost !== 'tracker.gg' &&
        $trackerHost !== 'www.tracker.gg' &&
        $trackerHost !== 'r6.tracker.network' &&
        $trackerHost !== 'www.r6.tracker.network'
    ) {

        flash(
            'error',
            'O link informado precisa ser de um perfil válido do R6 Tracker.'
        );

        redirect('/register');
    }

    if (
        !in_array(
            $r6Rank,
            $ranks,
            true
        )
    ) {

        flash(
            'error',
            'Selecione um rank válido.'
        );

        redirect('/register');
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar se já existe uma conta
    |--------------------------------------------------------------------------
    */

    $statement = $usersPdo->prepare(
        '
        SELECT id
        FROM users
        WHERE r6_nickname = :r6_nickname
        LIMIT 1
        '
    );

    $statement->execute([
        ':r6_nickname' => $r6Nickname,
    ]);

    if ($statement->fetch()) {

        flash(
            'error',
            'Este nick já possui acesso ao lobby.'
        );

        redirect('/register');
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar solicitação existente
    |--------------------------------------------------------------------------
    */

    $statement = $usersPdo->prepare(
        '
        SELECT
            id,
            status
        FROM access_requests
        WHERE r6_nickname = :r6_nickname
        LIMIT 1
        '
    );

    $statement->execute([
        ':r6_nickname' => $r6Nickname,
    ]);

    $existingRequest = $statement->fetch();

    if ($existingRequest) {

        if (
            $existingRequest['status']
            === 'pending'
        ) {

            flash(
                'error',
                'Já existe uma solicitação pendente para este nick.'
            );

            redirect('/register');
        }

        if (
            $existingRequest['status']
            === 'approved'
        ) {

            flash(
                'error',
                'Esta solicitação já foi aprovada.'
            );

            redirect('/register');
        }

        if (
            $existingRequest['status']
            === 'rejected'
        ) {

            flash(
                'error',
                'Já existe uma solicitação rejeitada para este nick. Entre em contato com a staff.'
            );

            redirect('/register');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Criar solicitação
    |--------------------------------------------------------------------------
    */

    try {

        $claimToken = bin2hex(random_bytes(32));
        $statement = $usersPdo->prepare(
            '
            INSERT INTO access_requests (
                name,
                r6_nickname,
                r6_tracker_url,
                r6_rank,
                claim_token_hash,
                status
            )
            VALUES (
                :name,
                :r6_nickname,
                :r6_tracker_url,
                :r6_rank,
                :claim_token_hash,
                :status
            )
            '
        );

        $statement->execute([
            ':name' => $name,
            ':r6_nickname' => $r6Nickname,
            ':r6_tracker_url' => $r6TrackerUrl,
            ':r6_rank' => $r6Rank,
            ':claim_token_hash' => hash('sha256', $claimToken),
            ':status' => 'pending',
        ]);

        clearOldInput();
        setClaimCookie($claimToken);

        flash(
            'success',
            'Solicitação enviada com sucesso. Agora aguarde a análise da staff.'
        );

        redirect('/solicitacao');

    } catch (PDOException $exception) {

        flash(
            'error',
            'Não foi possível enviar sua solicitação. Tente novamente.'
        );

        redirect('/register');
    }
}


$authError = $_SESSION['flash']['error'] ?? $_SESSION['flash']['warning'] ?? null;
require_once __DIR__ . '/../components/header.php';

?>

<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/auth.css')) ?>">
<section class="auth-page">

    

    <div class="auth-layout auth-flex flex min-h-[calc(100vh-80px)] w-full flex-col justify-center gap-16 px-5 py-12 sm:px-8 lg:flex-row lg:items-center lg:px-16 xl:px-32 2xl:px-80">

        

            <!-- Conteúdo -->

            <section class="auth-intro w-full min-w-0 lg:flex-1">

                <span class="inline-flex w-fit rounded-[8px] border border-border bg-surface px-4 py-1 text-xs font-medium text-muted">
                    Solicitação de entrada
                </span>

                <h1 class="mt-6 text-3xl font-semibold tracking-tight text-foreground">
                    Entre para o lobby.
                </h1>

                <p class="mt-4 max-w-sm text-xs leading-6 text-muted">
                    Envie seus dados para análise da staff. Seu perfil será verificado antes da liberação do acesso ao lobby.
                </p>

                <div class="mt-10 border-t border-border pt-6">

                    <div class="flex items-start gap-4">

                        <span class="mt-[6px] h-2 w-2 shrink-0 rounded-full bg-[#FF3D2C]"></span>

                        <div>

                            <p class="text-xs font-medium text-foreground">
                                Envie sua solicitação
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                Informe seus dados e seu perfil no Rainbow Six Siege.
                            </p>

                        </div>

                    </div>

                    <div class="mt-5 flex items-start gap-4">

                        <span class="mt-[6px] h-2 w-2 shrink-0 rounded-full bg-[#FF3D2C]"></span>

                        <div>

                            <p class="text-xs font-medium text-foreground">
                                Análise da staff
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                A staff verifica seu perfil no R6 Tracker antes de liberar o acesso.
                            </p>

                        </div>

                    </div>

                    <div class="mt-5 flex items-start gap-4">

                        <span class="mt-[6px] h-2 w-2 shrink-0 rounded-full bg-[#FF3D2C]"></span>

                        <div>

                            <p class="text-xs font-medium text-foreground">
                                Crie sua senha
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                Guarde o código exibido no acompanhamento. Após a aprovação, ative sua conta com nick R6, código e nova senha.
                            </p>

                        </div>

                    </div>

                </div>

            </section>
<!-- Registro -->

            <section class="auth-form-column w-full min-w-0 lg:flex-1">

                <div class="auth-form-content">

                    <!-- Cabeçalho -->

                    <div class="flex items-center justify-between">

                        <p class="text-[10px] font-medium uppercase tracking-wider text-[#FF3D2C]">
                            REGISTRO
                        </p>

                        <span
                            id="register-step-counter"
                            class="text-[10px] font-medium text-muted"
                        >
                            1 de 2
                        </span>

                    </div>

                    <h2
                        id="register-step-title"
                        class="mt-3 text-2xl font-semibold tracking-tight text-foreground"
                    >
                        Identifique-se
                    </h2>

                    <p
                        id="register-step-description"
                        class="mt-2 text-xs leading-5 text-muted"
                    >
                        Informe seu nome e seu nick no Rainbow Six Siege.
                    </p>

                    <!-- Progresso -->

                    <div class="mt-8">

                        <div class="flex gap-1.5">

                            <div
                                data-register-progress="1"
                                class="h-1 flex-1 rounded-full bg-[#FF3D2C] transition-all duration-300"
                            ></div>

                            <div
                                data-register-progress="2"
                                class="h-1 flex-1 rounded-full bg-border transition-all duration-300"
                            ></div>

                        </div>

                        <div class="mt-3 flex justify-between">

                            <span
                                data-register-label="1"
                                class="text-[10px] font-medium text-[#FF3D2C]"
                            >
                                Identificação
                            </span>

                            <span
                                data-register-label="2"
                                class="text-[10px] font-medium text-muted"
                            >
                                Perfil R6
                            </span>

                        </div>

                    </div>



                    <form
                        id="register-form"
                        action="/register"
                        method="POST"
                        class="mt-8"
                    >

                        <?= csrfField() ?>
<?php if ($authError): ?><p id="auth-error" class="auth-error"><?= e($authError) ?></p><?php endif; ?>

                        <!-- STEP 1 -->

                        <div data-register-step="1">

                            <!-- Nome -->

                            <div>

                                

                                <div class="ui-field"><label class="ui-field-label" for="name">Nome</label><input
                                    type="text"
                                    id="name" <?php if ($authError): ?>aria-describedby="auth-error"<?php endif; ?>
                                    name="name"
                                    value="<?= old('name') ?>"
                                    placeholder="Digite seu nome"
                                    autocomplete="name"
                                    required
                                    maxlength="100"
                                    class="h-12 w-full rounded-lg border border-border bg-surface px-4 text-xs text-foreground outline-none transition placeholder:text-muted hover:border-border focus:border-[#FF3D2C] focus:ring-2 focus:ring-[#FF3D2C]/10"
                                ></div>

                            </div>


                            <!-- Nick -->

                            <div class="mt-5">

                                

                                <div class="ui-field"><label class="ui-field-label" for="r6_nickname">Nick no Rainbow Six Siege</label><input
                                    type="text"
                                    id="r6_nickname" <?php if ($authError): ?>aria-describedby="auth-error"<?php endif; ?>
                                    name="r6_nickname"
                                    value="<?= old('r6_nickname') ?>"
                                    placeholder="Digite seu nick no jogo"
                                    autocomplete="username"
                                    required
                                    maxlength="50"
                                    class="h-12 w-full rounded-lg border border-border bg-surface px-4 text-xs text-foreground outline-none transition placeholder:text-muted hover:border-border focus:border-[#FF3D2C] focus:ring-2 focus:ring-[#FF3D2C]/10"
                                ></div>

                            </div>

                            <button
                                type="button"
                                id="register-next"
                                class="mt-6 flex h-12 w-full items-center justify-center rounded-[4px] bg-[#FF3D2C] px-6 text-xs font-medium text-on-primary transition hover:bg-[#E93425]"
                            >
                                Continuar
                            </button>

                        </div>

                        <!-- STEP 2 -->

                        <div data-register-step="2">

                            <!-- Jogador -->

                            <div class="mb-5 flex items-center justify-between border-b border-border pb-4">

                                <div>

                                    <p class="text-[10px] uppercase tracking-wider text-muted">
                                        Solicitando como
                                    </p>

                                    <p
                                        id="register-player-name"
                                        class="mt-1 text-xs font-medium text-foreground"
                                    >
                                        —
                                    </p>

                                </div>

                                <button
                                    type="button"
                                    id="change-register-player"
                                    class="text-[10px] font-medium text-muted transition hover:text-[#FF3D2C]"
                                >
                                    Alterar
                                </button>

                            </div>

                            <!-- Tracker -->

                            <div>

                                

                                <div class="ui-field"><label class="ui-field-label" for="r6_tracker_url">Link do R6 Tracker</label><input
                                    type="url"
                                    id="r6_tracker_url" <?php if ($authError): ?>aria-describedby="auth-error"<?php endif; ?>
                                    name="r6_tracker_url"
                                    value="<?= old('r6_tracker_url') ?>"
                                    placeholder="https://tracker.gg/..."
                                    autocomplete="url"
                                    required
                                    maxlength="500"
                                    class="h-12 w-full rounded-lg border border-border bg-surface px-4 text-xs text-foreground outline-none transition placeholder:text-muted hover:border-border focus:border-[#FF3D2C] focus:ring-2 focus:ring-[#FF3D2C]/10"
                                ></div>

                                <p class="mt-2 text-[10px] leading-4 text-muted">
                                    A staff utilizará este perfil para verificar sua conta.
                                </p>

                            </div>

                            <!-- Rank -->

                            <div class="mt-5">

                                

                                <div class="relative">

                                    <div class="ui-field"><label class="ui-field-label" for="r6_rank">Rank atual</label><select
                                        id="r6_rank" <?php if ($authError): ?>aria-describedby="auth-error"<?php endif; ?>
                                        name="r6_rank"
                                        required
                                        class="h-12 w-full appearance-none rounded-lg border border-border bg-surface px-4 pr-10 text-xs text-foreground outline-none transition hover:border-border focus:border-[#FF3D2C] focus:ring-2 focus:ring-[#FF3D2C]/10"
                                    >

                                        <option
                                            value=""
                                            disabled
                                            <?= old('r6_rank') === '' ? 'selected' : '' ?>
                                        >
                                            Selecione seu rank
                                        </option>

                                        <?php foreach ($ranks as $rank) { ?>

                                            <option
                                                value="<?= e($rank) ?>"
                                                <?= old('r6_rank') === $rank ? 'selected' : '' ?>
                                            >
                                                <?= e($rank) ?>
                                            </option>

                                        <?php } ?>

                                    </select></div>

                                    <?= faIcon('chevron-down', 'auth-select-icon') ?>

                                </div>

                            </div>

                            <!-- Informação -->

                            <div class="mt-5 flex items-start gap-3">

                                <span class="mt-[5px] h-1.5 w-1.5 shrink-0 rounded-full bg-[#FF3D2C]"></span>

                                <p class="text-[10px] leading-5 text-muted">
                                    Sua solicitação será analisada pela staff. Guarde o código de ativação para criar sua senha após a aprovação.
                                </p>

                            </div>

                            <!-- Botões -->

                            <div class="mt-6 grid grid-cols-2 gap-2">

                                <button
                                    type="button"
                                    id="register-back"
                                    class="flex h-12 items-center justify-center rounded-[4px] border border-border bg-surface px-6 text-xs font-medium text-foreground transition hover:border-[#FF3D2C] hover:text-[#FF3D2C]"
                                >
                                    Voltar
                                </button>

                                <button
                                    type="submit"
                                    id="register-submit"
                                    class="flex h-12 items-center justify-center rounded-[4px] bg-[#FF3D2C] px-6 text-xs font-medium text-on-primary transition hover:bg-[#E93425] disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Enviar solicitação
                                </button>

                            </div>

                        </div>

                    </form>
<noscript><p class="auth-nojs">Preencha os campos abaixo e envie o formulário. As duas etapas estão disponíveis nesta página.</p></noscript>

                    <p class="auth-return"><a href="/solicitacao">Acompanhar solicitação</a></p>
<!-- Login -->

                    <div class="mt-8 border-t border-border pt-6">

                        <p class="text-center text-xs text-muted">
                            Já possui acesso?

                            <a
                                href="/login"
                                class="font-medium text-foreground transition hover:text-[#FF3D2C]"
                            >
                                Entrar
                            </a>
                        </p>

                    </div>

                </div>

            </section>
    </div>
<p class="auth-return"><a href="/">Voltar para o início</a></p>
</section>

<script src="<?= e(assetUrl('/assets/js/auth-steps.js')) ?>" defer></script>

<?php

clearOldInput();

require_once __DIR__ . '/../components/footer.php';
