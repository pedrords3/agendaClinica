# Banco de dados da plataforma de agendamentos

Este documento descreve o banco **`plataforma_agendamentos`**, criado pela migration [`001_initial_schema.sql`](../database/migrations/001_initial_schema.sql). O nome correto do banco está no plural: `plataforma_agendamentos`.

## Visão geral

O banco possui 14 tabelas. Treze fazem parte do domínio da aplicação e a tabela `migrations` controla quais migrations já foram executadas.

| Grupo | Tabelas |
|---|---|
| Empresa e acesso | `empresas`, `configuracoes_empresa`, `usuarios` |
| Catálogo e equipe | `profissionais`, `servicos`, `profissional_servico` |
| Agenda | `clientes`, `horarios_profissional`, `bloqueios_agenda`, `agendamentos` |
| Segurança e rastreabilidade | `logs_auditoria`, `tentativas_login`, `limites_requisicao` |
| Infraestrutura | `migrations` |

### Relacionamentos principais

```text
empresas
├── usuarios
├── profissionais ──┐
│   ├── horarios_profissional
│   └── bloqueios_agenda
├── servicos ───────┤
│                   └── profissional_servico
├── clientes
├── agendamentos
│   ├── cliente_id       → clientes
│   ├── profissional_id  → profissionais
│   ├── servico_id       → servicos
│   ├── criado_por       → usuarios
│   └── cancelado_por    → usuarios
├── configuracoes_empresa
├── logs_auditoria
└── tentativas_login
```

Uma empresa possui seus próprios usuários, profissionais, serviços, clientes e agendamentos. O campo `empresa_id` é a fronteira de segurança: uma operação nunca deve localizar um registro apenas por `id`; deve combinar `id` e `empresa_id`.

## Convenções usadas no banco

- `BIGINT UNSIGNED`: identificador numérico positivo.
- `AUTO_INCREMENT`: o banco gera o próximo ID.
- `PRIMARY KEY`: identificador único da linha.
- `FOREIGN KEY`: relacionamento validado pelo banco.
- `UNIQUE`: valor ou combinação que não pode se repetir.
- `INDEX`: estrutura que acelera consultas frequentes.
- `NULL`: campo opcional.
- `BOOLEAN`: no MariaDB é armazenado como `0` ou `1`.
- `created_at`: momento da criação.
- `updated_at`: atualizado automaticamente quando a linha é alterada.
- `deleted_at`: exclusão lógica. Quando preenchido, o registro é tratado como removido sem perder histórico.
- Campos `inicio_at` e `fim_at` de agendamentos e bloqueios são armazenados em UTC. A interface converte para o fuso da empresa.

## 1. `empresas`

É a tabela central do modelo multiempresa. Cada registro representa um estabelecimento ou profissional autônomo.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador da empresa. |
| `nome` | VARCHAR(150) | obrigatório | Razão social ou nome interno. |
| `nome_fantasia` | VARCHAR(150) | obrigatório | Nome apresentado no sistema e na página pública. |
| `slug` | VARCHAR(120) | obrigatório, único | Parte do link público, por exemplo `studio-demo`. |
| `segmento` | VARCHAR(100) | obrigatório | Barbearia, psicologia, odontologia etc. |
| `telefone` | VARCHAR(30) | opcional | Telefone do estabelecimento. |
| `whatsapp` | VARCHAR(30) | opcional | WhatsApp comercial. |
| `email` | VARCHAR(190) | opcional | E-mail comercial. |
| `endereco` | VARCHAR(255) | opcional | Endereço em formato livre. |
| `cidade` | VARCHAR(100) | opcional | Cidade. |
| `estado` | CHAR(2) | opcional | UF, como `SP`. |
| `logo` | VARCHAR(255) | opcional | Caminho de uma futura imagem de logo. |
| `cor_principal` | CHAR(7) | padrão `#5b5bd6` | Cor hexadecimal usada na identidade visual. |
| `timezone` | VARCHAR(64) | padrão `America/Sao_Paulo` | Fuso usado para interpretar e exibir horários. |
| `ativo` | BOOLEAN | padrão verdadeiro | Controla se a empresa pode operar. |
| `created_at` | TIMESTAMP | automático | Data de criação. |
| `updated_at` | TIMESTAMP | automático | Última alteração. |

