<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/** Login exclusivo do administrador (tabela `admin`, separada de `usuario`). */
final class AdminModel
{
    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? Database::getConnection();
    }

    public function getByLogin(string $login): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM admin WHERE login = :login LIMIT 1');
        $stmt->execute([':login' => $login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function loginExists(string $login): bool
    {
        return (bool) $this->getByLogin($login);
    }

    /** Retorna o admin (sem o hash) se login e senha conferem; senão false. */
    public function authenticate(string $login, string $senha): array|false
    {
        $admin = $this->getByLogin($login);
        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            return false;
        }
        unset($admin['senha_hash']);
        return $admin;
    }

    public function create(string $login, string $senha): int
    {
        $stmt = $this->db->prepare('INSERT INTO admin (login, senha_hash) VALUES (:login, :hash)');
        $stmt->execute([':login' => $login, ':hash' => password_hash($senha, PASSWORD_DEFAULT)]);
        return (int) $this->db->lastInsertId();
    }
}
