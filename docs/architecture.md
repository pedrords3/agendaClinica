# Arquitetura e decisões

## Fluxo HTTP

`index.php` inicializa ambiente, autoload, erros e segurança. O roteador associa método/caminho a um controller e executa middleware de autenticação, perfil e CSRF antes da ação. Controllers validam a requisição e delegam persistência aos repositories e regras aos services.

## Isolamento multiempresa

A sessão autenticada guarda o `empresa_id` proveniente do usuário validado. Repositories nunca recebem o tenant da URL ou formulário; controllers passam exclusivamente `Auth::tenantId()`. Toda operação por ID combina `id` e `empresa_id`. Recursos do perfil profissional recebem ainda o `profissional_id` da sessão. As associações relevantes repetem `empresa_id`, permitindo auditoria e consultas eficientes.

## Datas

Jornadas recorrentes são horários locais da empresa. Agendamentos e bloqueios são convertidos do timezone configurado para UTC antes da persistência e convertidos novamente na exibição. A conexão PDO força a sessão SQL para UTC.

## Disponibilidade

O gerador parte dos múltiplos períodos do dia da semana e avança pelo intervalo de grade configurado. Cada candidato considera duração do serviço, intervalos antes/depois, antecedência mínima, horizonte máximo, agenda existente e bloqueios. A mesma classe é usada na API interna, página pública e validação final do backend.

## Concorrência

A trava nomeada `agenda:{empresa}:{profissional}:{data}` do MySQL/MariaDB serializa reservas concorrentes para o mesmo recurso/dia. Depois de adquirir a trava, o serviço inicia a transação, recalcula a disponibilidade e insere. Esse desenho funciona entre processos PHP e evita depender de uma validação prévia feita pelo navegador.

## Evolução

Origem e status usam valores validados, porém permanecem em colunas textuais com constraints, facilitando migrations futuras. Integrações de WhatsApp/e-mail podem consumir os eventos registrados na auditoria ou uma futura outbox. Planos SaaS podem ser adicionados em tabelas de assinatura/limites sem alterar o vínculo central por empresa.

