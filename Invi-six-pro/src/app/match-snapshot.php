<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';
header('Content-Type: application/json'); header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
if (!isLoggedIn()) { http_response_code(401); echo json_encode(['message'=>'Sua sessão expirou. Entre novamente.']); exit; }
csrfToken();
session_write_close();
require_once __DIR__.'/../includes/matches.php';
try {
 $matchData=matchDetails($pdo,positiveId($_GET['match_id']??0),(int)userId(),isStaff());
 ob_start(); require __DIR__.'/../components/match-board.php'; $html=ob_get_clean();
 echo json_encode(['html'=>$html,'version'=>(int)$matchData['match']['version'],'status'=>$matchData['match']['status'],'captain'=>(bool)($matchData['me']['is_captain']??false),'turn'=>$matchData['turn'],'team'=>$matchData['me']['team']??null,'event'=>end($matchData['events'])['description']??null],JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
 if ($error instanceof PDOException) {
  error_log('Match snapshot database failure: SQLSTATE '.$error->getCode());
  http_response_code(503); echo json_encode(['message'=>'Não foi possível consultar a partida. Tente novamente.']);
 } else { http_response_code(404); echo json_encode(['message'=>'Partida indisponível.']); }
}