Relacionamentos: uma empresa possui muitos usuários, profissionais, serviços, clientes, horários, bloqueios, agendamentos e logs. Possui exatamente uma linha em `configuracoes_empresa`.

## 2. `usuarios`

Contém as contas que podem entrar no painel administrativo. Um usuário sempre pertence a uma empresa.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do usuário. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa à qual a conta pertence. |
| `nome` | VARCHAR(150) | obrigatório | Nome exibido no painel. |
| `email` | VARCHAR(190) | obrigatório, único | Identificador usado no login. |
| `senha_hash` | VARCHAR(255) | obrigatório | Hash criado por `password_hash`; nunca contém a senha original. |
| `perfil` | VARCHAR(30) | constraint | `proprietario`, `administrador` ou `profissional`. |
| `ativo` | BOOLEAN | padrão verdadeiro | Permite bloquear o acesso sem apagar a conta. |
| `ultimo_login_at` | DATETIME | opcional | Data do último login bem-sucedido. |
| `created_at` | TIMESTAMP | automático | Criação da conta. |
| `updated_at` | TIMESTAMP | automático | Última alteração. |
| `deleted_at` | DATETIME | opcional | Exclusão lógica. |

O e-mail é único em toda a plataforma, não apenas dentro da empresa. Se o usuário tiver perfil profissional, ele pode ser associado a uma linha de `profissionais` por meio de `profissionais.usuario_id`.

## 3. `profissionais`

Representa quem presta os serviços e possui agenda própria.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do profissional. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa proprietária do cadastro. |
| `usuario_id` | BIGINT | FK, opcional, único | Conta usada pelo profissional para entrar no painel. |
| `nome` | VARCHAR(150) | obrigatório | Nome do profissional. |
| `telefone` | VARCHAR(30) | opcional | Telefone. |
| `email` | VARCHAR(190) | opcional | E-mail de contato. |
| `descricao` | TEXT | opcional | Apresentação ou informações públicas. |
| `especialidade` | VARCHAR(120) | opcional | Cargo, função ou especialidade. |
| `foto` | VARCHAR(255) | opcional | Caminho de uma futura foto. |
| `cor_agenda` | CHAR(7) | padrão `#5b5bd6` | Cor usada no calendário. |
| `ativo` | BOOLEAN | padrão verdadeiro | Define se pode receber novos agendamentos. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |
| `deleted_at` | DATETIME | opcional | Exclusão lógica. |

Cada profissional pode oferecer vários serviços, possuir vários períodos semanais, bloqueios e agendamentos. `usuario_id` usa `ON DELETE SET NULL`: apagar a conta não apaga o profissional nem seu histórico.

## 4. `servicos`

É o catálogo de atendimentos vendidos ou oferecidos pela empresa.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do serviço. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa proprietária. |
| `nome` | VARCHAR(150) | obrigatório | Nome do serviço. É único dentro da empresa. |
| `descricao` | TEXT | opcional | Explicação do serviço. |
| `duracao_minutos` | SMALLINT | 5 a 1440 | Tempo efetivo do atendimento. |
| `preco` | DECIMAL(10,2) | opcional | Preço atual do serviço. |
| `intervalo_antes` | SMALLINT | padrão 0 | Tempo de preparação reservado antes do atendimento. |
| `intervalo_depois` | SMALLINT | padrão 0 | Tempo de finalização reservado depois do atendimento. |
| `cor` | CHAR(7) | opcional | Cor visual do serviço. |
| `ativo` | BOOLEAN | padrão verdadeiro | Controla a oferta para novos agendamentos. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |
| `deleted_at` | DATETIME | opcional | Exclusão lógica. |

O mesmo nome de serviço não pode se repetir na mesma empresa, mas empresas diferentes podem usar nomes iguais.

### Duração versus intervalo antes/depois

Os três campos reservam tempo por motivos diferentes:

