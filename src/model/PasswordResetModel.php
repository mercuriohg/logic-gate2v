<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class PasswordResetModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function issueCode(int $userId, string $codeHash): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM password_reset
             WHERE user_id = :user_id AND last_sent_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)'
        );
        $stmt->execute([':user_id' => $userId]);
        if ($stmt->fetchColumn()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO password_reset (user_id, code_hash, expires_at, attempts, last_sent_at)
             VALUES (:user_id, :code_hash, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0, NOW())
             ON DUPLICATE KEY UPDATE
                code_hash = VALUES(code_hash),
                expires_at = VALUES(expires_at),
                attempts = 0,
                last_sent_at = NOW()'
        );
        $stmt->execute([':user_id' => $userId, ':code_hash' => $codeHash]);

        return true;
    }

    public function deleteCode(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_reset WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }

    public function resetPassword(int $userId, string $code, string $newPassword): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT code_hash, (expires_at > NOW()) AS unexpired, attempts
                 FROM password_reset WHERE user_id = :user_id FOR UPDATE'
            );
            $stmt->execute([':user_id' => $userId]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$reset
                || (int) $reset['attempts'] >= 5
                || (int) $reset['unexpired'] !== 1
            ) {
                $this->deleteCode($userId);
                $this->db->commit();
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE password_reset SET attempts = attempts + 1 WHERE user_id = :user_id'
            );
            $stmt->execute([':user_id' => $userId]);

            if (!password_verify($code, $reset['code_hash'])) {
                if ((int) $reset['attempts'] + 1 >= 5) {
                    $this->deleteCode($userId);
                }
                $this->db->commit();
                return false;
            }

            $stmt = $this->db->prepare('UPDATE usuario SET senha_hash = :senha_hash WHERE id = :id');
            $stmt->execute([
                ':senha_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id' => $userId,
            ]);
            $this->deleteCode($userId);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
