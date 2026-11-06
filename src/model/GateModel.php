<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/** Catálogo das 7 portas lógicas (tabela `porta_logica`, só leitura). */
final class GateModel
{
    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? Database::getConnection();
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT id, nome, descricao, tabela_verdade FROM porta_logica ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Quantos dos ids informados existem no catálogo. */
    public function countExisting(array $ids): int
    {
        if (!$ids) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM porta_logica WHERE id IN ($in)");
        $stmt->execute(array_values($ids));
        return (int) $stmt->fetchColumn();
    }
}
