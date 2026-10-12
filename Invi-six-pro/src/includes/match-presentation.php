<?php
declare(strict_types=1);
function matchStatusLabels(): array {
 return ['ready_check'=>'Confirmando presença','captain_vote'=>'Votação de capitães','draft'=>'Escolha de equipes','veto'=>'Veto de mapas','side_selection'=>'Escolha de lados','playing'=>'Em andamento','pending_confirmation'=>'Aguardando confirmação','disputed'=>'Em disputa','finished'=>'Finalizada','cancelled'=>'Cancelada','completed'=>'Finalizada','pending'=>'Pendente','in_progress'=>'Em andamento'];
}
function matchStatusTone(string $status): string {
 return match($status){'finished','completed'=>'success','cancelled','disputed'=>'error','ready_check','side_selection','pending','pending_confirmation'=>'warning','captain_vote','draft','veto','playing','in_progress'=>'info',default=>'neutral'};
}
function matchHistoryResult(array $match): string {
 $winner=$match['winner_team']??null;
 if(!in_array($winner,['A','B'],true))return $match['status']==='cancelled'?'Sem resultado':'Resultado não definido';
 $own=$match['team']??null;
 $result=in_array($own,['A','B'],true)?($own===$winner?'Vitória':'Derrota'):'Equipe '.$winner.' venceu';
 if(!empty($match['points_applied']))$result.=in_array($own,['A','B'],true)?($own===$winner?' · +5 pontos':' · −3 pontos'):'';
 elseif(!empty($match['is_simulation']))$result.=' · Simulação sem pontos';
 return $result;
}
