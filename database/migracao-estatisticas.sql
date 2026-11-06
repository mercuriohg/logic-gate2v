-- Migração: estatísticas do painel admin e tempo médio no perfil.
-- Use SOMENTE se o banco já existe e você não quer recriá-lo (docker compose down -v).
-- Rode uma única vez (o MySQL não aceita ADD COLUMN repetido).
USE logic_gate;

ALTER TABLE usuario
    ADD COLUMN ultimo_acesso DATETIME NULL;

ALTER TABLE progresso
    ADD COLUMN data_inicio DATETIME NULL,
    ADD COLUMN tempo_segundos INT NULL;
