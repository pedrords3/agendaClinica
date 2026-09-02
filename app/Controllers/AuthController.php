<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function form(Request $request): string { return view('auth/login'); }

    public function login(Request $request): never
    {
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('senha');
        $service = new AuthService();
        if ($service->attempt($email, $password, $request->ip())) {
            Session::flash('success', 'Bem-vindo de volta!');
            Response::redirect('/dashboard');
        }
        Session::flash('error', $service->isBlocked($email, $request->ip()) ? 'Muitas tentativas. Aguarde 15 minutos.' : 'E-mail ou senha inválidos.');
        Response::redirect('/login');
    }

    public function logout(Request $request): never
    {
        Auth::logout();
        session_start();
        Session::flash('success', 'Você saiu com segurança.');
        Response::redirect('/login');
    }
}

