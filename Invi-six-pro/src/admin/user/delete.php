<?php
declare(strict_types=1);
require_once __DIR__ . '/../../components/head.php';
require_once __DIR__ . '/../../includes/admin-removal.php';
requireAdmin();
if (!isPost()) { http_response_code(405); header('Allow: POST'); exit('Método não permitido.'); }
verifyCsrf();
$targetId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
if (!$targetId || $targetId < 1) { flash('error', 'Jogador inválido.'); redirect('/admin'); }
try {
    deletePlayerAccount($pdo, $targetId, (int) userId());
    flash('success', 'Jogador removido com sucesso.');
} catch (Throwable $exception) {
    flash('error', $exception instanceof RuntimeException && !($exception instanceof PDOException)
        ? $exception->getMessage() : 'Não foi possível remover o jogador.');
}
redirect('/admin');
