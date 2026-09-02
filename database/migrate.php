<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Core\Env;
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$database = (string) Env::get('DB_DATABASE', 'plataforma_agendamentos');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
    throw new RuntimeException('DB_DATABASE contém caracteres inválidos.');
}

$server = new \PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', Env::get('DB_HOST', '127.0.0.1'), (int) Env::get('DB_PORT', 3306)),
    (string) Env::get('DB_USERNAME', 'root'),
    (string) Env::get('DB_PASSWORD', ''),
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_EMULATE_PREPARES => false]
);
$server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$pdo = Database::connection();
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$executed = $pdo->query('SELECT migration FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $executed, true)) {
        echo "Já aplicada: {$name}" . PHP_EOL;
        continue;
    }
    $sql = (string) file_get_contents($file);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    try {
        foreach ($statements as $statement) {
            if (trim($statement) !== '') {
                $pdo->exec($statement);
            }
        }
        $insert = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $insert->execute(['migration' => $name]);
        echo "Aplicada: {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        throw $exception;
    }
}

echo 'Migrations concluídas.' . PHP_EOL;
