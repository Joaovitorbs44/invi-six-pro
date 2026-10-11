<?php

  declare(strict_types=1);

  function csrfToken(): string {
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
      );
    }

    return $_SESSION['csrf_token'];
  }

  function csrfField(): string {
    return sprintf(
      '<input type="hidden" name="_token" value="%s">',
      e(csrfToken())
    );
  }

  function verifyCsrf(): void {
    $token = $_POST['_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
      http_response_code(419);
      if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'code'=>'csrf_expired','message'=>'O token de segurança expirou. Atualize a página e tente novamente.']);
        exit;
      }
      exit('Sessão expirada. Atualize a página e tente novamente.');
    }
  }

