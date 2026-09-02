# Agenda Fácil — plataforma de agendamentos

Aplicação PHP 8 multiempresa para pequenos negócios que trabalham com horário marcado. A primeira versão inclui autenticação, perfis, profissionais, serviços, clientes, jornadas, bloqueios, agendamentos internos, disponibilidade dinâmica, FullCalendar, dashboard e página pública mobile-first.

## Requisitos

- PHP 8.1 ou superior com `pdo_mysql`, `mbstring` e `json`;
- MySQL 8 ou MariaDB 10.4+ com tabelas InnoDB;
- Apache com `mod_rewrite` (incluído no XAMPP);
- Composer é recomendado para gerar o autoloader, mas há um autoloader PSR-4 de fallback para facilitar a instalação local.

## Instalação no XAMPP

1. Coloque o projeto em `C:\xampp\htdocs\agendaClinica`.
2. Inicie Apache e MySQL no painel do XAMPP.
3. No terminal, entre na pasta do projeto.
4. Copie `.env.example` para `.env` e revise os valores. O arquivo `.env` é ignorado pelo Git.
5. Defina `DEV_ADMIN_PASSWORD` com uma senha local de pelo menos 12 caracteres.
6. Se o Composer estiver disponível, execute `composer install`. Ele não baixa frameworks; apenas gera o autoloader.
7. Execute as migrations e o seed:

```powershell
C:\xampp\php\php.exe database\migrate.php
C:\xampp\php\php.exe database\seed.php
```

O migrador cria automaticamente o banco informado em `DB_DATABASE`; não é necessário criar tabelas no phpMyAdmin. Acesse `http://localhost/agendaClinica`.

## Configuração do ambiente

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/agendaClinica
APP_TIMEZONE=America/Sao_Paulo
SESSION_LIFETIME=120

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plataforma_agendamentos
DB_USERNAME=root
DB_PASSWORD=
```

Em produção, use HTTPS, `APP_DEBUG=false`, credenciais exclusivas com privilégio apenas sobre o banco da aplicação e uma senha forte. Não publique o `.env`.

## Seed de demonstração

O seed cria a empresa fictícia **Studio Demo**, profissionais Ana e Carlos, quatro serviços, dois clientes fictícios, jornadas e agendamentos de exemplo. O acesso administrativo usa:

- e-mail: o valor de `DEV_ADMIN_EMAIL`;
- senha: o valor local de `DEV_ADMIN_PASSWORD`.

Nenhuma senha é fixa no código ou na migration. O seed recusa senha com menos de 12 caracteres e não executa duas vezes para a mesma empresa.

## Estrutura

```text
app/
  Controllers/       entrada HTTP e composição das telas
  Core/              roteador, banco, sessão, CSRF e infraestrutura
  Helpers/           escape, URLs, views, datas e moeda
  Middleware/        autenticação, perfis e CSRF
  Repositories/      consultas escopadas por empresa
  Services/          autenticação, auditoria, disponibilidade e reserva
bootstrap/           inicialização da aplicação
database/
  migrations/        schema SQL versionado
  migrate.php        executor de migrations
  seed.php           dados fictícios de desenvolvimento
public/assets/        CSS e JavaScript
resources/views/      templates PHP
routes/               rotas públicas, privadas e JSON
storage/              logs, sessões e cache não públicos
tests/                testes críticos sem framework externo
docs/                 decisões arquiteturais e auditoria do legado
```

## Segurança

- `password_hash(PASSWORD_DEFAULT)` e `password_verify`, sem bypass de teste;
- ID de sessão regenerado no login, timeout, cookies HttpOnly/SameSite e Secure sob HTTPS;
- rate limiting de login e dos fluxos públicos;
- CSRF em toda mutação, inclusive logout e reserva pública;
- prepared statements e emulação desativada no PDO;
- helper `e()` para escape contextual em HTML e respostas JSON serializadas;
- autorização executada no backend, com `empresa_id` em toda leitura e mutação de negócio;
- profissionais recebem um escopo adicional por `profissional_id`;
- CSP, proteção contra frames, MIME sniffing e política de referenciador;
- exceções técnicas são registradas em `storage/logs/app.log`; detalhes só aparecem com `APP_DEBUG=true`;
- registros históricos são inativados/cancelados, sem exclusão física de agendamentos.

Consulte [a arquitetura](docs/architecture.md), [a documentação completa do banco](docs/database.md) e [a auditoria do legado](docs/legacy-audit.md).

## Concorrência e conflitos

Todos os pontos de criação, internos ou públicos, passam por `AppointmentService`. O serviço adquire uma trava nomeada do MySQL para a combinação empresa/profissional/data, recalcula a disponibilidade dentro da transação e somente então insere. A trava é liberada em `finally`. Assim, duas solicitações simultâneas para o mesmo profissional/dia são serializadas. A regra de sobreposição é `novo_inicio < fim_existente AND novo_fim > inicio_existente`; duração e intervalos antes/depois também participam do bloqueio.

## Testes

Com o MySQL iniciado e o seed aplicado:

```powershell
C:\xampp\php\php.exe tests\run.php
```

O conjunto cobre sobreposição de intervalos, CSRF, senha incorreta, isolamento por empresa, escopo do profissional, durações distintas, bloqueios, jornada e ausência de dados de clientes na página pública.

## Troubleshooting

- **Conexão recusada:** inicie o MySQL no XAMPP e confirme `DB_HOST`/`DB_PORT`.
- **Página 404 em rotas limpas:** habilite `mod_rewrite` e `AllowOverride All` para `htdocs` no Apache.
- **Erro de extensão:** valide com `C:\xampp\php\php.exe -m` se `pdo_mysql` e `mbstring` estão ativos.
- **Migration já aplicada:** o comando é idempotente e registra versões na tabela `migrations`.
- **Página sem estilo:** confirme `APP_URL` exatamente com a URL/base usada no navegador.
- **Produção:** aponte o DocumentRoot para o projeto com as regras do `.htaccess`, negue acesso a arquivos de configuração e mantenha `storage` fora de exposição direta sempre que o provedor permitir.
