-- =====================================================================
-- L&R SISTEMA — Esquema de banco de dados (MySQL 5.7+/MariaDB 10.3+)
-- Compatível com hospedagem compartilhada (HostGator / cPanel MySQL)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USUÁRIOS (equipe interna: advogados e atendentes)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    senha_hash      VARCHAR(255) NOT NULL,
    cargo           ENUM('admin','advogado','atendente') NOT NULL DEFAULT 'atendente',
    oab             VARCHAR(30) NULL,
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- CLIENTES (clientes já convertidos / carteira ativa do escritório)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(200) NOT NULL,
    cpf_cnpj        VARCHAR(20) NULL,
    email           VARCHAR(150) NULL,
    telefone        VARCHAR(30) NULL,
    endereco        VARCHAR(255) NULL,
    observacoes     TEXT NULL,
    contato_origem_id INT UNSIGNED NULL, -- referência à pasta de Contatos que originou este cliente, se houver
    criado_por      INT UNSIGNED NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clientes_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_clientes_nome (nome),
    INDEX idx_clientes_cpf_cnpj (cpf_cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PROCESSOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS processos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id          INT UNSIGNED NOT NULL,
    numero_cnj          VARCHAR(25) NULL, -- número único CNJ do processo, usado para casar com publicações
    tipo_acao           VARCHAR(150) NULL,
    vara                VARCHAR(150) NULL,
    comarca             VARCHAR(150) NULL,
    tribunal            VARCHAR(100) NULL,
    advogado_responsavel_id INT UNSIGNED NULL,
    situacao            ENUM('ativo','suspenso','arquivado','encerrado') NOT NULL DEFAULT 'ativo',
    observacoes         TEXT NULL,
    criado_em            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_processos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    CONSTRAINT fk_processos_advogado FOREIGN KEY (advogado_responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_processos_numero_cnj (numero_cnj),
    INDEX idx_processos_situacao (situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- TIPOS DE PRAZO (catálogo configurável, ex: "Contestação", "Recurso")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tipos_prazo (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(120) NOT NULL,
    dias_padrao     SMALLINT UNSIGNED NULL, -- prazo padrão em dias, usado como sugestão ao criar
    conta_em_dias_uteis TINYINT(1) NOT NULL DEFAULT 1,
    cor             VARCHAR(7) NULL DEFAULT '#274B6D', -- cor de identificação na agenda
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PRAZOS (agenda vinculada a processos)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prazos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    processo_id         INT UNSIGNED NULL, -- pode ser nulo (ex: reunião não vinculada a processo)
    tipo_prazo_id       INT UNSIGNED NULL,
    titulo              VARCHAR(200) NOT NULL,
    descricao           TEXT NULL,
    data_limite         DATE NOT NULL,
    hora_limite         TIME NULL,
    responsavel_id       INT UNSIGNED NULL,
    origem_publicacao_id INT UNSIGNED NULL, -- se o prazo nasceu automaticamente de uma publicação
    status              ENUM('pendente','confirmado','cumprido','perdido','cancelado') NOT NULL DEFAULT 'pendente',
    criado_em           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prazos_processo FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE CASCADE,
    CONSTRAINT fk_prazos_tipo FOREIGN KEY (tipo_prazo_id) REFERENCES tipos_prazo(id) ON DELETE SET NULL,
    CONSTRAINT fk_prazos_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_prazos_data_limite (data_limite),
    INDEX idx_prazos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PUBLICAÇÕES (capturadas do Diário Oficial / DJEN / fontes terceirizadas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publicacoes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    processo_id         INT UNSIGNED NULL, -- vínculo automático por número CNJ, quando encontrado
    numero_cnj          VARCHAR(25) NULL,
    fonte               VARCHAR(100) NULL, -- ex: "DJEN", "TJSP"
    data_publicacao     DATE NOT NULL,
    conteudo            TEXT NOT NULL,
    prazo_sugerido_id   INT UNSIGNED NULL, -- prazo criado automaticamente a partir desta publicação
    revisado_por        INT UNSIGNED NULL, -- advogado que confirmou o prazo calculado
    revisado_em         DATETIME NULL,
    status              ENUM('novo','vinculado','revisado','ignorado') NOT NULL DEFAULT 'novo',
    criado_em           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_publicacoes_processo FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE SET NULL,
    CONSTRAINT fk_publicacoes_prazo FOREIGN KEY (prazo_sugerido_id) REFERENCES prazos(id) ON DELETE SET NULL,
    CONSTRAINT fk_publicacoes_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_publicacoes_numero_cnj (numero_cnj),
    INDEX idx_publicacoes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- MÓDULO CONTATOS (captação/pré-venda) — substitui Propostas/Contratos/
-- Procurações/Calculadora
-- =====================================================================

-- Roteiros de conversa reutilizáveis (script de captação)
CREATE TABLE IF NOT EXISTS contatos_roteiros (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo          VARCHAR(150) NOT NULL,
    conteudo        TEXT NOT NULL, -- texto do roteiro/script, pode ter etapas em markdown simples
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_por      INT UNSIGNED NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_roteiros_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pasta do cliente (lead em captação)
CREATE TABLE IF NOT EXISTS contatos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(200) NOT NULL,
    cpf_cnpj            VARCHAR(20) NULL,
    email               VARCHAR(150) NULL,
    telefone            VARCHAR(30) NULL,
    origem              ENUM('planilha','manual') NOT NULL DEFAULT 'manual',
    roteiro_id          INT UNSIGNED NULL,
    status              ENUM('novo','enviado','recusado','convertido') NOT NULL DEFAULT 'novo',
    atendente_atual_id  INT UNSIGNED NULL, -- atendente responsável pela captação no momento
    cliente_id          INT UNSIGNED NULL, -- preenchido quando status = convertido, aponta para o registro em `clientes`
    observacoes         TEXT NULL,
    criado_por          INT UNSIGNED NULL,
    criado_em           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contatos_roteiro FOREIGN KEY (roteiro_id) REFERENCES contatos_roteiros(id) ON DELETE SET NULL,
    CONSTRAINT fk_contatos_atendente FOREIGN KEY (atendente_atual_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_contatos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_contatos_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_contatos_status (status),
    INDEX idx_contatos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registro de auditoria da pasta (quem cadastrou, enviou 1ª mensagem, mudou status, editou dados...)
CREATE TABLE IF NOT EXISTS contatos_auditoria (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contato_id      INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NULL,
    acao            VARCHAR(100) NOT NULL, -- ex: 'cadastrou', 'enviou_primeira_mensagem', 'mudou_status', 'editou_dados'
    detalhes        TEXT NULL,
    criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_contato FOREIGN KEY (contato_id) REFERENCES contatos(id) ON DELETE CASCADE,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_auditoria_contato (contato_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trocas de mensagem com o cliente + rascunho de resposta gerado por IA
CREATE TABLE IF NOT EXISTS contatos_mensagens (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contato_id          INT UNSIGNED NOT NULL,
    mensagem_cliente    TEXT NOT NULL, -- texto colado pelo atendente, recebido do cliente
    resposta_sugerida_ia TEXT NULL,     -- rascunho gerado pela IA
    resposta_utilizada   TEXT NULL,     -- o que o atendente de fato enviou (pode editar a sugestão)
    usuario_id          INT UNSIGNED NULL,
    criado_em           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensagens_contato FOREIGN KEY (contato_id) REFERENCES contatos(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensagens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DADOS INICIAIS (seed mínimo para o sistema abrir funcional)
-- =====================================================================

-- Usuário admin inicial — troque a senha no primeiro acesso.
-- Senha inicial: "trocaresta123" (hash gerado com password_hash/PASSWORD_BCRYPT)
INSERT INTO usuarios (nome, email, senha_hash, cargo, ativo) VALUES
('Administrador', 'admin@lr.adv.br', '$2y$12$IQuudu/ij31Pux7D8sbF7OJN1R/ld/2n10onhwEX69Q1JjcZBeL/6', 'admin', 1);

INSERT INTO tipos_prazo (nome, dias_padrao, conta_em_dias_uteis, cor) VALUES
('Contestação', 15, 1, '#274B6D'),
('Recurso', 15, 1, '#A85D3F'),
('Manifestação', 5, 1, '#4C7A52'),
('Audiência', NULL, 0, '#C9A15B');

INSERT INTO contatos_roteiros (titulo, conteudo, ativo) VALUES
('Roteiro padrão de captação', '1. Apresentação e confirmação do interesse.\n2. Levantar dados básicos (nome, telefone, situação).\n3. Explicar como o escritório pode ajudar.\n4. Encaminhar próximos passos e prazo de retorno.', 1);