| Campo | O cliente está sendo atendido? | Exemplo |
|---|---|---|
| `duracao_minutos` | Sim | Corte das 10:00 às 10:30. |
| `intervalo_antes` | Não | Preparar sala/material antes das 10:00. |
| `intervalo_depois` | Não | Limpar, organizar ou fazer anotações depois das 10:30. |

Exemplo de um serviço configurado assim:

```text
Duração:           30 minutos
Intervalo antes:   10 minutos
Intervalo depois:  15 minutos
Agendamento:       10:00 às 10:30
```

A ocupação real do profissional fica:

```text
09:50        10:00                    10:30        10:45
  │ preparação │ atendimento do cliente │ finalização │
  └──────────────── horário bloqueado ────────────────┘
```

Para o cliente, o atendimento continua aparecendo como **10:00–10:30**. Entretanto, nenhum outro serviço poderá usar o profissional entre **09:50 e 10:45**.

Se o próximo serviço também exigir 10 minutos de preparação, o seu atendimento só poderá começar às 10:55:

```text
Serviço anterior termina de liberar a agenda: 10:45
Preparação do próximo serviço:                 10:45–10:55
Próximo cliente começa:                        10:55
```

Intervalos adjacentes não são conflito. Um bloqueio que termina exatamente às 10:45 permite que outro período reservado comece às 10:45.

#### Sugestões práticas

| Negócio/serviço | Antes | Depois | Possível finalidade |
|---|---:|---:|---|
| Corte simples | 0 | 5 | Limpar cadeira e instrumentos. |
| Coloração | 10 | 15 | Separar produtos e higienizar. |
| Consulta online | 0 | 0 | Sem preparação física. |
| Psicoterapia | 0 | 10 | Anotações e pausa entre sessões. |
| Procedimento odontológico | 10 | 15 | Preparação e esterilização da sala. |
| Massagem | 10 | 15 | Preparar e reorganizar o ambiente. |

Se o negócio não precisa desses tempos, mantenha ambos em `0`.

#### Efeito nos limites da jornada

Os intervalos também precisam caber dentro da jornada. Com expediente começando às 08:00 e `intervalo_antes = 10`, o primeiro horário oferecido ao cliente será 08:10, pois 08:00–08:10 ficará reservado para preparação. Da mesma forma, atendimento mais intervalo posterior precisam terminar antes do fim do período de trabalho.

#### Não confundir com `intervalo_padrao`

`configuracoes_empresa.intervalo_padrao` controla a grade de possíveis inícios. Com valor 15, o sistema tenta gerar opções a cada 15 minutos; com valor 30, tenta a cada 30 minutos. Ele não representa limpeza ou preparação.

Exemplo com grade de 15 minutos:

```text
08:00, 08:15, 08:30, 08:45, 09:00...
```

Depois, a aplicação remove as opções que não comportam duração, intervalos, jornada, bloqueios ou outros agendamentos.

#### Comportamento atual ao alterar um serviço

Ao criar um agendamento, `duracao_minutos` e `preco` são copiados para `agendamentos.duracao_minutos` e `agendamentos.preco_registrado`. Assim, alterações futuras não mudam a duração e o preço registrados naquela reserva.

Os intervalos antes/depois ainda não são copiados. A disponibilidade consulta os valores atuais em `servicos`. Portanto, alterar os intervalos de um serviço pode aumentar ou reduzir o espaço protegido ao redor de agendamentos futuros já existentes desse serviço. Uma evolução possível é adicionar `intervalo_antes_registrado` e `intervalo_depois_registrado` em `agendamentos` para congelar também esses valores.

## 5. `profissional_servico`

Tabela de associação muitos-para-muitos entre profissionais e serviços.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `empresa_id` | BIGINT | FK | Garante o contexto da empresa. |
| `profissional_id` | BIGINT | PK composta, FK | Profissional. |
| `servico_id` | BIGINT | PK composta, FK | Serviço oferecido. |
| `created_at` | TIMESTAMP | automático | Momento da associação. |

