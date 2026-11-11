-- Configuração de entradas para o simulador de circuitos.
-- Rode uma única vez em bancos já existentes (não recrie o banco).
USE logic_gate;

ALTER TABLE fase
    ADD COLUMN entradas_quantidade TINYINT UNSIGNED NOT NULL DEFAULT 2,
    ADD COLUMN entradas_iniciais VARCHAR(8) NOT NULL DEFAULT '00';
