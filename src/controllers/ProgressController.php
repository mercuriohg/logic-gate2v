<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

final class ProgressController
{
    /**
     * POST /api/fase/tentativa   campos: id (fase), saida (0 ou 1), csrf_token
     * "saida" é o resultado do circuito montado pelo jogador. Se for igual ao objetivo da fase,
     * a fase é concluída (xp/pontos concedidos uma única vez e próxima fase desbloqueada).
     */
    public static function attempt(): void
    {
        $uid = Session::userId();
        if ($uid === null) {
            Http::json(['erro' => 'Faça login para continuar.'], 401);
        }
        if (!Session::csrfValido()) {
            Http::json(['erro' => 'Sessão expirada. Recarregue a página.'], 419);
        }

        $faseId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $saida = $_POST['saida'] ?? null;
        if ($faseId === false || !in_array($saida, ['0', '1', 0, 1], true)) {
            Http::json(['erro' => 'Informe a fase (id) e a saída (0 ou 1).'], 400);
        }

        try {
            Http::json((new ProgressModel())->submitAttempt($uid, $faseId, (int) $saida));
        } catch (HttpError $e) {
            Http::json(['erro' => $e->getMessage()], $e->status);
        }
    }

    /** Linhas do ranking (a view monta a tabela). */
    public static function ranking(int $limite = 50): array
    {
        return (new ProgressModel())->ranking($limite);
    }
}
