<?php

  declare(strict_types=1);
  require_once __DIR__ . '/dates.php';
  require_once __DIR__ . '/security.php';
  require_once __DIR__ . '/match-presentation.php';

  function assetUrl(string $path): string {
    static $versions = [];
    if (!preg_match('~\A/assets/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_.-]+\.(?:css|js)\z~', $path)) {
      throw new InvalidArgumentException('Caminho de asset inválido.');
    }
    if (!isset($versions[$path])) {
      // Vercel serves built assets from the CDN; they need not be in the PHP bundle.
      $deployment = getenv('VERCEL_DEPLOYMENT_ID') ?: getenv('VERCEL_GIT_COMMIT_SHA');
      if ($deployment) $hash = hash('sha256', $deployment);
      else {
        $file = dirname(__DIR__, 2) . '/public' . $path;
        $hash = is_file($file) ? hash_file('sha256', $file) : false;
      }
      if ($hash === false) throw new RuntimeException('Asset indisponível.');
      $versions[$path] = substr($hash, 0, 12);
    }
    return $path . '?v=' . $versions[$path];
  }

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

