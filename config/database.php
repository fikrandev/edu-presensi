<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function config(): array
    {
        $get = static function (string $key, string $default): string {
            if (function_exists('env')) {
                $val = env($key, $default);
                return $val !== null ? $val : $default;
            }
            $val = getenv($key);
            return $val !== false ? (string)$val : $default;
        };

        return [
            'host'     => $get('DB_HOST', '127.0.0.1'),
            'port'     => $get('DB_PORT', '3306'),
            'database' => $get('DB_NAME', 'edu_presensi_saas'),
            'username' => $get('DB_USER', 'root'),
            'password' => $get('DB_PASS', ''),
            'charset'  => 'utf8mb4',
            'timezone' => $get('DB_TIMEZONE', '+08:00'),
        ];
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function pdo(?array $overrideCfg = null): PDO
    {
        if (self::$instance === null || $overrideCfg !== null) {
            $cfg = $overrideCfg ? array_merge(self::config(), $overrideCfg) : self::config();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );
            $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            try {
                $tz = $cfg['timezone'] ?: '+08:00';
                $pdo->exec("SET time_zone = " . $pdo->quote($tz));
            } catch (Throwable) {
            }
            if ($overrideCfg !== null) {
                return $pdo;
            }
            self::$instance = $pdo;
        }
        return self::$instance;
    }

    public static function pdoWithoutDb(?array $overrideCfg = null): PDO
    {
        $cfg = $overrideCfg ? array_merge(self::config(), $overrideCfg) : self::config();
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);
        return new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
