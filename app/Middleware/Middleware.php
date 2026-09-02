<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class Middleware
{
    public static function handle(array $middleware, Request $request): void
    {
        foreach ($middleware as $item) {
            if ($item === 'auth' && !Auth::check()) {
                Session::flash('error', 'Entre para continuar.');
                Response::redirect('/login');
            }
            if ($item === 'guest' && Auth::check()) {
                Response::redirect('/dashboard');
            }
            if ($item === 'csrf' && !Csrf::valid((string) $request->input('_token', $request->server['HTTP_X_CSRF_TOKEN'] ?? ''))) {
                if ($request->expectsJson()) {
                    Response::json(['error' => 'Token de segurança inválido. Atualize a página.'], 419);
                }
                Response::abort(419, 'Sua sessão de formulário expirou. Atualize a página.');
            }
            if (str_starts_with($item, 'role:')) {
                $roles = explode(',', substr($item, 5));
                if (!in_array(Auth::role(), $roles, true)) {
                    Response::abort(403, 'Você não tem permissão para realizar esta ação.');
                }
            }
        }
    }
}

