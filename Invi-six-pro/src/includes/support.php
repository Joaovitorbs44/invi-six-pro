<?php
declare(strict_types=1);
require_once __DIR__ . '/../database/connection.php';
function supportCategories(): array {
    return ['match'=>'Partidas e disputas','report'=>'Denúncia de jogador','access'=>'Problema de acesso',
        'ranking'=>'Ranking e temporadas','ban'=>'Punições','bug'=>'Bug da plataforma','other'=>'Outro'];
}
function supportStatuses(): array {
    return ['open'=>'Aberto','in_progress'=>'Em atendimento','waiting_player'=>'Respondido','resolved'=>'Resolvido','closed'=>'Fechado'];
}
function supportFilters(array $input): array {
    $filters=[];
    foreach(['q','status','category'] as $key) {
        $value=is_string($input[$key]??null)?trim($input[$key]):'';
        if($key==='q')$value=mb_substr($value,0,150);
        if($key==='status'&&!isset(supportStatuses()[$value]))$value='';
        if($key==='category'&&!isset(supportCategories()[$value]))$value='';
        if($value!=='')$filters[$key]=$value;
    }
    return $filters;
}
function supportTicket(PDO $pdo, int $id, int $actor, bool $staff): array {
    $query=$pdo->prepare('SELECT t.* FROM support_tickets t WHERE t.id=?');
    $query->execute([$id]); $ticket=$query->fetch(PDO::FETCH_ASSOC);
    if (!$ticket || (!$staff && (int)$ticket['user_id']!==$actor)) throw new RuntimeException('Conversa indisponível.');
    $names=databaseIdentities($pdo,[$ticket['user_id'],$ticket['assigned_staff_id']]);
    $ticket['owner_name']=$names[(int)$ticket['user_id']]['r6_nickname']??'Conta removida';
    $ticket['staff_name']=$names[(int)$ticket['assigned_staff_id']]['r6_nickname']??null;
    return $ticket;
}
function supportText(string $text, int $min, int $max, string $label): string {
    $text=trim($text);
    if (mb_strlen($text)<$min || mb_strlen($text)>$max || str_contains($text,"\0")) throw new RuntimeException("$label deve conter entre $min e $max caracteres.");
    return $text;
}
function supportAction(PDO $pdo, int $actor, bool $staff, array $input): int {
    beginWriteTransaction($pdo);
    try {
        validateDatabaseActor($pdo,$actor,$staff);
        $action=(string)($input['action']??'reply');
        if ($action==='create') {
            $category=(string)($input['category']??'');
            if (!isset(supportCategories()[$category])) throw new RuntimeException('Categoria inválida.');
            $subject=supportText((string)($input['subject']??''),3,150,'Assunto');
            $message=supportText((string)($input['message']??''),1,5000,'Mensagem');
            $limit=$pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id=? AND created_at>(CURRENT_TIMESTAMP - INTERVAL '1 hour')");
            $limit->execute([$actor]); if ((int)$limit->fetchColumn()>=5) throw new RuntimeException('Limite de novas conversas atingido. Tente mais tarde.');
            $matchId=($input['related_match_id']??'')!==''?positiveId($input['related_match_id']):null;
            if ($category!=='match') $matchId=null;
            if ($matchId) {
                $profile=playerForUser($pdo,$actor);
                $check=$pdo->prepare('SELECT 1 FROM match_players WHERE match_id=? AND player_id=?');
                $check->execute([$matchId,$profile['id']??0]); if (!$check->fetchColumn()) throw new RuntimeException('Partida inválida.');
            }
            $related=$category==='report'?supportText((string)($input['related_nickname']??''),0,50,'Nick relacionado'):null;
            $relatedId=null;
            if ($related) { $q=accountsDatabase($pdo)->prepare('SELECT id FROM users WHERE r6_nickname=?'); $q->execute([$related]); $relatedId=$q->fetchColumn()?:null; }
            $q=$pdo->prepare('INSERT INTO support_tickets(user_id,category,subject,related_match_id,related_user_id,related_nickname) VALUES(?,?,?,?,?,?)');
            $q->execute([$actor,$category,$subject,$matchId,$relatedId,$related]); $id=(int)$pdo->query('SELECT lastval()')->fetchColumn();
            $q=$pdo->prepare("INSERT INTO support_messages(ticket_id,sender_id,sender_type,message) VALUES(?,?,?,?)"); $q->execute([$id,$actor,$staff?'staff':'player',$message]);
        } else {
            $id=positiveId($input['ticket_id']??0); $ticket=supportTicket($pdo,$id,$actor,$staff);
            if ($action==='reply') {
                if (in_array($ticket['status'],['resolved','closed'],true)) throw new RuntimeException('Reabra a conversa antes de responder.');
                $message=supportText((string)($input['message']??''),1,5000,'Mensagem');
                $q=$pdo->prepare("SELECT COUNT(*) FROM support_messages WHERE sender_id=? AND created_at>(CURRENT_TIMESTAMP - INTERVAL '1 minute')");
                $q->execute([$actor]); if ((int)$q->fetchColumn()>=20) throw new RuntimeException('Aguarde antes de enviar novas mensagens.');
                $q=$pdo->prepare('INSERT INTO support_messages(ticket_id,sender_id,sender_type,message) VALUES(?,?,?,?)'); $q->execute([$id,$actor,$staff?'staff':'player',$message]);
                $q=$pdo->prepare('UPDATE support_tickets SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?'); $q->execute([$staff?'waiting_player':'open',$id]);
            } elseif ($staff && $action==='assign') {
                $q=$pdo->prepare("UPDATE support_tickets SET assigned_staff_id=?,status='in_progress',closed_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?"); $q->execute([$actor,$id]);
            } elseif ($staff && $action==='status') {
                $status=(string)($input['status']??''); if (!isset(supportStatuses()[$status])) throw new RuntimeException('Status inválido.');
                $q=$pdo->prepare("UPDATE support_tickets SET status=?,closed_at=CASE WHEN ? IN ('resolved','closed') THEN CURRENT_TIMESTAMP ELSE NULL END,updated_at=CURRENT_TIMESTAMP WHERE id=?"); $q->execute([$status,$status,$id]);
            } else throw new RuntimeException('Ação indisponível.');
        }
        $q=$pdo->prepare('INSERT INTO logs(type,description,user_id,additional_data) VALUES(?,?,?,?)');
        $q->execute(['support_'.$action,'Conversa de suporte atualizada.',$actor,json_encode(['ticket_id'=>$id])]);
        $pdo->exec('COMMIT'); return $id;
    } catch (Throwable $e) { $pdo->exec('ROLLBACK'); throw $e; }
}
function supportMessages(PDO $pdo, int $id, int $actor, bool $staff, bool $read=true): array {
    supportTicket($pdo,$id,$actor,$staff);
    $q=$pdo->prepare('SELECT m.id,m.message,m.sender_id,m.sender_type,m.created_at FROM support_messages m WHERE m.ticket_id=? ORDER BY m.id');
    $q->execute([$id]); $rows=$q->fetchAll(PDO::FETCH_ASSOC);
    $names=databaseIdentities($pdo,array_column($rows,'sender_id'));
    foreach($rows as &$row){$row['sender_name']=$names[(int)$row['sender_id']]['r6_nickname']??'Conta removida';unset($row['sender_id']);}unset($row);
    if ($read && $rows) {
        $last=(int)end($rows)['id'];
        $q=$pdo->prepare('UPDATE support_messages SET read_at=CURRENT_TIMESTAMP WHERE ticket_id=? AND sender_type=? AND read_at IS NULL AND id<=?'); $q->execute([$id,$staff?'player':'staff',$last]);
    }
    return $rows;
}
function supportList(PDO $pdo,int $actor,bool $staff,array $filters=[]): array {
    $filters=supportFilters($filters);
    $where=$staff?'1=1':'t.user_id=:actor'; $params=$staff?[]:[':actor'=>$actor];
    foreach (['status'=>supportStatuses(),'category'=>supportCategories()] as $key=>$allowed) {
        if (isset($allowed[$filters[$key]??''])) { $where.=" AND t.$key=:$key"; $params[":$key"]=$filters[$key]; }
    }
    if (($search=trim((string)($filters['q']??'')))!=='') {
        $lookup=accountsDatabase($pdo)->prepare('SELECT id FROM users WHERE r6_nickname LIKE ?');
        $lookup->execute(['%'.mb_substr($search,0,150).'%']);$owners=array_map('intval',$lookup->fetchAll(PDO::FETCH_COLUMN));
        $where.=' AND (t.subject LIKE :q OR CAST(t.id AS TEXT)=:number'.($owners?' OR t.user_id IN('.implode(',',$owners).')':'').')'; $params[':q']='%'.mb_substr($search,0,150).'%'; $params[':number']=ltrim($search,'#');
    }
    $sql="SELECT t.*,(SELECT message FROM support_messages WHERE ticket_id=t.id ORDER BY id DESC LIMIT 1) last_message,
        (SELECT COUNT(*) FROM support_messages WHERE ticket_id=t.id AND sender_type='".($staff?'player':'staff')."' AND read_at IS NULL) unread
        FROM support_tickets t WHERE $where ORDER BY t.updated_at DESC,t.id DESC LIMIT 100";
    $q=$pdo->prepare($sql); $q->execute($params); $rows=$q->fetchAll(PDO::FETCH_ASSOC);
    $names=databaseIdentities($pdo,array_column($rows,'user_id'));
    foreach($rows as &$row)$row['owner_name']=$names[(int)$row['user_id']]['r6_nickname']??'Conta removida';
    return $rows;
}
function supportSummary(PDO $pdo,int $actor,bool $staff): array {
    $q=$pdo->prepare("SELECT COUNT(*) pending FROM support_tickets WHERE status IN('open','in_progress','waiting_player')".($staff?'':' AND user_id=?'));
    $q->execute($staff?[]:[$actor]);$pending=(int)$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*) FROM support_messages m JOIN support_tickets t ON t.id=m.ticket_id WHERE m.read_at IS NULL AND m.sender_type=?'.($staff?'':' AND t.user_id=?'));
    $q->execute($staff?['player']:['staff',$actor]);return ['pending'=>$pending,'unread'=>(int)$q->fetchColumn()];
}
