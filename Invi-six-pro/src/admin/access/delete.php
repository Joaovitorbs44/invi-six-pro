<?php
declare(strict_types=1);
require_once __DIR__ . '/../../components/head.php';
require_once __DIR__ . '/../../includes/admin-removal.php';
requireAdmin();
if (!isPost()) { http_response_code(405); header('Allow: POST'); exit('Método não permitido.'); }
verifyCsrf();
$requestId = filter_var($_POST['request_id'] ?? '', FILTER_VALIDATE_INT);
if (!$requestId || $requestId < 1) { flash('error', 'Solicitação inválida.'); redirect('/admin'); }
try {
    deleteAccessRequest($pdo, $requestId, (int) userId());
    flash('success', 'Solicitação excluída com sucesso.');
} catch (Throwable $exception) {
    flash('error', 'Não foi possível excluir esta solicitação.');
}
redirect('/admin');
