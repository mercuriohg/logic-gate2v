-- ============================================
-- Logic Gate
-- init.sql - MySQL
-- ============================================

DROP DATABASE IF EXISTS logic_gate;
CREATE DATABASE logic_gate
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE logic_gate;

-- ============================================
-- TABELA USUARIO
-- ============================================

CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    xp_total INT NOT NULL DEFAULT 0,
    nivel INT NOT NULL DEFAULT 1,
    ultimo_acesso DATETIME NULL,

    CHECK (xp_total >= 0),
    CHECK (nivel >= 1)
) ENGINE=InnoDB;

-- ============================================
-- TABELA ADMIN
-- ============================================

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ============================================
-- TABELA PORTA LOGICA
-- ============================================

CREATE TABLE porta_logica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(10) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    tabela_verdade VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ============================================
-- TABELA FASE
-- ============================================

CREATE TABLE fase (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    ordem INT NOT NULL UNIQUE,
    dificuldade ENUM('facil','medio','dificil') NOT NULL,
    enunciado TEXT NOT NULL,
    objetivo TINYINT NOT NULL,
    xp_recompensa INT NOT NULL DEFAULT 0,
    pontos_recompensa INT NOT NULL DEFAULT 0,
    admin_id INT NOT NULL,

    CONSTRAINT chk_objetivo
        CHECK (objetivo IN (0,1)),

    CONSTRAINT fk_fase_admin
        FOREIGN KEY (admin_id)
        REFERENCES admin(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABELA FASE_PORTA
-- ============================================

CREATE TABLE fase_porta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fase_id INT NOT NULL,
    porta_id INT NOT NULL,
    quantidade_disponivel INT NOT NULL,

    CONSTRAINT chk_quantidade
        CHECK (quantidade_disponivel > 0),

    CONSTRAINT fk_faseporta_fase
        FOREIGN KEY (fase_id)
        REFERENCES fase(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_faseporta_porta
        FOREIGN KEY (porta_id)
        REFERENCES porta_logica(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE (fase_id, porta_id)
) ENGINE=InnoDB;

-- ============================================
-- TABELA PROGRESSO
-- ============================================

CREATE TABLE progresso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fase_id INT NOT NULL,
    desbloqueada BOOLEAN NOT NULL DEFAULT FALSE,
    concluida BOOLEAN NOT NULL DEFAULT FALSE,
    xp_ganho INT NOT NULL DEFAULT 0,
    pontos_ganhos INT NOT NULL DEFAULT 0,
    tentativas INT NOT NULL DEFAULT 0,
    data_conclusao DATETIME NULL,
    data_inicio DATETIME NULL,
    tempo_segundos INT NULL,

    CONSTRAINT fk_progresso_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuario(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_progresso_fase
        FOREIGN KEY (fase_id)
        REFERENCES fase(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE (usuario_id, fase_id)
) ENGINE=InnoDB;

-- ============================================
-- PORTAS LÓGICAS PADRÃO
-- ============================================

INSERT INTO porta_logica (nome, descricao, tabela_verdade) VALUES
('AND', 'Retorna 1 somente quando ambas as entradas são 1.', '00→0 | 01→0 | 10→0 | 11→1'),

('OR', 'Retorna 1 quando pelo menos uma entrada é 1.', '00→0 | 01→1 | 10→1 | 11→1'),

('NOT', 'Inverte o valor da entrada.', '0→1 | 1→0'),

('XOR', 'Retorna 1 quando as entradas são diferentes.', '00→0 | 01→1 | 10→1 | 11→0'),

('NAND', 'Inverso da AND.', '00→1 | 01→1 | 10→1 | 11→0'),

('NOR', 'Inverso da OR.', '00→1 | 01→0 | 10→0 | 11→0'),

('XNOR', 'Retorna 1 quando as entradas são iguais.', '00→1 | 01→0 | 10→0 | 11→1');

-- ============================================
-- ADMIN PADRÃO (opcional)
-- Login: admin | Senha: admin123  (TROQUE em produção — veja src/scripts/criar-admin.php)
-- ============================================

INSERT INTO admin (login, senha_hash)
VALUES ('admin', '$2y$10$UmSwouXlIBAS5pNLeTwLr.pgHkl0fGrv7opbeA8yL2Zp9NTYl6laa');