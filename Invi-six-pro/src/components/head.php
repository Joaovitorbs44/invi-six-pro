<?php

  require_once __DIR__ . '/../database/connection.php';
  try {
    require_once __DIR__ . '/../config/session.php';
    [$pdo, $usersPdo] = applicationDatabases();
    require_once __DIR__ . '/../includes/lobby.php';
    reconcileLobby($pdo);
    require_once __DIR__ . '/../includes/seasons.php';
    reconcileSeasons($pdo);
  } catch (Throwable $exception) {
    error_log('Application database initialization failed: '.get_class($exception));
    http_response_code(503);
    exit('Não foi possível conectar aos bancos de dados.');
  }
  
  require_once __DIR__ . '/../includes/functions.php';
  require_once __DIR__ . '/../includes/csrf.php';
  require_once __DIR__ . '/../includes/auth.php';

  $pageTitle = $pageTitle ?? 'INVI-SIX PRO';

