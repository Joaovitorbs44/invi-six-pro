<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Lobby R6';
require_once __DIR__ . '/icons.php';

?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">

<head>

  <meta charset="UTF-8">
  <meta name="color-scheme" content="dark">
  <meta name="theme-color" content="#171C26">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="/assets/images/logo-invisix.svg">

  <script src="<?= e(assetUrl('/assets/js/dates.js')) ?>" defer></script>

  <link rel="stylesheet" href="<?= e(assetUrl('/assets/css/tailwind.css')) ?>">

  <link
    rel="preconnect"
    href="https://fonts.googleapis.com">

  <link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

  <link
    href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap"
    rel="stylesheet">






</head>

<body class="min-h-screen bg-background">

  <?php
  $headerLoggedIn = isLoggedIn();
  $headerRole = $headerLoggedIn ? userRole() : null;
  $headerStaff = $headerLoggedIn && isStaff();
  $headerNickname = (string) ($_SESSION['user_r6_nickname'] ?? $_SESSION['user_name'] ?? 'Minha conta');
  $headerPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
  $headerLinks = ['/'=>'Início'];
  if ($headerLoggedIn && $headerRole !== 'staff') $headerLinks['/matchmaking'] = 'Matchmaking';
  $headerLinks['/ranking'] = 'Ranking';
  $headerLinks += ['/#como-funciona'=>'Como funciona', '/#tabela'=>'Tabela', '/#invitational'=>'Invitational'];
  $supportUnread = 0;
  if ($headerLoggedIn) {
      $supportCounter = $pdo->prepare("SELECT COUNT(*) FROM support_messages m JOIN support_tickets t ON t.id=m.ticket_id WHERE m.read_at IS NULL AND m.sender_type=?" . ($headerStaff ? '' : ' AND t.user_id=?'));
      $supportCounter->execute($headerStaff ? ['player'] : ['staff', userId()]);
      $supportUnread = (int) $supportCounter->fetchColumn();
  }
  ?>
  <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
  <header class="site-header">
    <div class="site-header-inner">
      <a href="/" class="site-logo" aria-label="InviSix Pro — início">
        <img src="/assets/images/logo-invisix.svg" class="w-full h-16 object-contain" alt="INVI SIX PRO" decoding="async" />
      </a>
      <details class="site-navigation" data-navigation open>
        <summary class="site-menu-toggle" aria-label="Menu de navegação"><?= faIcon('bars') ?><span>Menu</span></summary>
        <div class="site-navigation-content">
          <nav class="site-primary-nav" aria-label="Navegação principal">
            <?php foreach ($headerLinks as $href=>$label): ?>
              <a href="<?= e($href) ?>" <?= $headerPath===$href?'aria-current="page"':'' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
          </nav>
          <div class="site-account-nav">
            <?php if ($headerLoggedIn): ?>
              <a class="site-support-link" href="<?= $headerStaff?'/admin/support':'/support' ?>"><?= faIcon('shield-halved') ?><span>Suporte</span><?php if ($supportUnread): ?><span class="site-unread" aria-label="<?= $supportUnread ?> mensagens não lidas"><?= $supportUnread ?></span><?php endif; ?></a>
              <details class="site-profile-menu" data-profile-menu>
                <summary aria-expanded="false" aria-controls="site-profile-panel"><span class="site-account-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($headerNickname,0,1))) ?></span><span class="site-account-name"><?= e($headerNickname) ?></span><span class="site-chevron" aria-hidden="true"><?= faIcon('chevron-down', 'fa-sm') ?></span></summary>
                <div class="site-profile-panel" id="site-profile-panel">
                  <p class="site-profile-caption"><?= e($headerNickname) ?><span><?= $headerRole==='admin'?'Administrador':($headerRole==='staff'?'Staff':'Jogador') ?></span></p>
                  <a href="/perfil">Meu perfil</a>
                  <?php if ($headerRole !== 'staff'): ?><a href="/matchmaking">Matchmaking</a><?php endif; ?>
                  <a href="<?= $headerStaff?'/admin/punishments':'/punishments' ?>">Punições</a>
                  <?php if ($headerStaff): ?><a href="/admin">Administração</a><?php endif; ?>
                  <form action="/logout" method="POST"><?= csrfField() ?><button type="submit"><?= faIcon('right-from-bracket') ?> Sair</button></form>
                </div>
              </details>
            <?php else: ?>
              <a class="site-login-button" href="/login">Entrar <?= faIcon('arrow-right') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </details>
    </div>
  </header>

  <?php require_once __DIR__ . '/toasts.php'; ?>

  <main id="main-content" tabindex="-1">
<?php if(isStaff()&&str_starts_with($headerPath,'/admin'))require __DIR__.'/admin-navigation.php'; ?>

    <?php if (in_array($headerPath, ['/matchmaking', '/ranking', '/perfil'], true) || preg_match('#^/(?:match|partida)/[1-9][0-9]*$#', (string) $headerPath)): ?>
      <?php require __DIR__ . '/community.php'; ?>
    <?php endif; ?>