A chave primária composta impede associar o mesmo serviço duas vezes ao mesmo profissional. Ao excluir fisicamente profissional ou serviço, as respectivas associações são removidas por `ON DELETE CASCADE`.

Exemplo:

```text
Ana    → Corte
Ana    → Barba
Carlos → Corte
```

## 6. `clientes`

Cadastro único de pessoas que realizam agendamentos.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do cliente. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa que possui o relacionamento. |
| `nome` | VARCHAR(150) | obrigatório | Nome. |
| `telefone` | VARCHAR(30) | obrigatório | Principal dado de contato/localização. |
| `whatsapp` | VARCHAR(30) | opcional | WhatsApp. |
| `email` | VARCHAR(190) | opcional | E-mail. |
| `data_nascimento` | DATE | opcional | Data de nascimento sem dados clínicos. |
| `ativo` | BOOLEAN | padrão verdadeiro | Ativa/inativa o cadastro. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |
| `deleted_at` | DATETIME | opcional | Exclusão lógica. |

Telefone e e-mail possuem índices para busca, mas não são `UNIQUE`. A aplicação tenta reutilizar um cliente com o mesmo telefone ou e-mail ao receber um agendamento público.

## 7. `horarios_profissional`

Define a jornada semanal recorrente de cada profissional.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do período. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa. |
| `profissional_id` | BIGINT | FK, obrigatório | Dono da agenda. |
| `dia_semana` | TINYINT | 0 a 6 | Dia da semana. |
| `hora_inicio` | TIME | obrigatório | Início local do período. |
| `hora_fim` | TIME | obrigatório, maior que início | Fim local do período. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |

Mapeamento de `dia_semana`:

| Valor | Dia |
|---:|---|
| 0 | Domingo |
| 1 | Segunda-feira |
| 2 | Terça-feira |
| 3 | Quarta-feira |
| 4 | Quinta-feira |
| 5 | Sexta-feira |
| 6 | Sábado |

Pode haver vários períodos no mesmo dia, por exemplo 08:00–12:00 e 13:30–18:00. A aplicação rejeita períodos sobrepostos. Ao excluir fisicamente o profissional, os horários são removidos em cascata.

## 8. `bloqueios_agenda`

Registra exceções à jornada: férias, reuniões, feriados, ausências e compromissos.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do bloqueio. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa. |
| `profissional_id` | BIGINT | FK, obrigatório | Agenda bloqueada. |
| `inicio_at` | DATETIME | obrigatório, UTC | Início do bloqueio. |
| `fim_at` | DATETIME | obrigatório, UTC, maior que início | Fim do bloqueio. |
| `motivo` | VARCHAR(255) | opcional | Explicação interna. |
| `dia_inteiro` | BOOLEAN | padrão falso | Indica bloqueio de dia inteiro. |
| `criado_por` | BIGINT | FK, obrigatório | Usuário que criou o bloqueio. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |
| `canceled_at` | DATETIME | opcional | Cancelamento lógico do bloqueio. |

Bloqueios ativos participam do cálculo de disponibilidade. Um registro com `canceled_at` preenchido deixa de bloquear a agenda, preservando o histórico.

## 9. `agendamentos`

É a tabela transacional principal. Liga cliente, profissional e serviço a um intervalo de data/hora.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do agendamento. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa proprietária. |
| `cliente_id` | BIGINT | FK, obrigatório | Cliente atendido. |
| `profissional_id` | BIGINT | FK, obrigatório | Profissional reservado. |
| `servico_id` | BIGINT | FK, obrigatório | Serviço reservado. |
| `inicio_at` | DATETIME | obrigatório, UTC | Início efetivo do atendimento. |
| `fim_at` | DATETIME | obrigatório, UTC | Fim efetivo do atendimento. |
| `duracao_minutos` | SMALLINT | obrigatório | Cópia da duração no momento da reserva. |
| `preco_registrado` | DECIMAL(10,2) | opcional | Cópia do preço no momento da reserva. |
| `origem` | VARCHAR(30) | padrão `interno` | Canal pelo qual a reserva chegou. |
| `status` | VARCHAR(30) | constraint | Situação atual. |
| `observacoes` | TEXT | opcional | Nota operacional, sem prontuário clínico. |
| `criado_por` | BIGINT | FK, opcional | Usuário interno criador; fica nulo na reserva pública. |
| `cancelado_por` | BIGINT | FK, opcional | Usuário responsável pelo cancelamento. |
| `cancelado_at` | DATETIME | opcional | Momento do cancelamento. |
| `motivo_cancelamento` | VARCHAR(255) | opcional | Motivo. |
| `origem_cancelamento` | VARCHAR(30) | opcional | Canal do cancelamento. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |

