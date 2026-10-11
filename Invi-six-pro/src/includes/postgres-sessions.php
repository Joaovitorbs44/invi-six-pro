<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/database/connection.php';

/** A dedicated connection keeps per-session locks separate from game transactions. */
final class PostgresSessions implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private PDO $db;
    private ?string $locked=null;
    public function __construct(?PDO $db=null){
        // Sessions hold their own transaction; game writes use the main connection.
        $this->db=$db??new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', \App\Config\Env::get('DB_HOST'), \App\Config\Env::get('DB_PORT','6543'), \App\Config\Env::get('DB_DATABASE','postgres')),
            \App\Config\Env::get('DB_USERNAME'), \App\Config\Env::get('DB_PASSWORD'),
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::ATTR_PERSISTENT=>false]
        );
    }
    public function open(string $path,string $name): bool{return true;}
    private function lock(string $id): string
    {
        $key=hash('sha256',$id);
        if($this->locked!==$key){$this->close();$this->db->beginTransaction();
            $this->db->exec("SET LOCAL lock_timeout = '5s'");
            $this->db->exec("SET LOCAL statement_timeout = '15s'");
            $q=$this->db->prepare('SELECT pg_advisory_xact_lock(731422,hashtext(?))');$q->execute([$key]);$this->locked=$key;}
        return $key;
    }
    public function close(): bool{if($this->db->inTransaction())$this->db->commit();$this->locked=null;return true;}
    public function validateId(string $id): bool{$q=$this->db->prepare('SELECT 1 FROM app_sessions WHERE id_hash=? AND expires_at>?');$q->execute([hash('sha256',$id),time()]);return (bool)$q->fetchColumn();}
    public function read(string $id): string|false{$key=$this->lock($id);$q=$this->db->prepare('SELECT payload FROM app_sessions WHERE id_hash=? AND expires_at>?');$q->execute([$key,time()]);$value=$q->fetchColumn();return $value===false?'':$value;}
    public function write(string $id,string $data): bool{$key=$this->lock($id);$q=$this->db->prepare('INSERT INTO app_sessions(id_hash,payload,expires_at) VALUES(?,?,?) ON CONFLICT(id_hash) DO UPDATE SET payload=excluded.payload,expires_at=excluded.expires_at');return $q->execute([$key,$data,time()+1800]);}
    public function destroy(string $id): bool{$key=$this->lock($id);$q=$this->db->prepare('DELETE FROM app_sessions WHERE id_hash=?');$ok=$q->execute([$key]);$this->close();return $ok;}
    public function gc(int $max_lifetime): int|false{$q=$this->db->prepare('DELETE FROM app_sessions WHERE id_hash IN (SELECT id_hash FROM app_sessions WHERE expires_at<? LIMIT 500)');$q->execute([time()]);return $q->rowCount();}
    public function updateTimestamp(string $id,string $data): bool{$key=$this->lock($id);$q=$this->db->prepare('UPDATE app_sessions SET expires_at=? WHERE id_hash=? AND expires_at>?');return $q->execute([time()+1800,$key,time()]);}
    public function __destruct(){if(isset($this->db)&&$this->db->inTransaction())$this->db->rollBack();}
}
