CREATE TABLE empresas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nome_fantasia VARCHAR(150) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    segmento VARCHAR(100) NOT NULL,
    telefone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    logo VARCHAR(255) NULL,
    cor_principal CHAR(7) NOT NULL DEFAULT '#5b5bd6',
    timezone VARCHAR(64) NOT NULL DEFAULT 'America/Sao_Paulo',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresas_ativo (ativo)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil VARCHAR(30) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    ultimo_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT chk_usuarios_perfil CHECK (perfil IN ('proprietario','administrador','profissional')),
    UNIQUE KEY uq_usuarios_email (email),
    INDEX idx_usuarios_empresa_ativo (empresa_id, ativo)
) ENGINE=InnoDB;

CREATE TABLE profissionais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    descricao TEXT NULL,
    especialidade VARCHAR(120) NULL,
    foto VARCHAR(255) NULL,
    cor_agenda CHAR(7) NOT NULL DEFAULT '#5b5bd6',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_profissionais_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT fk_profissionais_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_profissional_usuario (usuario_id),
    INDEX idx_profissionais_empresa_ativo (empresa_id, ativo)
) ENGINE=InnoDB;

CREATE TABLE servicos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    duracao_minutos SMALLINT UNSIGNED NOT NULL,
    preco DECIMAL(10,2) NULL,
    intervalo_antes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    intervalo_depois SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    cor CHAR(7) NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_servicos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT chk_servicos_duracao CHECK (duracao_minutos BETWEEN 5 AND 1440),
    UNIQUE KEY uq_servicos_empresa_nome (empresa_id, nome),
    INDEX idx_servicos_empresa_ativo (empresa_id, ativo)
) ENGINE=InnoDB;

CREATE TABLE profissional_servico (
    empresa_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    servico_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (profissional_id, servico_id),
    CONSTRAINT fk_ps_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT fk_ps_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE,
    INDEX idx_ps_empresa_servico (empresa_id, servico_id)
) ENGINE=InnoDB;

CREATE TABLE clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    data_nascimento DATE NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_clientes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_clientes_empresa_nome (empresa_id, nome),
    INDEX idx_clientes_empresa_telefone (empresa_id, telefone),
    INDEX idx_clientes_empresa_email (empresa_id, email)
) ENGINE=InnoDB;

CREATE TABLE horarios_profissional (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_horarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT fk_horarios_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE CASCADE,
    CONSTRAINT chk_horarios_dia CHECK (dia_semana BETWEEN 0 AND 6),
    CONSTRAINT chk_horarios_intervalo CHECK (hora_inicio < hora_fim),
    UNIQUE KEY uq_horario_periodo (profissional_id, dia_semana, hora_inicio, hora_fim),
    INDEX idx_horarios_empresa_profissional (empresa_id, profissional_id, dia_semana)
) ENGINE=InnoDB;

CREATE TABLE bloqueios_agenda (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    inicio_at DATETIME NOT NULL,
    fim_at DATETIME NOT NULL,
    motivo VARCHAR(255) NULL,
    dia_inteiro BOOLEAN NOT NULL DEFAULT FALSE,
    criado_por BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    canceled_at DATETIME NULL,
    CONSTRAINT fk_bloqueios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT fk_bloqueios_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id),
    CONSTRAINT fk_bloqueios_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    CONSTRAINT chk_bloqueios_intervalo CHECK (inicio_at < fim_at),
    INDEX idx_bloqueios_conflito (empresa_id, profissional_id, inicio_at, fim_at)
) ENGINE=InnoDB;

CREATE TABLE agendamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    servico_id BIGINT UNSIGNED NOT NULL,
    inicio_at DATETIME NOT NULL,
    fim_at DATETIME NOT NULL,
    duracao_minutos SMALLINT UNSIGNED NOT NULL,
    preco_registrado DECIMAL(10,2) NULL,
    origem VARCHAR(30) NOT NULL DEFAULT 'interno',
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    observacoes TEXT NULL,
    criado_por BIGINT UNSIGNED NULL,
    cancelado_por BIGINT UNSIGNED NULL,
    cancelado_at DATETIME NULL,
    motivo_cancelamento VARCHAR(255) NULL,
    origem_cancelamento VARCHAR(30) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_agendamentos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT fk_agendamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_agendamentos_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id),
    CONSTRAINT fk_agendamentos_servico FOREIGN KEY (servico_id) REFERENCES servicos(id),
    CONSTRAINT fk_agendamentos_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_agendamentos_cancelador FOREIGN KEY (cancelado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT chk_agendamentos_intervalo CHECK (inicio_at < fim_at),
    CONSTRAINT chk_agendamentos_status CHECK (status IN ('pendente','confirmado','em_atendimento','concluido','cancelado','nao_compareceu')),
    INDEX idx_agenda_conflito (empresa_id, profissional_id, inicio_at, fim_at, status),
    INDEX idx_agenda_cliente (empresa_id, cliente_id, inicio_at),
    INDEX idx_agenda_dashboard (empresa_id, inicio_at, status)
) ENGINE=InnoDB;

CREATE TABLE configuracoes_empresa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    intervalo_padrao SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    antecedencia_minima_minutos INT UNSIGNED NOT NULL DEFAULT 60,
    maximo_dias_futuros SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    permitir_agendamento_publico BOOLEAN NOT NULL DEFAULT TRUE,
    confirmar_automaticamente BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_config_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    UNIQUE KEY uq_config_empresa (empresa_id)
) ENGINE=InnoDB;

CREATE TABLE logs_auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    acao VARCHAR(80) NOT NULL,
    entidade VARCHAR(80) NOT NULL,
    entidade_id BIGINT UNSIGNED NULL,
    detalhes JSON NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_auditoria_empresa_data (empresa_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE tentativas_login (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NULL,
    email_hash CHAR(64) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    sucesso BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tentativas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL,
    INDEX idx_tentativas_limite (email_hash, ip, created_at)
) ENGINE=InnoDB;

CREATE TABLE limites_requisicao (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave_hash CHAR(64) NOT NULL,
    contexto VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_limites_janela (chave_hash, contexto, created_at)
) ENGINE=InnoDB;
