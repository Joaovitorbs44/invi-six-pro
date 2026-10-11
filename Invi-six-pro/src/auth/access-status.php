<?php
declare(strict_types=1);
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../includes/account-setup.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$request = findClaimRequest($pdo, claimCookieToken());
if (!$request) clearClaimCookie();
$pageTitle = 'Sua solicitação';
require_once __DIR__ . '/../components/header.php';
?>
<section class="flex min-h-[calc(100vh-64px)] items-center justify-center bg-background px-6 py-10">
    <div class="w-full max-w-[420px]" <?= $request && $request['status'] === 'pending' ? 'data-access-pending' : '' ?>>
        <?php if ($request && $request['activated_at'] === null && in_array($request['status'], ['pending', 'approved'], true)) { ?>
            <div class="mb-6 rounded-lg border border-border p-4">
                <p class="text-xs font-medium text-foreground">Código de ativação</p>
                <code class="mt-2 block break-all text-xs text-muted"><?= e(claimCookieToken()) ?></code>
                <p class="mt-2 text-xs text-muted">Guarde este código em local privado. Não compartilhe sua senha.</p>
            </div>
        <?php } ?>
        <?php if (!$request) { ?>
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#FF3D2C]">Sua solicitação</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-foreground">Solicitação indisponível</h1>
            <p class="mt-4 text-xs leading-6 text-muted">Esta solicitação não está mais disponível.</p>
            <p class="mt-2 text-xs leading-6 text-muted">Se você guardou o código, pode ativar sua conta após a aprovação. Se perdeu o código e os dados deste navegador, procure a staff.</p>
            <a href="/primeiro-acesso/senha" class="mt-4 inline-block text-xs font-medium text-[#FF3D2C]">Ativar com código</a>
            <a href="/register" class="mt-6 inline-block text-xs font-medium text-[#FF3D2C]">Fazer nova solicitação</a>
        <?php } elseif ($request['status'] === 'approved') { ?>
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#FF3D2C]">Acesso aprovado</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-foreground">Sua entrada no Lobby R6 foi aprovada.</h1>
            <p class="mt-4 text-xs leading-6 text-muted">Use seu nick R6, o código de ativação e uma nova senha para ativar sua conta.</p>
            <a href="/primeiro-acesso/senha" class="mt-6 flex h-12 items-center justify-center rounded-[4px] bg-[#FF3D2C] text-xs font-medium text-on-primary hover:bg-[#E93425]">Criar minha senha</a>
        <?php } elseif ($request['status'] === 'rejected') { ?>
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#FF3D2C]">Solicitação não aprovada</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-foreground">Solicitação não aprovada</h1>
            <p class="mt-4 text-xs leading-6 text-muted">Sua solicitação não foi aprovada pela staff.</p>
            <a href="/login" class="mt-6 inline-block text-xs font-medium text-[#FF3D2C]">Voltar ao login</a>
        <?php } else { ?>
            <p class="text-[10px] font-medium uppercase tracking-wider text-[#FF3D2C]">Solicitação recebida</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-foreground"><?= e($request['r6_nickname']) ?></h1>
            <p class="mt-4 text-xs leading-6 text-muted">Sua solicitação foi enviada para análise.</p>
            <p class="mt-6 text-[10px] uppercase tracking-wider text-muted">Status</p>
            <p class="mt-2 flex items-center gap-2 text-xs font-medium text-foreground"><span class="h-2 w-2 rounded-full bg-[#FF3D2C]"></span>Aguardando aprovação</p>
            <p class="mt-4 text-xs leading-6 text-muted">Você não precisa fazer mais nada. Esta página será atualizada quando sua solicitação for analisada.</p>
            <p class="mt-2 text-xs leading-6 text-muted">Guarde o código de ativação acima. Você poderá usá-lo em outro navegador após a aprovação.</p>
        <?php } ?>
    </div>
</section>
<script src="<?= e(assetUrl('/assets/js/access-status.js')) ?>" defer></script>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
