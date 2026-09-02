<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\ClientRepository;
use App\Services\AuditService;

final class ClientController extends Controller
{
    public function index(Request $request): string
    {
        $search=trim((string)$request->input('busca'));
        $scope=Auth::role()==='profissional'?Auth::professionalId():null;
        return view('clients/index',['clients'=>(new ClientRepository())->all(Auth::tenantId(),$search,$scope),'search'=>$search]);
    }
    public function store(Request $request): never
    {
        $this->run($request,'/clientes',function()use($request):void{
            (new Validator())->required($request->body,'nome','Nome',150)->required($request->body,'telefone','Telefone',30)->email($request->body,'email')->throw();
            $id=(new ClientRepository())->create(Auth::tenantId(),['nome'=>trim((string)$request->input('nome')),'telefone'=>trim((string)$request->input('telefone')),'whatsapp'=>trim((string)$request->input('whatsapp')),'email'=>trim((string)$request->input('email')),'data_nascimento'=>trim((string)$request->input('data_nascimento'))]);
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'cliente.criado','cliente',$id,[],$request->ip());
            Session::flash('success','Cliente cadastrado.');
        });
    }
    public function show(Request $request): string
    {
        $id=(int)($request->params['id']??0); $repo=new ClientRepository(); $scope=Auth::role()==='profissional'?Auth::professionalId():null; $client=$repo->find(Auth::tenantId(),$id,$scope);
        if(!$client){Response::abort(404);}
        return view('clients/show',['client'=>$client,'history'=>$repo->history(Auth::tenantId(),$id,$scope),'company'=>(new \App\Repositories\CompanyRepository())->find(Auth::tenantId())]);
    }
}
