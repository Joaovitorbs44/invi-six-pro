<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';
requireAuth();
if (!isPost()) { http_response_code(405); header('Allow: POST'); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Esta ação exige POST.']); exit; }
verifyCsrf();
require_once __DIR__.'/../includes/rate-limit.php';
enforceRateLimit('match:'.(int)userId(),120,60);
require_once __DIR__.'/../includes/matches.php';
$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
try {
 matchAction($pdo, (int) userId(), isStaff(), $_POST);
 $message = ['decline'=>'Partida rejeitada. Participantes liberados.','vote'=>'Voto registrado.','pick'=>'Jogador escolhido.','ban'=>'Mapa banido.','side'=>'Lado inicial definido.','ready'=>'Você está pronto.','report'=>'Resultado enviado.','confirm'=>'Resultado registrado.','dispute'=>'Resultado contestado.','resolve'=>'Resultado definido pela staff.','cancel'=>'Partida cancelada.'][(string)($_POST['action']??'')] ?? 'Partida atualizada.';
 if ($json) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'message'=>$message]); exit; }
 flash('success',$message);
} catch (Throwable $error) {
 $message = $error instanceof RuntimeException && !($error instanceof PDOException) ? $error->getMessage() : 'Não foi possível atualizar a partida.';
 if ($json) { http_response_code(in_array($error->getCode(),[403,404],true)?$error->getCode():409); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$message]); exit; }
 flash('warning',$message);
}
redirect('/partida/'.max(1,(int)($_POST['match_id']??0)));
