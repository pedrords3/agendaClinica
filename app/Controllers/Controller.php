<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use Throwable;

abstract class Controller
{
    protected function run(Request $request, string $redirect, callable $callback): never
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $_SESSION['_old'] = array_diff_key($request->body, array_flip(['senha','password','_token']));
            Session::flash('errors', $exception->errors);
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect($redirect);
    }
}

