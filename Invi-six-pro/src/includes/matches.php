<?php
declare(strict_types=1);
require_once __DIR__.'/matchmaking.php';
require_once __DIR__.'/punishments.php';
function matchEvent(PDO $pdo,int $match,string $type,string $description,?int $user=null): void {
 $pdo->prepare('INSERT INTO logs(match_id,type,description,user_id) VALUES(?,?,?,?)')->execute([$match,$type,$description,$user]);
}
function createLobbyMatch(PDO $pdo): ?int {
 $season=$pdo->query("SELECT id FROM seasons WHERE status='active' AND starts_at<=CURRENT_TIMESTAMP AND ends_at>CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1")->fetchColumn();
 if(!$season)return null;
 if(lobbyFormationBusy($pdo))return null;
 $rows=competitiveQueue($pdo,(int)$season);
 $target=competitiveQueueTarget();
 if(count($rows)<$target)return null;
 $rows=array_slice($rows,0,$target);
 foreach($rows as $row){
  if(competitiveRestriction($pdo,(int)$row['user_id']))throw new RuntimeException('Um jogador da fila possui restrição competitiva.');
  $q=$pdo->prepare("SELECT 1 FROM match_players mp JOIN matches m ON m.id=mp.match_id WHERE mp.player_id=? AND m.status NOT IN('finished','cancelled')");$q->execute([$row['player_id']]);if($q->fetchColumn())throw new RuntimeException('Fila contém um jogador em partida.');
 }
 if((int)$pdo->query('SELECT COUNT(*) FROM maps WHERE active=1')->fetchColumn()<2)throw new RuntimeException('O map pool precisa de pelo menos dois mapas.');
 $pdo->prepare("INSERT INTO matches(match_code,season_id,status,draft_bans_required,ready_deadline) VALUES(?,?,'ready_check',0,CURRENT_TIMESTAMP + INTERVAL '60 seconds')")->execute(['IST-'.strtoupper(bin2hex(random_bytes(6))),$season]);$id=(int)$pdo->query('SELECT lastval()')->fetchColumn();
 foreach($rows as $row){
  $pdo->prepare('INSERT INTO match_players(match_id,player_id) VALUES(?,?)')->execute([$id,$row['player_id']]);
  $pdo->prepare('INSERT INTO logs(match_id,type,description,user_id,created_at) VALUES(?,?,?,?,?)')->execute([$id,'queue_joined',$row['r6_nickname'].' entrou na fila.',(int)$row['user_id'],$row['joined_at']]);
 }
 $pdo->prepare('INSERT INTO match_map_pool(match_id,map_id) SELECT ?,id FROM maps WHERE active=1')->execute([$id]);
 $pdo->exec('DELETE FROM matchmaking_queue WHERE player_id IN('.implode(',',array_map('intval',array_column($rows,'player_id'))).')');
 matchEvent($pdo,$id,'lobby_completed','Lobby completo. Ready check de 60 segundos iniciado.');return $id;
}
function defineMatchCaptains(PDO $pdo,int $id): void {
 $preference='';
 $q=$pdo->prepare('SELECT mp.player_id FROM match_players mp LEFT JOIN captain_votes cv ON cv.match_id=mp.match_id AND cv.voted_player_id=mp.player_id WHERE mp.match_id=? GROUP BY mp.id ORDER BY '.$preference.'COUNT(cv.id) DESC,mp.id ASC LIMIT 2');$q->execute([$id]);$captains=$q->fetchAll(PDO::FETCH_ASSOC);
 $identities=databaseIdentities($pdo,array_column($captains,'player_id'),true);
 foreach($captains as $index=>$captain){$team=$index===0?'A':'B';$pdo->prepare('UPDATE match_players SET is_captain=1,team=? WHERE match_id=? AND player_id=?')->execute([$team,$id,$captain['player_id']]);matchEvent($pdo,$id,'captain_defined',($identities[(int)$captain['player_id']]['r6_nickname']??'Conta removida').' definido como capitao do Time '.$team.'.');}
 $pdo->prepare("UPDATE matches SET status='draft' WHERE id=?")->execute([$id]);
}
function matchMapCatalog(): array {
 static $catalog;return $catalog??=require __DIR__.'/../config/maps.php';
}
function matchMapLabel(string $name): string {
 return matchMapCatalog()[$name]['label']??$name;
}
function matchMapImage(string $name): ?string {
 $map=matchMapCatalog()[$name]??null;
 if(!$map)return null;
 $path=$map['image']??null;
 return $path && is_file(dirname(__DIR__, 2).'/public'.$path) ? $path : null;
}
// Exhaustively check the at most eight unassigned players before committing a ban or pick.
function draftAssignmentPossible(array $players,array $bans): bool {
 $free=[];$need=['A'=>5,'B'=>5];$blocked=['A'=>[],'B'=>[]];
 foreach($bans as $ban)$blocked[$ban['team']][(int)$ban['player_id']]=true;
 foreach($players as $p){if($p['team']){if(isset($blocked[$p['team']][(int)$p['player_id']]))return false;$need[$p['team']]--;}else $free[]=(int)$p['player_id'];}
 if($need['A']<0||$need['B']<0||count($free)!==$need['A']+$need['B'])return false;
 for($mask=0;$mask<(1<<count($free));$mask++){$a=0;$valid=true;foreach($free as $i=>$id){$team=($mask&(1<<$i))?'A':'B';if($team==='A')$a++;if(isset($blocked[$team][$id])){$valid=false;break;}}if($valid&&$a===$need['A'])return true;}return false;
}
function matchDetails(PDO $pdo,int $id,int $userId,bool $staff=false): array {
 $q=$pdo->prepare('SELECT m.*,s.name season_name FROM matches m JOIN seasons s ON s.id=m.season_id WHERE m.id=?');$q->execute([$id]);$match=$q->fetch(PDO::FETCH_ASSOC);if(!$match)throw new RuntimeException('Partida indisponível.',404);
 $q=$pdo->prepare('SELECT mp.*,COALESCE(ps.points,0) points FROM match_players mp LEFT JOIN player_stats ps ON ps.player_id=mp.player_id AND ps.season_id=? WHERE mp.match_id=? ORDER BY mp.is_captain DESC,mp.pick_order,mp.id');$q->execute([$match['season_id'],$id]);$players=$q->fetchAll(PDO::FETCH_ASSOC);
 $identities=databaseIdentities($pdo,array_column($players,'player_id'),true);
 $me=null;foreach($players as &$player){$identity=$identities[(int)$player['player_id']]??[];$player+=['user_id'=>$identity['user_id']??null,'r6_nickname'=>$identity['r6_nickname']??null,'r6_rank'=>$identity['r6_rank']??null];$player['rank_image']=matchmakingRankImage($player['r6_rank']);if((int)$player['user_id']===$userId)$me=$player;}unset($player);
 if(!$staff&&!$me)throw new RuntimeException('Partida indisponível.',404);
 $q=$pdo->prepare('SELECT maps.*,v.team banned_team,v.banned_by_player_id FROM match_map_pool pool JOIN maps ON maps.id=pool.map_id LEFT JOIN map_veto v ON v.match_id=pool.match_id AND v.map_id=maps.id WHERE pool.match_id=? ORDER BY maps.sort_order,maps.id');$q->execute([$id]);$maps=$q->fetchAll(PDO::FETCH_ASSOC);$banners=databaseIdentities($pdo,array_column($maps,'banned_by_player_id'),true);foreach($maps as &$map){$map['image']=matchMapImage($map['name']);$map['banned_by']=$banners[(int)$map['banned_by_player_id']]['r6_nickname']??null;}unset($map);
 // Legacy matches without a pool snapshot remain viewable, without inventing bans.
 $q=$pdo->prepare('SELECT type,description,created_at FROM logs WHERE match_id=? ORDER BY id');$q->execute([$id]);$events=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare('SELECT COUNT(*) FROM captain_votes WHERE match_id=?');$q->execute([$id]);$votes=(int)$q->fetchColumn();
 $q=$pdo->prepare('SELECT COUNT(*) FROM draft_picks WHERE match_id=?');$q->execute([$id]);$picks=(int)$q->fetchColumn();
 $q=$pdo->prepare('SELECT pick_number,team,selected_player_id,created_at FROM draft_picks WHERE match_id=? ORDER BY pick_number');$q->execute([$id]);$draftHistory=$q->fetchAll(PDO::FETCH_ASSOC);
 $draftIdentities=databaseIdentities($pdo,array_column($draftHistory,'selected_player_id'),true);
 foreach($draftHistory as &$pick)$pick['r6_nickname']=$draftIdentities[(int)$pick['selected_player_id']]['r6_nickname']??'Conta removida';unset($pick);
 $q=$pdo->prepare('SELECT COUNT(*) FROM map_veto WHERE match_id=?');$q->execute([$id]);$bans=(int)$q->fetchColumn();
 $q=$pdo->prepare('SELECT v.veto_order,v.team,v.created_at,maps.name FROM map_veto v JOIN maps ON maps.id=v.map_id WHERE v.match_id=? ORDER BY v.veto_order');$q->execute([$id]);$vetoHistory=$q->fetchAll(PDO::FETCH_ASSOC);
 // Captains are the first A and B slots in the 1-2-2-2-2-1 sequence.
 $pickTeams=['B','A','A','B','B','A','A','B'];
 $voted=false;if($me){$q=$pdo->prepare('SELECT 1 FROM captain_votes WHERE match_id=? AND voter_player_id=?');$q->execute([$id,$me['player_id']]);$voted=(bool)$q->fetchColumn();}
 return ['match'=>$match,'players'=>$players,'me'=>$me,'maps'=>$maps,'veto_history'=>$vetoHistory,'draft_history'=>$draftHistory,'events'=>$events,'votes'=>$votes,'picks'=>$picks,'bans'=>$bans,'voted'=>$voted,
  'turn'=>$match['status']==='draft'?($pickTeams[$picks]??null):($match['status']==='veto'?($bans%2===0?'A':'B'):null)];
}
function applyMatchResult(PDO $pdo,array $match,string $winner): void {
 if(($match['status']??'')==='cancelled')throw new RuntimeException('Partida cancelada não aceita resultado.');
 $seasonState=$pdo->prepare('SELECT status FROM seasons WHERE id=?');$seasonState->execute([$match['season_id']]);
 if($seasonState->fetchColumn()!=='active')throw new RuntimeException('A temporada está encerrada; sua classificação não pode ser alterada.');

 if(!in_array($winner,['A','B'],true)||$match['points_applied'])throw new RuntimeException('Resultado indisponível.');
 $q=$pdo->prepare('SELECT player_id,team,is_simulated FROM match_players WHERE match_id=?');$q->execute([$match['id']]);$players=$q->fetchAll(PDO::FETCH_ASSOC);
 if(count($players)!==10)throw new RuntimeException('Partida incompleta. Encaminhe para a staff.');
 if(count(array_filter($players,fn($p)=>$p['team']==='A'))!==5||count(array_filter($players,fn($p)=>$p['team']==='B'))!==5||array_filter($players,fn($p)=>!empty($p['is_simulated'])))throw new RuntimeException('Resultado oficial exige duas equipes de cinco jogadores reais.');
 foreach($players as $p){if(!$p['player_id']||!in_array($p['team'],['A','B'],true))throw new RuntimeException('Partida incompleta. Encaminhe para a staff.');
  $won=$p['team']===$winner;$delta=$won?5:-3;
  $pdo->prepare('INSERT INTO player_stats(player_id,season_id,points,wins,losses,matches_played) VALUES(?,?,?,?,?,1) ON CONFLICT(player_id,season_id) DO UPDATE SET points=player_stats.points+excluded.points,wins=player_stats.wins+excluded.wins,losses=player_stats.losses+excluded.losses,matches_played=player_stats.matches_played+1')->execute([$p['player_id'],$match['season_id'],$delta,$won?1:0,$won?0:1]);
 }
 $pdo->prepare("UPDATE matches SET status='finished',winner_team=?,points_applied=1,finished_at=CURRENT_TIMESTAMP WHERE id=? AND points_applied=0")->execute([$winner,$match['id']]);
 matchEvent($pdo,(int)$match['id'],'match_finished','Partida encerrada. Time '.$winner.' venceu (+5 / −3).');
}
function matchAction(PDO $pdo,int $actor,bool $staff,array $input): void {
 beginWriteTransaction($pdo);
 try{
  validateDatabaseActor($pdo,$actor,$staff);
  $id=positiveId($input['match_id']??0);$data=matchDetails($pdo,$id,$actor,$staff);$m=$data['match'];$me=$data['me'];$action=(string)($input['action']??'');
  if($m['status']==='ready_check' && $m['ready_deadline']<=gmdate('Y-m-d H:i:s')) {
   expireReadyChecksInTransaction($pdo);$pdo->exec('COMMIT');$transactionCommitted=true;
   throw new RuntimeException('O prazo de confirmacao expirou. A partida foi cancelada; entre novamente na fila.');
  }
  if(!in_array($action,['vote','ready'],true)&&(!isset($input['version'])||(int)$input['version']!==(int)$m['version']))throw new RuntimeException('A partida mudou. Atualize e tente novamente.');
  if(!$me&&!($staff&&in_array($action,['resolve','cancel'],true)))throw new RuntimeException('Você não participa desta partida.',403);
  if($action==='vote'){
   if($m['status']!=='captain_vote')throw new RuntimeException('Votação encerrada.');
   $candidate=positiveId($input['player_id']??0);$found=false;foreach($data['players'] as $p)if((int)$p['player_id']===$candidate)$found=true;
   if(!$found)throw new RuntimeException('Candidato inválido.');
   $q=$pdo->prepare('INSERT INTO captain_votes(match_id,voter_player_id,voted_player_id) VALUES(?,?,?) ON CONFLICT(match_id,voter_player_id) DO NOTHING');$q->execute([$id,$me['player_id'],$candidate]);if($q->rowCount()!==1)throw new RuntimeException('Você já votou.');
   if($data['votes']+1===10)defineMatchCaptains($pdo,$id);
  }elseif($action==='pick'){
   if($m['status']!=='draft'||!$me['is_captain']||$me['team']!==$data['turn'])throw new RuntimeException('Não é sua vez de escolher.');
   $target=positiveId($input['player_id']??0);$q=$pdo->prepare('SELECT mp.player_id FROM match_players mp WHERE mp.match_id=? AND mp.player_id=? AND mp.team IS NULL');$q->execute([$id,$target]);$available=$q->fetchColumn();$name=$available?(databaseIdentities($pdo,[(int)$available],true)[(int)$available]['r6_nickname']??null):null;if(!$name)throw new RuntimeException('Jogador indisponível.');
   $q=$pdo->prepare('SELECT COUNT(*) FROM match_players WHERE match_id=? AND team=?');$q->execute([$id,$me['team']]);if((int)$q->fetchColumn()>=5)throw new RuntimeException('Time completo.');
   $proposed=$data['players'];foreach($proposed as &$p)if((int)$p['player_id']===$target)$p['team']=$me['team'];unset($p);
   if(!draftAssignmentPossible($proposed,[]))throw new RuntimeException('A escolha impediria completar dois times de cinco.');
   $order=$data['picks']+1;$pdo->prepare('INSERT INTO draft_picks(match_id,captain_player_id,selected_player_id,team,pick_number) VALUES(?,?,?,?,?)')->execute([$id,$me['player_id'],$target,$me['team'],$order]);
   $pdo->prepare('UPDATE match_players SET team=?,pick_order=? WHERE match_id=? AND player_id=? AND team IS NULL')->execute([$me['team'],$order,$id,$target]);
   matchEvent($pdo,$id,'draft_pick',$me['r6_nickname'].' escolheu '.$name.'.',$actor);
   if($order===8)$pdo->prepare("UPDATE matches SET status='veto' WHERE id=?")->execute([$id]);
  }elseif($action==='ban'){
   if($m['status']!=='veto'||!$me['is_captain']||$me['team']!==$data['turn'])throw new RuntimeException('Não é sua vez de banir.');
   $target=positiveId($input['map_id']??0);$selected=null;foreach($data['maps'] as $map)if((int)$map['id']===$target&&!$map['banned_team'])$selected=$map;
   if(!$selected)throw new RuntimeException('Mapa indisponível.');
   $pdo->prepare('INSERT INTO map_veto(match_id,map_id,banned_by_player_id,team,veto_order) VALUES(?,?,?,?,?)')->execute([$id,$target,$me['player_id'],$me['team'],$data['bans']+1]);
   matchEvent($pdo,$id,'map_banned','Time '.$me['team'].' · '.$me['r6_nickname'].' baniu '.$selected['name'].'.',$actor);
   if($data['bans']+1===count($data['maps'])-1){
    foreach($data['maps'] as $map)if(!$map['banned_team']&&(int)$map['id']!==$target)$final=$map['name'];
    $pdo->prepare("UPDATE matches SET status='side_selection',map_name=? WHERE id=?")->execute([$final,$id]);
    matchEvent($pdo,$id,'map_defined','Mapa definido: '.$final.'. Confirme sua presença.');
   }
  }elseif($action==='side'){
   if($m['status']!=='side_selection'||!$me['is_captain']||$me['team']!=='A'||$m['team_a_side']!==null)throw new RuntimeException('Escolha de lado indisponível.');
   $side=(string)($input['side']??'');if(!in_array($side,['attack','defense'],true))throw new RuntimeException('Lado inválido.');
   $pdo->prepare('UPDATE matches SET team_a_side=?,team_b_side=? WHERE id=?')->execute([$side,$side==='attack'?'defense':'attack',$id]);
   matchEvent($pdo,$id,'side_selected','Time A inicia em '.($side==='attack'?'ataque':'defesa').'.');
  }elseif($action==='ready'){
   if(!in_array($m['status'],['side_selection','ready_check'],true))throw new RuntimeException('Confirmação indisponível.');
   if(competitiveRestriction($pdo,$actor))throw new RuntimeException('Você possui restrição competitiva. Procure o suporte.');
   $q=$pdo->prepare('UPDATE match_players SET ready=1,ready_at=CURRENT_TIMESTAMP WHERE match_id=? AND player_id=? AND ready=0');$q->execute([$id,$me['player_id']]);if($q->rowCount()!==1)throw new RuntimeException('Você já confirmou presença.');
   matchEvent($pdo,$id,'player_ready',$me['r6_nickname'].' está pronto.',$actor);
  }elseif($action==='decline'){
   if($m['status']!=='ready_check'||!$me)throw new RuntimeException('Recusa indisponível.');
   $pdo->prepare("UPDATE matches SET status='cancelled',finished_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$id]);
   matchEvent($pdo,$id,'ready_declined','Jogador recusou o ready check. Participantes liberados, sem pontos.',$actor);
  }elseif($action==='report'){
   if($m['status']!=='playing'||!$me['is_captain'])throw new RuntimeException('Somente capitães podem enviar o resultado.');
   $winner=(string)($input['winner']??'');if(!in_array($winner,['A','B'],true))throw new RuntimeException('Vencedor inválido.');
   $pdo->prepare("UPDATE matches SET status='pending_confirmation',result_reported_by=?,result_reported_team=? WHERE id=?")->execute([$me['player_id'],$winner,$id]);matchEvent($pdo,$id,'result_reported','Time '.$me['team'].' enviou resultado: vitória do Time '.$winner.'.',$actor);
  }elseif($action==='confirm'||$action==='dispute'){
   if($m['status']!=='pending_confirmation'||!$me['is_captain'])throw new RuntimeException('Confirmação indisponível.');
   $q=$pdo->prepare('SELECT team FROM match_players WHERE match_id=? AND player_id=?');$q->execute([$id,$m['result_reported_by']]);if($q->fetchColumn()===$me['team'])throw new RuntimeException('O capitão adversário precisa confirmar.');
   if($action==='dispute'){$pdo->prepare("UPDATE matches SET status='disputed' WHERE id=?")->execute([$id]);matchEvent($pdo,$id,'result_disputed','Resultado contestado. Aguardando análise da staff.',$actor);}
   else{$pdo->prepare('UPDATE matches SET result_confirmed_by=? WHERE id=?')->execute([$me['player_id'],$id]);matchEvent($pdo,$id,'result_confirmed','Resultado confirmado pelo capitão adversário.',$actor);applyMatchResult($pdo,$m,$m['result_reported_team']);}
  }elseif($action==='resolve'&&$staff){
   if($m['status']!=='disputed')throw new RuntimeException('Partida não está em disputa.');
   applyMatchResult($pdo,$m,(string)($input['winner']??''));matchEvent($pdo,$id,'staff_resolution','Resultado definido pela staff.',$actor);
  }elseif($action==='cancel'&&$staff){
   if(in_array($m['status'],['finished','cancelled'],true))throw new RuntimeException('Partida já encerrada.');
   $pdo->prepare("UPDATE matches SET status='cancelled',finished_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$id]);matchEvent($pdo,$id,'match_cancelled','Partida cancelada pela staff.',$actor);
  }else throw new RuntimeException('Ação indisponível.');
  $q=$pdo->prepare("SELECT COUNT(*) FROM match_players WHERE match_id=? AND ready=1");$q->execute([$id]);$ready=(int)$q->fetchColumn();
  $q=$pdo->prepare("SELECT status,team_a_side FROM matches WHERE id=?");$q->execute([$id]);$current=$q->fetch(PDO::FETCH_ASSOC);
  if($current['status']==='ready_check'&&$ready===10){
   $pdo->prepare("UPDATE matches SET status='captain_vote' WHERE id=?")->execute([$id]);
   matchEvent($pdo,$id,'ready_completed','Todos confirmaram. Votação de capitães iniciada.');
  }
  if($current['status']==='side_selection'&&$ready===10&&$current['team_a_side']){
   $pdo->prepare("UPDATE matches SET status='playing',started_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$id]);matchEvent($pdo,$id,'match_started','Todos prontos. Partida iniciada.');
  }
  $pdo->prepare('UPDATE matches SET version=version+1 WHERE id=?')->execute([$id]);$pdo->exec('COMMIT');
 }catch(Throwable $e){if(empty($transactionCommitted))$pdo->exec('ROLLBACK');throw $e;}
}
