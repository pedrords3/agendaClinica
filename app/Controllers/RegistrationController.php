<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Services\RateLimiter;
use App\Services\RegistrationService;

final class RegistrationController extends Controller
{
    public function form(Request $request): string { return view('auth/register'); }

    public function store(Request $request): never
    {
        $this->run($request,'/cadastro',function()use($request):void{
            if(trim((string)$request->input('website'))!==''){throw new ValidationException(['cadastro'=>'Não foi possível enviar o formulário.']);}
            $limiter=new RateLimiter();
            if($limiter->tooMany($request->ip(),'company_registration',3,60)){throw new ValidationException(['limite'=>'Muitas tentativas de cadastro. Tente novamente mais tarde.']);}
            (new Validator())->required($request->body,'nome_fantasia','Nome do estabelecimento',150)->required($request->body,'segmento','Segmento',100)->required($request->body,'proprietario_nome','Seu nome',150)->email($request->body,'email',true)->required($request->body,'senha','Senha',255)->throw();
            if(strlen((string)$request->input('senha'))<12){throw new ValidationException(['senha'=>'Use uma senha com pelo menos 12 caracteres.']);}
            if(!hash_equals((string)$request->input('senha'),(string)$request->input('senha_confirmacao'))){throw new ValidationException(['senha_confirmacao'=>'As senhas não coincidem.']);}
            $limiter->hit($request->ip(),'company_registration');
            (new RegistrationService())->register(['nome'=>trim((string)$request->input('nome_fantasia')),'nome_fantasia'=>trim((string)$request->input('nome_fantasia')),'slug'=>trim((string)$request->input('slug')),'segmento'=>trim((string)$request->input('segmento')),'telefone'=>trim((string)$request->input('telefone')),'whatsapp'=>trim((string)$request->input('whatsapp')),'proprietario_nome'=>trim((string)$request->input('proprietario_nome')),'email'=>trim((string)$request->input('email')),'senha'=>(string)$request->input('senha')],$request->ip());
            unset($_SESSION['_old']); Session::flash('success','Empresa criada. Comece configurando seus serviços e profissionais.');
            Response::redirect('/dashboard');
        });
    }
}

