<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Services\AuditService;

final class UserController extends Controller
{
    public function index(Request $request): string
    {
        $professionals=array_values(array_filter((new \App\Repositories\CatalogRepository())->professionals(Auth::tenantId(),true),fn($item)=>empty($item['usuario_id'])));
        return view('users/index',['users'=>(new UserRepository())->all(Auth::tenantId()),'professionals'=>$professionals]);
    }
    public function store(Request $request): never
    {
        $this->run($request,'/usuarios',function()use($request):void{
            (new Validator())->required($request->body,'nome','Nome',150)->email($request->body,'email',true)->required($request->body,'senha','Senha',255)->oneOf($request->body,'perfil',['proprietario','administrador','profissional'])->throw();
            if(strlen((string)$request->input('senha'))<12){throw new ValidationException(['senha'=>'Use uma senha com pelo menos 12 caracteres.']);}
            $id=(new UserRepository())->create(Auth::tenantId(),['nome'=>trim((string)$request->input('nome')),'email'=>trim((string)$request->input('email')),'senha'=>(string)$request->input('senha'),'perfil'=>(string)$request->input('perfil'),'profissional_id'=>(int)$request->input('profissional_id')]);
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'usuario.criado','usuario',$id,['perfil'=>$request->input('perfil')],$request->ip());
            Session::flash('success','Usuário cadastrado.');
        });
    }
}
