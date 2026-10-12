<?php declare(strict_types=1); ?>
</main>
<footer class="site-footer border-t border-border bg-background">
    <div class="site-footer-main site-container">
        <div><a class="site-footer-brand" href="/"><span aria-hidden="true"></span>INVI SIX PRO</a><p>Partidas competitivas, ranking por temporada e uma comunidade feita para quem leva cada jogo a sério.</p></div>
        <nav aria-label="Plataforma"><h2>Plataforma</h2><a href="/">Início</a><a href="/ranking">Ranking</a><a href="/matchmaking">Matchmaking</a></nav>
        <nav aria-label="Atendimento"><h2>Atendimento</h2><a href="<?= isStaff()?'/admin/support':'/support' ?>">Central de suporte</a><a href="/#como-funciona">Como funciona</a><a href="/register">Solicitar acesso</a></nav>
    </div>
    <div class="site-footer-bottom site-container"><p>© <?= date('Y') ?> INVI SIX PRO. Todos os direitos reservados.</p><p>Feito para jogar sério, sem complicar.</p></div>
</footer>
<script src="<?= e(assetUrl('/assets/js/stage19.js')) ?>" defer></script>
<script src="<?= e(assetUrl('/assets/js/competitive-live.js')) ?>" defer></script>
<script src="<?= e(assetUrl('/assets/js/app.js')) ?>" defer></script>
<script src="<?= e(assetUrl('/assets/js/player-popovers.js')) ?>" defer></script>
</body>
</html>
