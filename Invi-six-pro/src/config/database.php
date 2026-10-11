<?php

  declare(strict_types=1);
  namespace App\Config;

  use PDO;
  use PDOException;
  use RuntimeException;

  final class Database {

    private static ?PDO $connection = null;

    public static function connection(): PDO {

      if (self::$connection) {
        return self::$connection;
      }

      $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        Env::get('DB_HOST'),
        Env::get('DB_PORT', '6543'),
        Env::get('DB_DATABASE', 'postgres')
      );

      try {
        return self::$connection = new PDO(
          $dsn,
          Env::get('DB_USERNAME'),
          Env::get('DB_PASSWORD'),
          [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_PERSISTENT => false,
          ]
        );
      } catch (PDOException $e) {
        throw new RuntimeException('Falha na conexão com o banco.', 0, $e);
      }
    }
  }
  
?>