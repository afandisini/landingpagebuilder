<?php
class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../config/config.php';
            $db = $config['db'];
            $db['host'] = getenv('DB_HOST') ?: $db['host'];
            $db['name'] = getenv('DB_NAME') ?: $db['name'];
            $db['user'] = getenv('DB_USER') ?: $db['user'];
            $db['pass'] = getenv('DB_PASS') ?: $db['pass'];
            $db['charset'] = getenv('DB_CHARSET') ?: $db['charset'];
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['name'],
                $db['charset']
            );
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }
}
