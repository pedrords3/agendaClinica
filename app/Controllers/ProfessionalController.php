<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\CatalogRepository;
use App\Services\AuditService;

final class ProfessionalController extends Controller
{
    public function index(Request $request): string
    {
        $catalog = new CatalogRepository();
        return view('professionals/index', ['professionals'=>$catalog->professionals(Auth::tenantId()),'services'=>$catalog->services(Auth::tenantId(), true)]);
    }

    public function store(Request $request): never
    {
        $this->run($request, '/profissionais', function () use ($request): void {
            (new Validator())->required($request->body,'nome','Nome',150)->email($request->body,'email')->throw();
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $request->input('cor_agenda')) ? $request->input('cor_agenda') : '#5b5bd6';
            $id = (new CatalogRepository())->createProfessional(Auth::tenantId(), [
                'nome'=>trim((string)$request->input('nome')),'telefone'=>trim((string)$request->input('telefone')),
                'email'=>trim((string)$request->input('email')),'descricao'=>trim((string)$request->input('descricao')),
                'especialidade'=>trim((string)$request->input('especialidade')),'cor_agenda'=>$color,
                'servicos'=>is_array($request->input('servicos', [])) ? $request->input('servicos') : [],
            ]);
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'profissional.criado','profissional',$id,[],$request->ip());
            Session::flash('success','Profissional cadastrado.');
        });
    }

    public function toggle(Request $request): never
    {
        $id = (int) ($request->params['id'] ?? 0);
        if ((new CatalogRepository())->toggle('profissionais',Auth::tenantId(),$id)) {
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'profissional.status_alterado','profissional',$id,[],$request->ip());
            Session::flash('success','Status do profissional atualizado.');
        }
        \App\Core\Response::redirect('/profissionais');
    }
}

