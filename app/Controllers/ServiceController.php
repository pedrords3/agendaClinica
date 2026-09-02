<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\CatalogRepository;
use App\Services\AuditService;

final class ServiceController extends Controller
{
    public function index(Request $request): string { return view('services/index',['services'=>(new CatalogRepository())->services(Auth::tenantId())]); }

    public function store(Request $request): never
    {
        $this->run($request, '/servicos', function () use ($request): void {
            (new Validator())->required($request->body,'nome','Nome',150)->integer($request->body,'duracao_minutos',5,1440)->integer($request->body,'intervalo_antes',0,720)->integer($request->body,'intervalo_depois',0,720)->throw();
            $price = str_replace(',','.',trim((string)$request->input('preco')));
            if ($price !== '' && (!is_numeric($price) || (float)$price < 0)) { throw new \App\Core\ValidationException(['preco'=>'Informe um preço válido.']); }
            $id=(new CatalogRepository())->createService(Auth::tenantId(),[
                'nome'=>trim((string)$request->input('nome')),'descricao'=>trim((string)$request->input('descricao')),
                'duracao_minutos'=>(int)$request->input('duracao_minutos'),'preco'=>$price,
                'intervalo_antes'=>(int)$request->input('intervalo_antes'),'intervalo_depois'=>(int)$request->input('intervalo_depois'),
                'cor'=>preg_match('/^#[0-9a-fA-F]{6}$/',(string)$request->input('cor')) ? $request->input('cor') : '',
            ]);
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'servico.criado','servico',$id,[],$request->ip());
            Session::flash('success','Serviço cadastrado.');
        });
    }

    public function toggle(Request $request): never
    {
        $id=(int)($request->params['id']??0);
        (new CatalogRepository())->toggle('servicos',Auth::tenantId(),$id);
        Session::flash('success','Status do serviço atualizado.');
        \App\Core\Response::redirect('/servicos');
    }
}

