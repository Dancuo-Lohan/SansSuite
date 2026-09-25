<?php
declare(strict_types=1);

namespace Support;

use CorianderCore\Core\Database\DatabaseHandler;
use PDO;
use RuntimeException;

final class Database
{
    public static function fromHandler(DatabaseHandler $handler): PDO
    {
        $pdo = $handler->getPDO();
        if ($pdo === null) {
            throw new RuntimeException('La connexion SQLite est indisponible. Exécutez les migrations.');
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }
}
