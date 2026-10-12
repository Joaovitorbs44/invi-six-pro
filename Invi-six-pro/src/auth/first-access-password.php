<?php
declare(strict_types=1);
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../includes/account-setup.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if (isLoggedIn()) redirect(isStaff() ? '/admin' : '/matchmaking');
if (isPost()) {
    verifyCsrf();
    require_once __DIR__.'/../includes/rate-limit.php';
    enforceRateLimit('activation',10,600);
}
$request = findClaimRequest($pdo, claimCookieToken());
if (isPost()) {
    $nickname = trim(is_string($_POST['r6_nickname'] ?? null) ? $_POST['r6_nickname'] : '');
    $claimToken = trim(is_string($_POST['activation_code'] ?? null) ? $_POST['activation_code'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
    try {
        if ($password !== $confirmation) throw new RuntimeException('As senhas precisam ser iguais.');
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 50 || !preg_match('/\A[a-f0-9]{64}\z/', $claimToken)) {
            throw new RuntimeException('Informe seu nick R6 e um código de ativação válido.');
        }
        enforceRateLimit('activation-account:' . hash('sha256', mb_strtolower($nickname)), 10, 600, false);
        completeClaimAccess($pdo, $nickname, $claimToken, $password);
        clearClaimCookie();
        session_regenerate_id(true);
        flash('success', 'Conta criada com sucesso. Entre com seu Nick R6 e sua senha.');
        redirect('/login');
    } catch (Throwable $exception) {
        flash('error', $exception instanceof RuntimeException && !($exception instanceof PDOException)
            ? $exception->getMessage() : 'Não foi possível ativar sua conta. Tente novamente.');
        redirect('/primeiro-acesso/senha');
    }
}
$pageTitle = 'Crie sua senha';
require_once __DIR__ . '/../components/header.php';
?>
<section class="flex min-h-[calc(100vh-64px)] items-center justify-center bg-background px-6 py-10">
    <div class="w-full max-w-[420px]">
        <p class="text-[12px] font-medium uppercase tracking-wider text-[#FF3D2C]">Crie sua senha</p>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-foreground">Crie sua senha</h1>
        <p class="mt-2 text-xs leading-5 text-muted">Após a aprovação da staff, informe seu nick R6, o código de ativação e uma nova senha.</p>
        <form action="/primeiro-acesso/senha" method="POST" class="mt-8">
            <?= csrfField() ?>
            <div class="ui-field"><label class="ui-field-label" for="r6_nickname">Nick R6</label><input id="r6_nickname" name="r6_nickname" type="text" autocomplete="username" required minlength="2" maxlength="50" value="<?= e($request['r6_nickname'] ?? '') ?>"
                placeholder="Digite seu nick no jogo"></div>
            <div class="ui-field"><label class="ui-field-label" for="activation_code">Código de ativação</label><input id="activation_code" name="activation_code" type="text" autocomplete="off" required minlength="64" maxlength="64" pattern="[a-f0-9]{64}" spellcheck="false"
                placeholder="Digite seu código de ativação"></div>
            
            <div class="ui-field"><label class="ui-field-label" for="password">Nova senha</label><input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"
                placeholder="Digite sua senha"></div>
            <p class="mt-2 text-[12px] text-muted">Mínimo de 8 caracteres e máximo de 72 bytes (acentos podem ocupar mais de um byte).</p>
            
            <div class="ui-field"><label class="ui-field-label" for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8"
                placeholder="Confirme sua senha"></div>
            <button type="submit" class="mt-6 flex h-12 w-full items-center justify-center rounded-[4px] bg-[#FF3D2C] text-xs font-medium text-on-primary hover:bg-[#E93425]">Criar minha senha</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
