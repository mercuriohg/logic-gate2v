<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/HttpError.php';
require_once __DIR__ . '/../helpers/Nivel.php';

/** Progresso do jogador durante as fases (tabela `progresso`), ranking e resumo do perfil. */
final class ProgressModel
{
    /** Tempo máximo contado por fase (evita que uma aba esquecida aberta distorça as médias). */
    public const TEMPO_MAXIMO_SEGUNDOS = 3600;

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function find(int $usuarioId, int $faseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM progresso WHERE usuario_id = :u AND fase_id = :f');
        $stmt->execute(['u' => $usuarioId, 'f' => $faseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Marca que o jogador abriu a fase (início da contagem de tempo). Fases já concluídas não são alteradas.
     * Só deve ser chamado para fases já liberadas para o jogador.
     */
    public function markOpened(int $usuarioId, int $faseId): void
    {
        $this->pdo->prepare(
            'INSERT INTO progresso (usuario_id, fase_id, desbloqueada, data_inicio)
             VALUES (:u, :f, 1, NOW())
             ON DUPLICATE KEY UPDATE
                 desbloqueada = 1,
                 data_inicio = IF(concluida = 1, data_inicio, NOW())'
        )->execute(['u' => $usuarioId, 'f' => $faseId]);
    }

    /**
     * Registra uma tentativa do jogador na fase.
     * Se o valor enviado for igual ao objetivo, conclui a fase (uma única vez): concede xp e pontos,
     * atualiza xp_total/nivel do usuário e desbloqueia a próxima fase. Tudo em uma transação.
     *
     * @throws HttpError 404 (usuário/fase inexistente) ou 403 (fase bloqueada)
     */
    public function submitAttempt(int $usuarioId, int $faseId, int $saida): array
    {
        $this->pdo->beginTransaction();
        try {
            // trava o usuário: evita recompensa duplicada em requisições simultâneas
            $stmt = $this->pdo->prepare('SELECT id, xp_total FROM usuario WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                throw new HttpError(404, 'Usuário não encontrado.');
            }

            $stmt = $this->pdo->prepare('SELECT * FROM fase WHERE id = :id');
            $stmt->execute(['id' => $faseId]);
            $fase = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$fase) {
                throw new HttpError(404, 'Fase não encontrada.');
            }

            $primeira = $this->pdo->query('SELECT id FROM fase ORDER BY ordem ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            $atual = $this->find($usuarioId, $faseId);
            $liberada = (int) $fase['id'] === (int) $primeira['id'] || ($atual && (int) $atual['desbloqueada'] === 1);
            if (!$liberada) {
                throw new HttpError(403, 'Fase ainda bloqueada.');
            }

            // cria o progresso (se preciso) e soma 1 tentativa
            $this->pdo->prepare(
                'INSERT INTO progresso (usuario_id, fase_id, desbloqueada, tentativas)
                 VALUES (:u, :f, 1, 1)
                 ON DUPLICATE KEY UPDATE tentativas = tentativas + 1, desbloqueada = 1'
            )->execute(['u' => $usuarioId, 'f' => $faseId]);
            $prog = $this->find($usuarioId, $faseId);

            if ($saida !== (int) $fase['objetivo']) {
                $this->pdo->commit();
                return [
                    'acertou' => false,
                    'concluida' => (bool) $prog['concluida'],
                    'tentativas' => (int) $prog['tentativas'],
                ];
            }

            if ((int) $prog['concluida'] === 1) {
                $this->pdo->commit();
                return [
                    'acertou' => true, 'concluida' => true, 'ja_concluida' => true,
                    'tentativas' => (int) $prog['tentativas'], 'xp_ganho' => 0, 'pontos_ganhos' => 0,
                ];
            }

            $xp = (int) $fase['xp_recompensa'];
            $pontos = (int) $fase['pontos_recompensa'];

            $this->pdo->prepare(
                'UPDATE progresso
                    SET concluida = 1, xp_ganho = :xp, pontos_ganhos = :pontos, data_conclusao = NOW(),
                        tempo_segundos = IF(data_inicio IS NULL, NULL,
                                            LEAST(TIMESTAMPDIFF(SECOND, data_inicio, NOW()), :maximo))
                  WHERE id = :id'
            )->execute(['xp' => $xp, 'pontos' => $pontos, 'maximo' => self::TEMPO_MAXIMO_SEGUNDOS, 'id' => $prog['id']]);

            $novoXp = (int) $usuario['xp_total'] + $xp;
            $novoNivel = Nivel::calcular($novoXp);
            $this->pdo->prepare('UPDATE usuario SET xp_total = :xp, nivel = :nivel WHERE id = :id')
                ->execute(['xp' => $novoXp, 'nivel' => $novoNivel, 'id' => $usuarioId]);

            // desbloqueia a próxima fase (pela ordem)
            $stmt = $this->pdo->prepare('SELECT id FROM fase WHERE ordem > :o ORDER BY ordem ASC LIMIT 1');
            $stmt->execute(['o' => $fase['ordem']]);
            $proxima = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($proxima) {
                $this->pdo->prepare(
                    'INSERT INTO progresso (usuario_id, fase_id, desbloqueada) VALUES (:u, :f, 1)
                     ON DUPLICATE KEY UPDATE desbloqueada = 1'
                )->execute(['u' => $usuarioId, 'f' => $proxima['id']]);
            }

            $this->pdo->commit();
            return [
                'acertou' => true,
                'concluida' => true,
                'ja_concluida' => false,
                'tentativas' => (int) $prog['tentativas'],
                'xp_ganho' => $xp,
                'pontos_ganhos' => $pontos,
                'usuario' => ['xp_total' => $novoXp, 'nivel' => $novoNivel],
                'proxima_fase_id' => $proxima ? (int) $proxima['id'] : null,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Resumo do jogador para o perfil / barra de progresso. */
    public function summary(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM fase)                          AS total_fases,
                    COALESCE(SUM(concluida = 1), 0)                      AS fases_concluidas,
                    COALESCE(SUM(pontos_ganhos), 0)                      AS pontos_total,
                    AVG(CASE WHEN concluida = 1 THEN tempo_segundos END) AS tempo_medio
               FROM progresso WHERE usuario_id = :id'
        );
        $stmt->execute(['id' => $usuarioId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_fases' => (int) $r['total_fases'],
            'fases_concluidas' => (int) $r['fases_concluidas'],
            'pontos_total' => (int) $r['pontos_total'],
            'tempo_medio' => $r['tempo_medio'] !== null ? (float) $r['tempo_medio'] : null,
        ];
    }

    /** Ranking por pontos (desempate por xp_total). */
    public function ranking(int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.nome_usuario, u.nivel, u.xp_total,
                    COALESCE(SUM(p.pontos_ganhos), 0) AS pontos_total,
                    COUNT(p.id) AS fases_concluidas
               FROM usuario u
               LEFT JOIN progresso p ON p.usuario_id = u.id AND p.concluida = 1
              GROUP BY u.id, u.nome_usuario, u.nivel, u.xp_total
              ORDER BY pontos_total DESC, u.xp_total DESC, u.id ASC
              LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Números gerais para o painel admin (/admin/stats). */
    public function adminOverview(): array
    {
        $r = $this->pdo->query(
            'SELECT COALESCE(SUM(CASE WHEN concluida = 1 THEN pontos_ganhos END), 0) AS pontos_distribuidos,
                    COALESCE(SUM(concluida = 1), 0)                                  AS fases_concluidas,
                    AVG(CASE WHEN concluida = 1 THEN tempo_segundos END)            AS tempo_medio,
                    COALESCE(SUM(CASE WHEN concluida = 1 THEN tempo_segundos END), 0) AS tempo_total
               FROM progresso'
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'pontos_distribuidos' => (int) $r['pontos_distribuidos'],
            'fases_concluidas' => (int) $r['fases_concluidas'],
            'tempo_medio' => $r['tempo_medio'] !== null ? (float) $r['tempo_medio'] : null,
            'tempo_total' => (int) $r['tempo_total'],
        ];
    }

    /** Uma linha por fase: quantos jogaram/concluíram, tentativas e tempo médio. */
    public function adminPerPhase(): array
    {
        return $this->pdo->query(
            'SELECT f.id, f.ordem, f.nome, f.dificuldade,
                    COUNT(p.id)                                         AS jogadores,
                    COALESCE(SUM(p.concluida = 1), 0)                   AS concluiram,
                    AVG(p.tentativas)                                   AS tentativas_media,
                    AVG(CASE WHEN p.concluida = 1 THEN p.tempo_segundos END) AS tempo_medio
               FROM fase f
               LEFT JOIN progresso p ON p.fase_id = f.id AND (p.tentativas > 0 OR p.concluida = 1)
              GROUP BY f.id, f.ordem, f.nome, f.dificuldade
              ORDER BY f.ordem ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
