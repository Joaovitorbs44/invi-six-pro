<?php

  declare(strict_types=1);
  require_once __DIR__ . '/dates.php';
  require_once __DIR__ . '/security.php';
  require_once __DIR__ . '/match-presentation.php';

  function e(mixed $value): string {
    return htmlspecialchars(
      (string) ($value ?? ''),
      ENT_QUOTES | ENT_SUBSTITUTE,
      'UTF-8'
    );
  }

  function redirect(string $path): never {
    if (!str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('/[\x00-\x20\\\\]/', $path)) {
      throw new InvalidArgumentException('Redirecionamento inválido.');
    }
    header('Location: ' . $path, true, 303);
    exit;
  }

  function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
  }

  function old(string $key, string $default = ''): string {
    return e($_SESSION['old'][$key] ?? $default);
  }

  function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
      $_SESSION['flash'][$key] = $message;
      return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
      return null;
    }

    $flashMessage = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $flashMessage;
  }

  function clearOldInput(): void {
    unset($_SESSION['old']);
  }

