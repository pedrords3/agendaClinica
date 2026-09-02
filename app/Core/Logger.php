<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }
        $safe = [];
        foreach ($context as $key => $value) {
            if ($value instanceof Throwable) {
                $safe[$key] = ['type' => $value::class, 'message' => $value->getMessage(), 'file' => $value->getFile(), 'line' => $value->getLine()];
            } elseif (!in_array(strtolower((string) $key), ['password', 'senha', 'token'], true)) {
                $safe[$key] = $value;
            }
        }
        $line = json_encode(['at' => gmdate('c'), 'message' => $message, 'context' => $safe], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @file_put_contents($directory . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

