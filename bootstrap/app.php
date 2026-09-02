<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Core/Env.php';
App\Core\Env::load(BASE_PATH . '/.env');
date_default_timezone_set((string) App\Core\Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = BASE_PATH . '/app/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
    require BASE_PATH . '/app/Helpers/functions.php';
}

App\Core\ErrorHandler::register();
if (PHP_SAPI !== 'cli') {
    App\Core\Security::boot();
}
