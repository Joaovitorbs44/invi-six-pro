<?php
declare(strict_types=1);
require_once __DIR__.'/../database/connection.php';

function seasonSchema(PDO $db): void {
  $ready=$db->query("SELECT to_regclass('season_closures') IS NOT NULL AND to_regclass('season_final_standings') IS NOT NULL")->fetchColumn();
 if(!$ready)throw new RuntimeException('Execute a migração revisada antes de iniciar o aplicativo.');
}
/** Caller holds the shared transaction lock, including results and lobby writers. */
function closeSeasonInTransaction(PDO $db,int $id,string $reason,?int $admin=null): void {
 $q=$db->prepare('SELECT * FROM seasons WHERE id=?');$q->execute([$id]);$season=$q->fetch();
 if(!$season||$season['status']!=='active')throw new RuntimeException('A temporada já foi encerrada ou não está ativa.');
 $q=$db->prepare("SELECT COUNT(*) FROM matches WHERE season_id=? AND status NOT IN ('finished','cancelled')");$q->execute([$id]);
 if((int)$q->fetchColumn()>0)throw new RuntimeException('Resolva as partidas em andamento, resultados pendentes e disputas antes de encerrar a temporada.');
 // Freeze the same eligibility and ordering used by the current ranking, including zero-point players.
 $profiles=accountsDatabase($db)->query("SELECT p.id,p.r6_nickname nickname,p.r6_rank FROM players p JOIN users u ON u.id=p.user_id WHERE u.status='active' AND u.role IN ('player','admin')")->fetchAll();
 $entries=[];foreach($profiles as $p)$entries[(int)$p['id']]=$p+['points'=>0,'wins'=>0,'losses'=>0,'matches_played'=>0];
 $q=$db->prepare('SELECT * FROM player_stats WHERE season_id=?');$q->execute([$id]);
 foreach($q->fetchAll() as $stats){$player=(int)$stats['player_id'];if(isset($entries[$player]))foreach(['points','wins','losses','matches_played'] as $field)$entries[$player][$field]=(int)$stats[$field];}
 $entries=array_values($entries);usort($entries,static fn($a,$b)=>($b['points']<=>$a['points'])?:($b['wins']<=>$a['wins'])?:($a['matches_played']<=>$b['matches_played'])?:($a['id']<=>$b['id']));
 $insert=$db->prepare('INSERT INTO season_final_standings(season_id,position,player_id,nickname,r6_rank,points,wins,losses,matches_played) VALUES(?,?,?,?,?,?,?,?,?)');
 foreach($entries as $index=>$entry)$insert->execute([$id,$index+1,$entry['id'],$entry['nickname'],$entry['r6_rank'],$entry['points'],$entry['wins'],$entry['losses'],$entry['matches_played']]);
 $db->prepare("UPDATE seasons SET status='finished',finished_at=CURRENT_TIMESTAMP WHERE id=? AND status='active'")->execute([$id]);
 $db->prepare('INSERT INTO season_closures(season_id,reason,closed_at,admin_user_id) VALUES(?,?,CURRENT_TIMESTAMP,?)')->execute([$id,$reason,$admin]);
 $db->prepare('INSERT INTO logs(type,description,staff_user_id,additional_data) VALUES(?,?,?,?)')->execute(['season_closed',$reason==='manual'?'Temporada encerrada manualmente; classificação congelada.':'Temporada encerrada automaticamente; classificação congelada.',$admin,json_encode(['season_id'=>$id,'reason'=>$reason])]);
}

function closeSeason(PDO $db,int $id,int $admin,bool $confirmed): void {
 validateDatabaseActor($db,$admin,true);
 if((databaseIdentities($db,[$admin])[$admin]['role']??null)!=='admin')throw new RuntimeException('Somente administradores podem encerrar temporadas.');
 if(!$confirmed)throw new RuntimeException('Confirme explicitamente o encerramento.');
 seasonSchema($db);beginWriteTransaction($db);
 try{validateDatabaseAdmin($db,$admin);closeSeasonInTransaction($db,$id,'manual',$admin);$db->exec('COMMIT');}catch(Throwable $error){$db->exec('ROLLBACK');throw $error;}
}

/** Safe on page requests and from the CLI scheduler; never cancels matches. */
function reconcileSeasons(PDO $db): array {
 seasonSchema($db);beginWriteTransaction($db);$closed=[];$blocked=[];
 try {
  foreach($db->query("SELECT id FROM seasons WHERE status='active' AND ends_at<=CURRENT_TIMESTAMP")->fetchAll(PDO::FETCH_COLUMN) as $id){
   try{closeSeasonInTransaction($db,(int)$id,'automatic');$closed[]=(int)$id;}catch(RuntimeException $error){if($error instanceof PDOException)throw $error;$blocked[]=(int)$id;}
  }
  if(!$db->query("SELECT 1 FROM seasons WHERE status='active'")->fetchColumn()){
   $next=$db->query("SELECT id FROM seasons WHERE status='scheduled' AND starts_at<=CURRENT_TIMESTAMP ORDER BY starts_at,id LIMIT 1")->fetchColumn();
   if($next){$db->prepare("UPDATE seasons SET status='active',starts_at=CURRENT_TIMESTAMP,ends_at=(CURRENT_TIMESTAMP + INTERVAL '30 days') WHERE id=? AND status='scheduled'")->execute([$next]);$db->prepare('INSERT INTO logs(type,description,additional_data) VALUES(?,?,?)')->execute(['season_started','Temporada agendada ativada por 30 dias.',json_encode(['season_id'=>(int)$next])]);}
  }
  $db->exec('COMMIT');return ['closed'=>$closed,'blocked'=>$blocked];
 }catch(Throwable $error){$db->exec('ROLLBACK');throw $error;}
}

function startSeason(PDO $db,int $admin,string $name): int {
 validateDatabaseActor($db,$admin,true);if((databaseIdentities($db,[$admin])[$admin]['role']??null)!=='admin')throw new RuntimeException('Somente administradores.');
 $name=trim($name);if(mb_strlen($name)<3||mb_strlen($name)>80)throw new RuntimeException('Informe um nome entre 3 e 80 caracteres.');
 reconcileSeasons($db);beginWriteTransaction($db);
 try {
  validateDatabaseAdmin($db,$admin);
  if($db->query("SELECT 1 FROM seasons WHERE status='active'")->fetchColumn())throw new RuntimeException('Já existe uma temporada ativa. Resolva suas partidas e encerre-a primeiro.');
  $db->prepare("INSERT INTO seasons(name,starts_at,ends_at,status) VALUES(?,CURRENT_TIMESTAMP,(CURRENT_TIMESTAMP + INTERVAL '30 days'),'active')")->execute([$name]);$id=(int)$db->query('SELECT lastval()')->fetchColumn();
  $db->prepare('INSERT INTO logs(type,description,staff_user_id,additional_data) VALUES(?,?,?,?)')->execute(['season_started','Temporada iniciada por 30 dias.',$admin,json_encode(['season_id'=>$id])]);
  $db->exec('COMMIT');return $id;
 }catch(Throwable $error){$db->exec('ROLLBACK');throw $error;}
}
