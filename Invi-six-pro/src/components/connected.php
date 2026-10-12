<?php
declare(strict_types=1);
$pageTitle = 'Você já está conectado · INVI SIX PRO';
require __DIR__ . '/header.php';
?>
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/auth.css')) ?>">
<section class="auth-page auth-connected" aria-labelledby="connected-title">
  <div class="auth-connected-content"><p class="auth-eyebrow">ÁREA DO JOGADOR</p><h1 id="connected-title">Você já está conectado.</h1><p>Sua sessão está ativa. Escolha para onde deseja ir.</p>
    <div class="auth-connected-actions">
      <?php if (userRole() !== 'staff'): ?><a class="auth-primary" href="/matchmaking">Ir para o matchmaking</a><?php else: ?><a class="auth-primary" href="/admin/support">Ir para o suporte</a><?php endif; ?>
      <a class="auth-secondary" href="/">Voltar para o início</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
