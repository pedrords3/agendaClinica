# Auditoria do projeto legado

## O que existia

O repositório original possuía login, perfis simples de administrador e médico, cadastro parcial de médicos, criação e edição de consultas, mudança de status, dashboard em lista e FullCalendar. O frontend usava Bootstrap, Font Awesome, jQuery para máscara e um seletor de tema. Não havia schema, migration, seed ou testes.

## Componentes aproveitados

Foram aproveitados como referência de produto: agenda em lista/calendário, cores por profissional, acesso rápido a novo agendamento, cartões de resumo e preferência de tema. O código antigo não foi incorporado à nova camada de domínio.

## Problemas encontrados

- conexão MySQL e credenciais fixas no código, charset incompleto e exceções exibidas ao usuário;
- bypass universal `$senha === 'password'` e credenciais publicadas na tela;
- sessão sem regeneração, timeout ou atributos seguros;
- nenhuma proteção CSRF;
- edição e alteração de status por ID sem autorização por recurso (IDOR);
- perfil profissional associado por coincidência de e-mail;
- consulta do FullCalendar sem o escopo aplicado na tabela, expondo todos os clientes a profissionais;
- valores do banco inseridos em HTML, atributos e JavaScript sem serialização segura, permitindo XSS;
- horários fixos de 30 minutos, sem serviço, jornada ou bloqueio;
- detecção de conflito apenas pela igualdade do início, sem transação e vulnerável a corrida;
- cliente duplicado dentro de cada consulta;
- PHP, SQL, HTML, CSS e JavaScript misturados em arquivos de até 873 linhas;
- ações visuais de editar/excluir médico sem implementação;
- endpoint manual `add_color_column.php`, que expunha erro SQL e alterava dados via navegador;
- nomenclatura e regras rigidamente médicas, sem `empresa_id` ou isolamento multiempresa.

## Decisão

A compatibilidade interna foi descartada. O `.htaccess` encaminha as rotas para o novo front controller e os endpoints monolíticos antigos foram removidos para que não exista uma superfície insegura caso a reescrita do Apache seja desativada. O diagnóstico permanece versionado neste documento.
