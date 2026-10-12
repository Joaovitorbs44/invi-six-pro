<?php

  require_once __DIR__ . '/../database/connection.php';
  try {
    $initializationStart = hrtime(true);
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $anonymousPublicPage = !isset($_COOKIE['invisix_session'])
      && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
      && in_array($requestPath, ['/', '/ranking'], true);
    if ($anonymousPublicPage) $_SESSION = [];
    else require_once __DIR__ . '/../config/session.php';
    $sessionReady = hrtime(true);
    [$pdo, $usersPdo] = applicationDatabases();
    $databaseReady = hrtime(true);
    if (!in_array($requestPath, ['/login', '/register', '/logout', '/primeiro-acesso/senha', '/solicitacao', '/solicitacao/status'], true)) {
      require_once __DIR__ . '/../includes/lobby.php';
      reconcileLobby($pdo);
      require_once __DIR__ . '/../includes/seasons.php';
      reconcileSeasons($pdo);
    }
    header(sprintf('Server-Timing: session;dur=%.2f, db;dur=%.2f, reconcile;dur=%.2f',
      ($sessionReady - $initializationStart) / 1e6,
      ($databaseReady - $sessionReady) / 1e6,
      (hrtime(true) - $databaseReady) / 1e6));
  } catch (Throwable $exception) {
    $cause = $exception->getPrevious() ?? $exception;
    error_log('Application database initialization failed: '.get_class($cause).' code='.$cause->getCode());
    http_response_code(503);
    exit('Não foi possível conectar aos bancos de dados.');
  }
  
  require_once __DIR__ . '/../includes/functions.php';
  require_once __DIR__ . '/../includes/csrf.php';
  require_once __DIR__ . '/../includes/auth.php';

  $pageTitle = $pageTitle ?? 'INVI-SIX PRO';

