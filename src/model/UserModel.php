<?php

require_once __DIR__ . '/../config/Database.php';

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Cadastrar usuário (retorna o id criado)
    public function create(
        string $nome,
        string $email,
        string $senha
    ): int {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuario
                (nome_usuario, email, senha_hash)
                VALUES (:nome, :email, :senha_hash)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':senha_hash' => $senhaHash
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Buscar usuário pelo ID
    public function getById(int $id): array|false
    {
        $sql = "SELECT id, nome_usuario, email, xp_total, nivel
                FROM usuario
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar usuário pelo e-mail
    public function getByEmail(string $email): array|false
    {
        $sql = "SELECT *
                FROM usuario
                WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar pelo nome de usuário OU e-mail (o formulário de login tem um único campo)
    public function getByLogin(string $login): array|false
    {
        $sql = "SELECT *
                FROM usuario
                WHERE nome_usuario = :nome OR email = :email
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nome' => $login, ':email' => strtolower($login)]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Indica qual campo já está em uso: 'nome', 'email' ou null
    public function campoEmUso(string $nome, string $email): ?string
    {
        $stmt = $this->db->prepare('SELECT 1 FROM usuario WHERE nome_usuario = :nome LIMIT 1');
        $stmt->execute([':nome' => $nome]);
        if ($stmt->fetchColumn()) {
            return 'nome';
        }

        $stmt = $this->db->prepare('SELECT 1 FROM usuario WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() ? 'email' : null;
    }

    // Verificar login (aceita nome de usuário ou e-mail)
    public function authenticate(
        string $login,
        string $senha
    ): array|false {
        $usuario = $this->getByLogin($login);

        if (
            !$usuario ||
            !password_verify($senha, $usuario['senha_hash'])
        ) {
            return false;
        }

        unset($usuario['senha_hash']);

        return $usuario;
    }

    // Registra atividade do jogador (usado para "jogadores ativos" no painel admin)
    public function registrarAcesso(int $id): void
    {
        $this->db->prepare('UPDATE usuario SET ultimo_acesso = NOW() WHERE id = :id')->execute([':id' => $id]);
    }

    // Ao sair, o jogador deixa de contar como ativo
    public function limparAcesso(int $id): void
    {
        $this->db->prepare('UPDATE usuario SET ultimo_acesso = NULL WHERE id = :id')->execute([':id' => $id]);
    }

    // Jogadores com atividade nos últimos $minutos minutos
    public function contarAtivos(int $minutos = 5): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuario WHERE ultimo_acesso >= (NOW() - INTERVAL :min MINUTE)'
        );
        $stmt->bindValue(':min', $minutos, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function contarTodos(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    }

    // Lista para o painel admin
    public function listAll(): array
    {
        return $this->db
            ->query('SELECT id, nome_usuario, email, xp_total, nivel FROM usuario ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Atualizar nome e e-mail
    public function update(
        int $id,
        string $nome,
        string $email
    ): bool {
        $sql = "UPDATE usuario
                SET nome_usuario = :nome,
                    email = :email
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':nome' => $nome,
            ':email' => $email
        ]);
    }

    // Alterar senha
    public function updatePassword(
        int $id,
        string $novaSenha
    ): bool {
        $senhaHash = password_hash(
            $novaSenha,
            PASSWORD_DEFAULT
        );

        $sql = "UPDATE usuario
                SET senha_hash = :senha_hash
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':senha_hash' => $senhaHash
        ]);
    }

    // Excluir usuário
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM usuario WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
