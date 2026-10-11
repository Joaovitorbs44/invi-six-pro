<?php
declare(strict_types=1);
require_once __DIR__ . '/../database/connection.php';
function competitiveRestriction(PDO $pdo,int $userId): bool {
 $q=$pdo->prepare("SELECT 1 FROM punishments WHERE user_id=? AND type IN ('suspension','ban') AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at>CURRENT_TIMESTAMP) LIMIT 1");
 $q->execute([$userId]); return (bool)$q->fetchColumn();
}
function punishmentAction(PDO $pdo,int $staff,array $input): void {
 beginWriteTransaction($pdo);
 try{
  validateDatabaseActor($pdo,$staff,true);
  if(($input['action']??'create')==='revoke'){
   $q=$pdo->prepare('UPDATE punishments SET revoked_at=CURRENT_TIMESTAMP,revoked_by=? WHERE id=? AND revoked_at IS NULL');$q->execute([$staff,positiveId($input['punishment_id']??0)]);
   if($q->rowCount()!==1)throw new RuntimeException('Punição indisponível.');
  }elseif(($input['action']??'create')==='create'){
   $target=positiveId($input['user_id']??0);$type=(string)($input['type']??'');
   $q=accountsDatabase($pdo)->prepare("SELECT id FROM users WHERE id=? AND role='player'");$q->execute([$target]);
   if(!$q->fetchColumn()||!in_array($type,['warning','suspension','ban'],true))throw new RuntimeException('Jogador ou tipo inválido.');
   $reason=trim((string)($input['reason']??''));$notes=trim((string)($input['internal_notes']??''));
   if(mb_strlen($reason)<3||mb_strlen($reason)>1000||mb_strlen($notes)>3000)throw new RuntimeException('Informe um motivo entre 3 e 1000 caracteres.');
   $hours=filter_var($input['hours']??'',FILTER_VALIDATE_INT);$permanent=($input['permanent']??'')==='1'||$type==='warning';
   if(!$permanent&&(!$hours||$hours<1||$hours>87600))throw new RuntimeException('Informe uma duração válida em horas.');
   $expires=$permanent?null:gmdate('Y-m-d H:i:s',time()+$hours*3600);
   $q=$pdo->prepare('INSERT INTO punishments(user_id,type,reason,internal_notes,staff_user_id,expires_at) VALUES(?,?,?,?,?,?)');$q->execute([$target,$type,$reason,$notes,$staff,$expires]);
   if($type!=='warning'&&($profile=playerForUser($pdo,$target)))$pdo->prepare('DELETE FROM matchmaking_queue WHERE player_id=?')->execute([$profile['id']]);
  }else throw new RuntimeException('Ação indisponível.');
  $pdo->prepare('INSERT INTO logs(type,description,staff_user_id) VALUES(?,?,?)')->execute(['punishment_updated','Punição atualizada.',$staff]);
  $pdo->exec('COMMIT');
 }catch(Throwable $e){$pdo->exec('ROLLBACK');throw $e;}
}

function punishmentStatus(array $row): string
{
    if ($row['revoked_at'] || ($row['expires_at'] && $row['expires_at']<=gmdate('Y-m-d H:i:s'))) return 'Encerrada';
    return !$row['expires_at'] && $row['type']!=='warning' ? 'Permanente' : 'Ativa';
}
function punishmentHistory(PDO $pdo, ?int $user=null, string $search=''): array
{
    $params=[]; $where='1=1';
    if ($user !== null) { $where='user_id=?'; $params[]=$user; }
    elseif ($search!=='') {
        $q=accountsDatabase($pdo)->prepare('SELECT id FROM users WHERE r6_nickname LIKE ? OR CAST(id AS TEXT)=?');
        $q->execute(['%'.$search.'%',ltrim($search,'#')]);
        $ids=$q->fetchAll(PDO::FETCH_COLUMN);
        if (!$ids) return [];
        $where='user_id IN('.implode(',',array_fill(0,count($ids),'?')).')';$params=$ids;
    }
    // Player queries explicitly exclude internal notes.
    $fields=$user===null?'*':'id,type,reason,staff_user_id,created_at,expires_at,revoked_at';
    $q=$pdo->prepare('SELECT '.$fields.' FROM punishments WHERE '.$where.' ORDER BY id DESC'.($user===null?' LIMIT 100':''));
    $q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
    $ids=array_merge(array_column($rows,'user_id'),array_column($rows,'staff_user_id'));
    $names=databaseIdentities($pdo,$ids);
    foreach ($rows as &$row) { $row['nickname']=$names[(int)($row['user_id']??0)]['r6_nickname']??'Conta removida'; $row['staff_name']=$names[(int)$row['staff_user_id']]['r6_nickname']??'Conta removida'; }
    return $rows;
}
