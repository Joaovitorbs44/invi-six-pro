<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/csrf.php';
require_once __DIR__ . '/../src/includes/matches.php';

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}

check(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML escaping');
check((bool) preg_match('~\A/assets/js/dates\.js\?v=[a-f0-9]{12}\z~', assetUrl('/assets/js/dates.js')), 'Versioned script URL');
check(assetUrl('/assets/js/dates.js') === assetUrl('/assets/js/dates.js'), 'Stable asset version');
foreach (['https://example.com','//example.com',"/login\r\nX-Test: value",'/\\example.com'] as $destination) {
    try { redirect($destination); throw new RuntimeException('Unsafe redirect accepted'); }
    catch (InvalidArgumentException) { $checks++; }
}
check(safeLoginDestination('/partida/42') === '/partida/42', 'Match redirect');
check(safeLoginDestination('/admin/user/delete') === null, 'Mutation redirect denied');
check(safeLoginDestination('//example.com') === null, 'External redirect denied');
check(positiveId('42') === 42, 'Positive ID');
foreach (['0','-1','1.5',[],str_repeat('9',100)] as $invalid) {
    try { positiveId($invalid); throw new LogicException('Invalid ID accepted'); }
    catch (RuntimeException) { $checks++; }
}
$_SESSION = [];
check(strlen(csrfToken()) === 64, 'CSRF token length');
check(csrfToken() === csrfToken(), 'CSRF stable during session');
$_POST['_token'] = csrfToken();
verifyCsrf();
$checks++;
check(str_contains(csrfField(), 'name="_token"'), 'CSRF field');
check(brazilDate('2026-10-11 03:00:00') === '11/10/2026 00:00', 'UTC conversion');
check(matchHistoryResult(['winner_team'=>'A','team'=>'A','points_applied'=>1,'status'=>'finished']) === 'Vitória · +5 pontos', 'Win display');
check(matchHistoryResult(['winner_team'=>'A','team'=>'B','points_applied'=>1,'status'=>'finished']) === 'Derrota · −3 pontos', 'Loss display');
$players = [['player_id'=>1,'team'=>'A'],['player_id'=>2,'team'=>'B']];
for ($id=3; $id<=10; $id++) $players[] = ['player_id'=>$id,'team'=>null];
check(draftAssignmentPossible($players, []), 'Two teams can be filled');
$players[2]['team']='A';$players[3]['team']='A';$players[4]['team']='A';$players[5]['team']='A';$players[6]['team']='A';
check(!draftAssignmentPossible($players, []), 'Overfilled team rejected');
foreach (matchMapCatalog() as $name=>$map) {
    if (!$map['image']) continue;
    check(matchMapImage($name) === $map['image'], 'Map asset: '.$name);
}
echo "$checks checks passed. No database connection or application data changed.\n";
