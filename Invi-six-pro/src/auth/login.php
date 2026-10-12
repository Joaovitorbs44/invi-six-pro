<?php

declare(strict_types=1);

$pageTitle = 'Entrar';

require_once __DIR__ . '/../components/head.php';
if (!isPost()) {
    $destination = safeLoginDestination($_GET['next'] ?? null);
    if ($destination) $_SESSION['login_destination'] = $destination;
}

if (isLoggedIn()) {
    require __DIR__ . '/../components/connected.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Processar login
|--------------------------------------------------------------------------
*/

if (isPost()) {

    verifyCsrf();
    require_once __DIR__.'/../includes/rate-limit.php';
    enforceRateLimit('login',15,600);

    $r6Nickname = trim(
        is_string($_POST['r6_nickname']??null)?$_POST['r6_nickname']:''
    );

    $password = is_string($_POST['password']??null)?$_POST['password']:'';
    enforceRateLimit('login-account:'.hash('sha256',mb_strtolower($r6Nickname)),30,600,false);

    /*
    |--------------------------------------------------------------------------
    | Manter nick após erro
    |--------------------------------------------------------------------------
    */

    $_SESSION['old'] = [
        'r6_nickname' => $r6Nickname,
    ];

    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    if (mb_strlen($r6Nickname) < 2 || mb_strlen($r6Nickname) > 50) {

        flash(
            'error',
            'Informe seu nick no Rainbow Six Siege.'
        );

        redirect('/login');
    }

    if ($password === '' || strlen($password) > 72 || str_contains($password, "\0")) {

        flash(
            'error',
            'Informe sua senha.'
        );

        redirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar usuário
    |--------------------------------------------------------------------------
    */

    try {

        $statement = $usersPdo->prepare(
            '
            SELECT
                id,
                name,
                r6_nickname,
                password_hash,
                session_version,
                role,
                status
            FROM users
            WHERE r6_nickname = :r6_nickname
            LIMIT 1
            '
        );

        $statement->execute([
            ':r6_nickname' => $r6Nickname,
        ]);

        $user = $statement->fetch();

    } catch (Throwable $exception) {
        flash('error', 'Não foi possível entrar na sua conta. Tente novamente.');
        redirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar usuário e senha
    |--------------------------------------------------------------------------
    */

    if (
        !$user ||
        !password_verify(
            $password,
            $user['password_hash']
        )
    ) {

        flash(
            'error',
            'Nick ou senha incorretos.'
        );

        redirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar status
    |--------------------------------------------------------------------------
    */

    if ($user['status'] !== 'active') {

        flash(
            'error',
            'Nick ou senha incorretos.'
        );

        redirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Criar sessão
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['session_version'] = (int)$user['session_version'];
    $_SESSION['authenticated_at']=time();
    $_SESSION['last_activity']=time();

    $_SESSION['user_id'] =
        (int) $user['id'];

    $_SESSION['user_name'] =
        (string) $user['name'];

    $_SESSION['user_r6_nickname'] =
        (string) $user['r6_nickname'];

    $_SESSION['user_role'] =
        (string) $user['role'];

    /*
    |--------------------------------------------------------------------------
    | Limpar dados antigos
    |--------------------------------------------------------------------------
    */

    clearOldInput();
    $destination = safeLoginDestination($_SESSION['login_destination'] ?? null);
    unset($_SESSION['login_destination']);
    if ($destination) redirect($destination);

    /*
    |--------------------------------------------------------------------------
    | Redirecionamento
    |--------------------------------------------------------------------------
    */

    if (
        $user['role'] === 'admin' ||
        $user['role'] === 'staff'
    ) {

        redirect(isAdmin() ? '/admin' : '/admin/support');
    }

    redirect('/matchmaking');
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
                    Área do jogador
                </span>

                <h1 class="mt-6 text-3xl font-semibold tracking-tight text-foreground">
                    Entre no lobby.
                </h1>

                <p class="mt-4 max-w-sm text-xs leading-6 text-muted">
                    Acesse sua conta para entrar no matchmaking, acompanhar suas partidas e disputar posições no ranking.
                </p>

                <div class="mt-10 border-t border-border pt-6">

                    <div class="flex items-start gap-4">

                        <span class="mt-[6px] h-2 w-2 shrink-0 rounded-full bg-[#FF3D2C]"></span>

                        <div>

                            <p class="text-xs font-medium text-foreground">
                                Acesso aprovado pela staff
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                Cada jogador precisa ser aprovado antes de receber acesso ao lobby.
                            </p>

                        </div>

                    </div>

                    <div class="mt-5 flex items-start gap-4">

                        <span class="mt-[6px] h-2 w-2 shrink-0 rounded-full bg-[#FF3D2C]"></span>

                        <div>

                            <p class="text-xs font-medium text-foreground">
                                Senha de acesso
                            </p>

                            <p class="mt-1 text-xs leading-5 text-muted">
                                Ative sua conta com nick R6 e código após a aprovação. Para recuperar o acesso, procure a staff.
                            </p>

                        </div>

                    </div>

                </div>

            </section>

<!-- Login -->

            <section class="auth-form-column w-full min-w-0 lg:flex-1">

                <div class="auth-form-content">

                    <div class="flex items-center justify-between">

                        <p class="text-[12px] font-medium uppercase tracking-wider text-[#FF3D2C]">
                            LOGIN
                        </p>

                        <span
                            id="login-step-counter"
                            class="text-[12px] font-medium text-muted"
                        >
                            1 de 2
                        </span>

                    </div>

                    <h2
                        id="login-step-title"
                        class="mt-3 text-2xl font-semibold tracking-tight text-foreground"
                    >
                        Identifique sua conta
                    </h2>

                    <p
                        id="login-step-description"
                        class="mt-2 text-xs leading-5 text-muted"
                    >
                        Informe seu nick no Rainbow Six Siege.
                    </p>

                    <!-- Progresso -->

                    <div class="mt-8">

                        <div class="flex gap-1.5">

                            <div
                                data-login-progress="1"
                                class="h-1 flex-1 rounded-full bg-[#FF3D2C] transition-all duration-300"
                            ></div>

                            <div
                                data-login-progress="2"
                                class="h-1 flex-1 rounded-full bg-border transition-all duration-300"
                            ></div>

                        </div>

                        <div class="mt-3 flex justify-between">

                            <span
                                data-login-label="1"
                                class="text-[12px] font-medium text-[#FF3D2C]"
                            >
                                Identificação
                            </span>

                            <span
                                data-login-label="2"
                                class="text-[12px] font-medium text-muted"
                            >
                                Acesso
                            </span>

                        </div>

                    </div>



                    <form
                        id="login-form"
                        action="/login"
                        method="POST"
                        class="mt-8"
                    >

                        <?= csrfField() ?>
<?php if ($authError): ?><p id="auth-error" class="auth-error"><?= e($authError) ?></p><?php endif; ?>

                        <!-- Step 1 -->

                        <div data-login-step="1">

                            

                            <fieldset class="ui-field"><legend><label class="ui-field-label" for="r6_nickname">Nick no Rainbow Six Siege</label></legend><input
                                type="text"
                                id="r6_nickname" <?php if ($authError): ?>aria-describedby="auth-error"<?php endif; ?>
                                name="r6_nickname"
                                value="<?= old('r6_nickname') ?>"
                                placeholder="Digite seu nick no jogo"
                                autocomplete="username"
                                required
                                maxlength="50"
                                class="h-12 w-full rounded-lg border border-border bg-surface px-4 text-xs text-foreground outline-none transition placeholder:text-muted hover:border-border focus:border-[#FF3D2C] focus:ring-2 focus:ring-[#FF3D2C]/10"
                            ></fieldset>

                            <button
                                type="button"
                                id="login-next"
                                class="mt-6 flex h-12 w-full items-center justify-center rounded-[4px] bg-[#FF3D2C] px-6 text-xs font-medium text-on-primary transition hover:bg-[#E93425]"
                            >
                                Continuar
                            </button>

                        </div>

                        <!-- Step 2 -->

                        <div data-login-step="2">

                            <div class="mb-5 flex items-center justify-between border-b border-border pb-4">

                                <div>

                                    <p class="text-[12px] uppercase tracking-wider text-muted">
                                        Entrando como
                                    </p>

                                    <p
                                        id="login-player-name"
                                        class="mt-1 text-xs font-medium text-foreground"
                                    >
                                        —
                                    </p>

                                </div>

                                <button
                                    type="button"
                                    id="change-player"
                                    class="text-[12px] font-medium text-muted transition hover:text-[#FF3D2C]"
                                >
                                    Alterar
                                </button>

                            </div>

                            <div>

                                <div class="mb-2 flex items-center justify-between">

                                    

                                    <span class="text-[12px] text-muted">
                                        Criada no primeiro acesso
                                    </span>

                                </div>

                                <div class="relative">

                                    <fieldset class="ui-field"><legend><label class="ui-field-label" for="password">Senha</label></legend><input
                                        type="password"
                                        id="password" <?php if ($authError): ?>aria-describedby="auth-error"<?php endif; ?>
                                        name="password"
                                        placeholder="Digite sua senha"
                                        autocomplete="current-password"
                                        required
                                        class="h-12 w-full rounded-lg border border-border bg-surface px-4 pr-16 text-xs text-foreground outline-none transition placeholder:text-muted hover:border-border focus:border-[#FF3D2C] focus:ring-2 focus:ring-[#FF3D2C]/10"
                                    ></fieldset>

                                    <button
                                        type="button"
                                        id="toggle-password"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-[12px] font-medium text-muted transition hover:text-foreground"
                                        aria-label="Mostrar senha"
                                    >
                                        Mostrar
                                    </button>

                                </div>

                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-2">

                                <button
                                    type="button"
                                    id="login-back"
                                    class="flex h-12 items-center justify-center rounded-[4px] border border-border bg-surface px-6 text-xs font-medium text-foreground transition hover:border-[#FF3D2C] hover:text-[#FF3D2C]"
                                >
                                    Voltar
                                </button>

                                <button
                                    type="submit"
                                    id="login-submit"
                                    class="flex h-12 items-center justify-center rounded-[4px] bg-[#FF3D2C] px-6 text-xs font-medium text-on-primary transition hover:bg-[#E93425] disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Entrar
                                </button>

                            </div>

                        </div>

                    </form>
<noscript><p class="auth-nojs">Preencha os campos abaixo e envie o formulário. As duas etapas estão disponíveis nesta página.</p></noscript>

                    <p class="mt-5 text-center text-xs text-muted">
                        Ainda não criou sua senha?
                        <a href="/primeiro-acesso/senha" class="font-medium text-foreground transition hover:text-[#FF3D2C]">Ativar com código</a>
                    </p>

                    <div class="mt-8 border-t border-border pt-6">

                        <p class="text-center text-xs text-muted">
                            Ainda não possui acesso?

                            <a
                                href="/register"
                                class="font-medium text-foreground transition hover:text-[#FF3D2C]"
                            >
                                Solicitar entrada
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
