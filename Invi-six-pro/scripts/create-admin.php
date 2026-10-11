<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;
require_once __DIR__ . '/../src/database/connection.php';
\App\Config\Env::load(dirname(__DIR__));

if (($argv[1] ?? '') !== '--create' || count($argv) !== 4) {
    exit("Uso: php scripts/create-admin.php --create nome nick\nDefina INVI_ADMIN_PASSWORD privadamente no ambiente do processo.\n");
}
[$command, $flag, $name, $nickname] = $argv;
$password = getenv('INVI_ADMIN_PASSWORD') ?: '';
if (mb_strlen($name)<2 || mb_strlen($name)>100 || mb_strlen($nickname)<2 || mb_strlen($nickname)>50
    || mb_strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0")) {
    fwrite(STDERR, "Dados inválidos. Senha: mínimo 8 caracteres e máximo 72 bytes.\n");
    exit(1);
}
$db = null;
try {
    $db = \App\Config\Database::connection();
    beginWriteTransaction($db);
    $query = $db->prepare("INSERT INTO users(name,r6_nickname,password_hash,role,status) VALUES(?,?,?,'admin','active')");
    $query->execute([$name,$nickname,password_hash($password,PASSWORD_DEFAULT)]);
    $db->commit();
    echo "Administrador criado. Nenhuma senha foi registrada.\n";
} catch (Throwable $error) {
    if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
    fwrite(STDERR,"Não foi possível criar a conta. Verifique a migração, conexão e unicidade do nick.\n");
    exit(1);
}
