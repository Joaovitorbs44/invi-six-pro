<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';
header('Content-Type: application/json; charset=UTF-8'); header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') { http_response_code(405); header('Allow: GET'); exit; }
if(!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Sessão expirada.']); exit; }
session_write_close();
require_once __DIR__.'/../includes/support.php';
try {
 $id=($_GET['ticket']??'0')==='0'?0:positiveId($_GET['ticket']); $actor=(int)userId(); $staff=isStaff()&&($_GET['view']??'')==='staff';
 $messages=[]; $ticket=null;
 if($id) { $ticket=supportTicket($pdo,$id,$actor,$staff); $messages=supportMessages($pdo,$id,$actor,$staff); }
 $list=supportList($pdo,$actor,$staff,$_GET);
 $summary=supportSummary($pdo,$actor,$staff);
 echo json_encode(['messages'=>$messages,'status'=>$ticket?$ticket['status']:null,'staff_name'=>$ticket['staff_name']??null,
  'unread'=>$summary['unread'],'pending'=>$summary['pending'],'categories'=>supportCategories(),'statuses'=>supportStatuses(),'tickets'=>array_map(static fn($t)=>['id'=>$t['id'],'unread'=>$t['unread'],'subject'=>$t['subject'],'owner_name'=>$t['owner_name'],'category'=>$t['category'],'status'=>$t['status'],'updated_at'=>$t['updated_at'],'last_message'=>mb_substr($t['last_message']??'',0,90)],$list)],JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
 if ($e instanceof PDOException) {
  error_log('Support snapshot database failure: SQLSTATE '.$e->getCode());
  http_response_code(503); echo json_encode(['error'=>'Não foi possível consultar o suporte. Tente novamente.']);
 } else { http_response_code(404); echo json_encode(['error'=>'Conversa indisponível.']); }
}