Status permitidos:

| Status | Significado |
|---|---|
| `pendente` | Criado, aguardando confirmação. |
| `confirmado` | Atendimento confirmado. |
| `em_atendimento` | Atendimento iniciado. |
| `concluido` | Atendimento finalizado. |
| `cancelado` | Cancelado, sem exclusão física. |
| `nao_compareceu` | Cliente não compareceu. |

Origens usadas atualmente: `interno`, `telefone`, `whatsapp`, `recepcao` e `link_publico`. A coluna aceita futuras origens, mas controllers validam as opções permitidas em cada fluxo.

Agendamentos cancelados não bloqueiam disponibilidade. Os demais estados bloqueiam o período. Antes de inserir, a aplicação adquire uma trava exclusiva por empresa, profissional e data, recalcula a disponibilidade dentro de uma transação e só então grava a reserva.

## 10. `configuracoes_empresa`

Guarda regras operacionais que variam por empresa.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador interno. |
| `empresa_id` | BIGINT | FK, único | Garante uma configuração por empresa. |
| `intervalo_padrao` | SMALLINT | padrão 15 | Passo, em minutos, usado para gerar possíveis inícios. |
| `antecedencia_minima_minutos` | INT | padrão 60 | Tempo mínimo entre agora e um novo agendamento. |
| `maximo_dias_futuros` | SMALLINT | padrão 60 | Horizonte máximo de datas disponíveis. |
| `permitir_agendamento_publico` | BOOLEAN | padrão verdadeiro | Ativa/desativa o link público. |
| `confirmar_automaticamente` | BOOLEAN | padrão falso | Define se reserva pública nasce confirmada ou pendente. |
| `created_at`, `updated_at` | TIMESTAMP | automáticos | Controle temporal. |

## 11. `logs_auditoria`

Registra ações importantes para rastreabilidade.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do evento. |
| `empresa_id` | BIGINT | FK, obrigatório | Empresa do evento. |
| `usuario_id` | BIGINT | FK, opcional | Usuário responsável, quando existir. |
| `acao` | VARCHAR(80) | obrigatório | Ex.: `agendamento.criado`. |
| `entidade` | VARCHAR(80) | obrigatório | Tipo do recurso afetado. |
| `entidade_id` | BIGINT | opcional | ID do recurso. |
| `detalhes` | JSON | opcional | Metadados não secretos da ação. |
| `ip` | VARCHAR(45) | opcional | IP relacionado à ação. |
| `created_at` | TIMESTAMP | automático | Momento do evento. |

Senhas, tokens e hashes de senha não devem ser gravados em `detalhes`. Se o usuário for removido fisicamente, `usuario_id` passa a nulo e o evento permanece.

## 12. `tentativas_login`

Suporta a proteção contra força bruta no login.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador da tentativa. |
| `empresa_id` | BIGINT | FK, opcional | Empresa, quando o e-mail foi localizado. |
| `email_hash` | CHAR(64) | obrigatório | SHA-256 do e-mail normalizado; evita registrar o e-mail em texto. |
| `ip` | VARCHAR(45) | obrigatório | Endereço de origem. |
| `sucesso` | BOOLEAN | padrão falso | Resultado da tentativa. |
| `created_at` | TIMESTAMP | automático | Momento da tentativa. |

O login é bloqueado temporariamente quando a combinação de e-mail e IP excede o limite de falhas na janela configurada pela aplicação.

## 13. `limites_requisicao`

