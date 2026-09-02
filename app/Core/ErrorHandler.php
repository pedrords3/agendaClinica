<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);
        set_exception_handler(static function (Throwable $exception): void {
            Logger::error('Exceção não tratada.', ['exception' => $exception]);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, $exception->getMessage() . PHP_EOL);
                exit(1);
            }
            if (!headers_sent()) {
                http_response_code(500);
            }
            $detail = Env::get('APP_DEBUG', false) ? '<small>' . e($exception->getMessage()) . '</small>' : '';
            echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Erro</title><body style="font-family:system-ui;padding:3rem"><h1>Algo não saiu como esperado</h1><p>Tente novamente.</p>' . $detail . '<p><a href="' . e(url('/')) . '">Voltar ao início</a></p></body></html>';
        });
    }
}
