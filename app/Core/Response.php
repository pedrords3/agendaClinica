<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . url($path), true, $status);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function abort(int $status, string $message = 'Recurso não encontrado.'): never
    {
        http_response_code($status);
        echo view('errors/http', ['status' => $status, 'message' => $message]);
        exit;
    }
}

