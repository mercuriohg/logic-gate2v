<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

/** Fases do ponto de vista do jogador (mapa e tela da fase) + endpoints JSON para o front do jogo. */
final class PhaseController
{
    /** Mapa de fases do jogador (bloqueada / desbloqueada / concluída). */
    public static function map(int $usuarioId): array
    {
        return (new PhaseModel())->getMapForUser($usuarioId);
    }

    /**
     * Abre uma fase: enunciado, objetivo e portas disponíveis. Só se estiver desbloqueada.
     * @throws HttpError 404 / 403
     */
    public static function open(int $usuarioId, int $faseId): array
    {
        $mapa = self::map($usuarioId);
        $item = null;
        foreach ($mapa as $f) {
            if ($f['id'] === $faseId) {
                $item = $f;
                break;
            }
        }
        if ($item === null) {
            throw new HttpError(404, 'Fase não encontrada.');
        }
        if (!$item['desbloqueada']) {
            throw new HttpError(403, 'Essa fase ainda está bloqueada. Conclua a anterior!');
        }

        (new ProgressModel())->markOpened($usuarioId, $faseId); // começa a contar o tempo da fase

        $fase = (new PhaseModel())->getById($faseId);
        unset($fase['admin_id']);
        $fase['concluida'] = $item['concluida'];
        $fase['tentativas'] = $item['tentativas'];
        return $fase;
    }

    // ---------- JSON ----------

    /** GET /api/fases */
    public static function apiMap(): void
    {
        $uid = self::jsonUserId();
        Http::json(self::map($uid));
    }

    /** GET /api/fase?id=1 */
    public static function apiOpen(): void
    {
        $uid = self::jsonUserId();
        $id = (int) ($_GET['id'] ?? 0);
        try {
            Http::json(self::open($uid, $id));
        } catch (HttpError $e) {
            Http::json(['erro' => $e->getMessage()], $e->status);
        }
    }

    private static function jsonUserId(): int
    {
        $uid = Session::userId();
        if ($uid === null) {
            Http::json(['erro' => 'Faça login para continuar.'], 401);
        }
        return $uid;
    }
}
