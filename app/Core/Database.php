<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }
        $host = (string) Env::get('DB_HOST', '127.0.0.1');
        $port = (int) Env::get('DB_PORT', 3306);
        $name = (string) Env::get('DB_DATABASE', 'plataforma_agendamentos');
        try {
            self::$connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                (string) Env::get('DB_USERNAME', 'root'),
                (string) Env::get('DB_PASSWORD', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+00:00'",
                ]
            );
        } catch (PDOException $exception) {
            Logger::error('Falha ao conectar ao banco.', ['exception' => $exception]);
            throw new RuntimeException('Não foi possível conectar ao banco de dados. Verifique o arquivo .env.');
        }
        return self::$connection;
    }
}

