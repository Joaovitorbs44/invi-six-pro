<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

function applicationDatabases(): array
{
    $connection = \App\Config\Database::connection();
    return [$connection, $connection];
}

function accountsDatabase(PDO $competitive): PDO
{
    return $competitive;
}

// One lobby: every writer uses the same transaction-scoped lock, including staff.
// The lock is released by PostgreSQL on commit/rollback and supports the pooler.
function beginWriteTransaction(PDO $db): void
{
    $db->beginTransaction();
    try {
        $db->exec("SET LOCAL statement_timeout = '15s'");
        $db->exec("SET LOCAL lock_timeout = '5s'");
        $db->exec("SET LOCAL TIME ZONE 'UTC'");
        $db->query('SELECT pg_advisory_xact_lock(731421, 1)');
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
}

function databaseIdentities(PDO $db, array $ids, bool $players = false): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
    $result = [];
    foreach (array_chunk($ids, 400) as $chunk) {
        $fields = $players ? 'p.id,p.user_id,p.r6_nickname,p.r6_rank,p.r6_tracker_url,u.status,u.role' : 'id,name,r6_nickname,role,status';
        $from = $players ? 'players p JOIN users u ON u.id=p.user_id' : 'users';
        $key = $players ? 'p.id' : 'id';
        $query = $db->prepare('SELECT ' . $fields . ' FROM ' . $from . ' WHERE ' . $key . ' IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')');
        $query->execute($chunk);
        foreach ($query->fetchAll() as $row) $result[(int)$row['id']] = $row;
    }
    return $result;
}

function playerForUser(PDO $db, int $user): ?array
{
    $query = $db->prepare('SELECT id FROM players WHERE user_id=?');
    $query->execute([$user]);
    $id = $query->fetchColumn();
    return $id ? (databaseIdentities($db, [(int)$id], true)[(int)$id] ?? null) : null;
}

function positiveId(mixed $value): int
{
    if (!is_int($value) && !is_string($value)) throw new RuntimeException('Identificador inválido.');
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) throw new RuntimeException('Identificador inválido.');
    return $id;
}

function validateDatabaseActor(PDO $db, int $actor, bool $staff = false): void
{
    $user = databaseIdentities($db, [$actor])[$actor] ?? null;
    if (!$user || $user['status'] !== 'active' || ($staff && !in_array($user['role'], ['staff', 'admin'], true))) {
        throw new RuntimeException('Conta ou permissão indisponível.');
    }
}

function validateDatabaseAdmin(PDO $db, int $actor): void
{
    validateDatabaseActor($db, $actor, true);
    if ((databaseIdentities($db, [$actor])[$actor]['role'] ?? null) !== 'admin') {
        throw new RuntimeException('Acesso não autorizado.');
    }
}
