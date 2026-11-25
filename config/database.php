<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function config(): array
    {
        return [
            'host'     => getenv('DB_HOST') ?: '127.0.0.1',
            'port'     => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'edu_presensi_saas',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
            'charset'  => 'utf8mb4',
        ];
    }

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            $cfg = self::config();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );
            self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$instance->exec("SET time_zone = '+08:00'");
        }
        return self::$instance;
    }

    public static function pdoWithoutDb(): PDO
    {
        $cfg = self::config();
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);
        return new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
