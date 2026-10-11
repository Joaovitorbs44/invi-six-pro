<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';
requireAuth();
require_once __DIR__.'/../includes/support.php';
if(!isPost()) { http_response_code(405); header('Allow: POST'); exit; }
verifyCsrf();
require_once __DIR__.'/../includes/rate-limit.php';
enforceRateLimit('support:'.(int)userId(),30,60);
$json=($_SERVER['HTTP_ACCEPT']??'')==='application/json';
try {
 $id=supportAction($pdo,(int)userId(),isStaff(),$_POST);
 $message=($_POST['action']??'')==='create'?'Ticket criado.':'Conversa atualizada.';
 if($json) { header('Content-Type: application/json'); echo json_encode(['ticket_id'=>$id,'message'=>$message]); exit; }
 flash('success',$message); redirect((isStaff()?'/admin/support':'/support').'?ticket='.$id);
} catch(Throwable $e) {
 $message=$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Não foi possível atualizar a conversa.';
 if($json) { http_response_code(422); header('Content-Type: application/json'); echo json_encode(['error'=>$message]); exit; }
 flash('error',$message); redirect(isStaff()?'/admin/support':'/support');
}
