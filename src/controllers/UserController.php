<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

final class UserController
{
    /**
     * Dados da tela de perfil: nome, nível, XP, pontos, fases concluídas.
     * O tempo médio vem de progresso.tempo_segundos (null enquanto não houver fase concluída).
     */
    public static function profileData(int $usuarioId): ?array
    {
        $usuario = (new UserModel())->getById($usuarioId);
        if (!$usuario) {
            return null;
        }
        $resumo = (new ProgressModel())->summary($usuarioId);

        return [
            'username' => $usuario['nome_usuario'],
            'nivel' => (int) $usuario['nivel'],
            'xp' => (int) $usuario['xp_total'],
            'score' => $resumo['pontos_total'],
            'completed_phases' => $resumo['fases_concluidas'],
            'total_phases' => $resumo['total_fases'],
            'average_time_seconds' => $resumo['tempo_medio'],
        ];
    }
}
