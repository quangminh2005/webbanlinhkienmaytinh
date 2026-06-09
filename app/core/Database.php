<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require __DIR__ . '/../../config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if (($config['ssl_mode'] ?? '') === 'required') {
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) ($config['ssl_verify_server_cert'] ?? false);
            }

            $sslCaPath = self::resolveSslCaPath($config);
            if ($sslCaPath !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
            }
        }

        try {
            self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
            if (!empty($config['timezone'])) {
                self::$instance->prepare('SET time_zone = :timezone')->execute([
                    'timezone' => $config['timezone'],
                ]);
            }
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

        return self::$instance;
    }

    private static function resolveSslCaPath(array $config): string
    {
        $sslCaPath = trim((string) ($config['ssl_ca_path'] ?? ''));
        if ($sslCaPath !== '') {
            return $sslCaPath;
        }

        $sslCaBase64 = trim((string) ($config['ssl_ca_base64'] ?? ''));
        if ($sslCaBase64 === '') {
            return '';
        }

        $decoded = base64_decode($sslCaBase64, true);
        if (!is_string($decoded) || $decoded === '') {
            return '';
        }

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pc_shop_mysql_ca.pem';
        if (!is_file($path) || file_get_contents($path) !== $decoded) {
            file_put_contents($path, $decoded);
        }

        return $path;
    }
}