Rate limiter genérico usado em disponibilidade pública, criação de agendamentos públicos e cadastro de empresas.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador do evento. |
| `chave_hash` | CHAR(64) | obrigatório | Hash da chave, normalmente composta de IP e contexto. |
| `contexto` | VARCHAR(40) | obrigatório | Operação limitada, como `public_booking`. |
| `created_at` | TIMESTAMP | automático | Momento contado na janela do limite. |

Registros antigos são removidos ocasionalmente pela aplicação. Esta tabela não contém o conteúdo dos formulários.

## 14. `migrations`

Criada pelo executor [`database/migrate.php`](../database/migrate.php), controla migrations já aplicadas.

| Campo | Tipo | Regra | Função |
|---|---|---|---|
| `id` | BIGINT | PK | Identificador da execução. |
| `migration` | VARCHAR(255) | único | Nome do arquivo aplicado, como `001_initial_schema.sql`. |
| `executed_at` | TIMESTAMP | automático | Momento da aplicação. |

Não apague linhas dessa tabela manualmente. Se uma linha for removida, o migrador tentará executar novamente um arquivo que pode já ter criado suas tabelas.

## Como a disponibilidade é calculada

Para uma data, serviço e profissional, a aplicação executa conceitualmente estas etapas:

1. valida se profissional e serviço pertencem à empresa;
2. valida a associação em `profissional_servico`;
3. lê os períodos de `horarios_profissional` para o dia da semana;
4. gera candidatos usando `configuracoes_empresa.intervalo_padrao`;
5. aplica `servicos.duracao_minutos`, `intervalo_antes` e `intervalo_depois`;
6. elimina horários no passado ou fora da antecedência/horizonte permitidos;
7. elimina sobreposições com `bloqueios_agenda`;
8. elimina sobreposições com `agendamentos` não cancelados;
9. retorna ao navegador somente os horários livres;
10. no momento da confirmação, repete todo o cálculo no backend sob trava e transação.

A regra de sobreposição usada é:

```text
novo_início_bloqueado < fim_existente
E
novo_fim_bloqueado > início_existente
```

Essa regra detecta sobreposição parcial, total ou contida, e permite períodos apenas adjacentes.

## Exclusão e preservação de histórico

- `usuarios`, `profissionais`, `servicos` e `clientes` devem ser inativados ou receber `deleted_at` em vez de serem apagados quando possuem histórico.
- `agendamentos` cancelados permanecem na tabela com status e informações de cancelamento.
- `bloqueios_agenda` podem ser cancelados por `canceled_at`.
- Cascatas existem somente nas associações e horários cuja remoção acompanha naturalmente o profissional/serviço.

## Consultas úteis no phpMyAdmin

Contar registros por tabela:

```sql
SELECT 'empresas' AS tabela, COUNT(*) AS total FROM empresas
UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios
UNION ALL SELECT 'profissionais', COUNT(*) FROM profissionais
UNION ALL SELECT 'servicos', COUNT(*) FROM servicos
UNION ALL SELECT 'clientes', COUNT(*) FROM clientes
UNION ALL SELECT 'agendamentos', COUNT(*) FROM agendamentos;
```

Ver serviços e seus intervalos:

```sql
SELECT
    id,
    nome,
    duracao_minutos,
    intervalo_antes,
    intervalo_depois,
    duracao_minutos + intervalo_antes + intervalo_depois AS ocupacao_total_minutos
FROM servicos
ORDER BY nome;
```

Ver agendamentos com nomes relacionados:

```sql
SELECT
    a.id,
    e.nome_fantasia AS empresa,
    c.nome AS cliente,
    p.nome AS profissional,
    s.nome AS servico,
    a.inicio_at,
    a.fim_at,
    a.status
FROM agendamentos a
JOIN empresas e ON e.id = a.empresa_id
JOIN clientes c ON c.id = a.cliente_id
JOIN profissionais p ON p.id = a.profissional_id
JOIN servicos s ON s.id = a.servico_id
ORDER BY a.inicio_at DESC;
```

Essas consultas são apenas para inspeção. Alterações operacionais devem ser feitas pela aplicação para que validações, isolamento e auditoria sejam respeitados.
