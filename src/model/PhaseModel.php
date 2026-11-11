<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/GateModel.php';

final class PhaseModel
{
    public const DIFICULDADES = ['facil', 'medio', 'dificil'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /** Lista as fases em ordem de jogo. */
    public function getAll(): array
    {
        $sql = '
            SELECT f.id, f.nome, f.ordem, f.dificuldade, f.enunciado,
                   1 AS objetivo, f.entradas_quantidade, f.entradas_iniciais,
                   f.xp_recompensa, f.pontos_recompensa, f.admin_id
            FROM fase f
            ORDER BY f.ordem ASC
        ';

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Busca uma fase com suas portas lógicas associadas. */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, ordem, dificuldade, enunciado, 1 AS objetivo,
                    entradas_quantidade, entradas_iniciais,
                    xp_recompensa, pontos_recompensa, admin_id
             FROM fase
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $phase = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$phase) {
            return null;
        }

        $doorsStmt = $this->pdo->prepare(
            'SELECT pl.id, pl.nome, pl.descricao, pl.tabela_verdade,
                    fp.quantidade_disponivel
             FROM fase_porta fp
             INNER JOIN porta_logica pl ON pl.id = fp.porta_id
             WHERE fp.fase_id = :fase_id
             ORDER BY pl.id ASC'
        );
        $doorsStmt->execute(['fase_id' => $id]);
        $phase['portas'] = $doorsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $phase;
    }

    /** Retorna as fases que estão marcadas como desbloqueadas para o usuário. */
    public function getUnlockedPhases(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.id, f.nome, f.ordem, f.dificuldade, f.enunciado,
                    1 AS objetivo, f.xp_recompensa, f.pontos_recompensa,
                    p.desbloqueada, p.concluida
             FROM progresso p
             INNER JOIN fase f ON f.id = p.fase_id
             WHERE p.usuario_id = :usuario_id
               AND p.desbloqueada = 1
             ORDER BY f.ordem ASC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mapa de fases do jogador: todas as fases com status (desbloqueada / concluída).
     * A primeira fase (menor ordem) está sempre desbloqueada.
     * Não traz enunciado/objetivo — esses só aparecem ao abrir a fase.
     */
    public function getMapForUser(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.id, f.nome, f.ordem, f.dificuldade,
                    f.xp_recompensa, f.pontos_recompensa,
                    (COALESCE(p.desbloqueada, 0) = 1
                        OR f.ordem = (SELECT MIN(ordem) FROM fase)
                        OR EXISTS (
                            SELECT 1
                              FROM fase anterior
                              INNER JOIN progresso progresso_anterior
                                      ON progresso_anterior.fase_id = anterior.id
                             WHERE progresso_anterior.usuario_id = :usuario_id_anterior
                               AND progresso_anterior.concluida = 1
                               AND anterior.ordem < f.ordem
                               AND anterior.ordem = (
                                   SELECT MAX(ordem) FROM fase WHERE ordem < f.ordem
                               )
                        )) AS desbloqueada,
                    COALESCE(p.concluida, 0)      AS concluida,
                    COALESCE(p.xp_ganho, 0)       AS xp_ganho,
                    COALESCE(p.pontos_ganhos, 0)  AS pontos_ganhos,
                    COALESCE(p.tentativas, 0)     AS tentativas
             FROM fase f
             LEFT JOIN progresso p ON p.fase_id = f.id AND p.usuario_id = :usuario_id
             ORDER BY f.ordem ASC'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'usuario_id_anterior' => $usuarioId,
        ]);

        return array_map(static function (array $f): array {
            foreach (['id', 'ordem', 'xp_recompensa', 'pontos_recompensa', 'xp_ganho', 'pontos_ganhos', 'tentativas'] as $k) {
                $f[$k] = (int) $f[$k];
            }
            $f['desbloqueada'] = (bool) $f['desbloqueada'];
            $f['concluida'] = (bool) $f['concluida'];
            return $f;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Cria uma fase (com as portas escolhidas pelo admin) e retorna o ID gerado. */
    public function create(array $data, array $portas = []): int
    {
        $data = $this->validateData($data);
        $portas = $this->validatePortas($portas);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO fase
                    (nome, ordem, dificuldade, enunciado, objetivo, entradas_quantidade, entradas_iniciais,
                     xp_recompensa, pontos_recompensa, admin_id)
                 VALUES
                    (:nome, :ordem, :dificuldade, :enunciado, :objetivo, :entradas_quantidade, :entradas_iniciais,
                     :xp_recompensa, :pontos_recompensa, :admin_id)'
            );
            $stmt->execute($data);
            $id = (int) $this->pdo->lastInsertId();

            $this->replacePortas($id, $portas);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Atualiza a fase. Se $portas for null, mantém as portas atuais. */
    public function update(int $id, array $data, ?array $portas = null): bool
    {
        $data = $this->validateData($data);
        unset($data['admin_id']); // quem cadastrou originalmente continua sendo o autor
        if ($portas !== null) {
            $portas = $this->validatePortas($portas);
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE fase
                    SET nome = :nome, ordem = :ordem, dificuldade = :dificuldade,
                        enunciado = :enunciado, objetivo = :objetivo,
                        entradas_quantidade = :entradas_quantidade, entradas_iniciais = :entradas_iniciais,
                        xp_recompensa = :xp_recompensa, pontos_recompensa = :pontos_recompensa
                  WHERE id = :id'
            );
            $stmt->execute($data + ['id' => $id]);

            if ($portas !== null) {
                $this->replacePortas($id, $portas);
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM fase WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getFirst(): ?array
    {
        $row = $this->pdo->query('SELECT * FROM fase ORDER BY ordem ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getNext(int $ordemAtual): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fase WHERE ordem > :ordem ORDER BY ordem ASC LIMIT 1');
        $stmt->execute(['ordem' => $ordemAtual]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Valida e normaliza os dados da fase. Lança InvalidArgumentException com mensagem para o admin. */
    private function validateData(array $d): array
    {
        $nome = trim((string) ($d['nome'] ?? ''));
        if ($nome === '' || Http::len($nome) > 100) {
            throw new InvalidArgumentException('Informe o nome da fase (até 100 caracteres).');
        }

        $ordem = filter_var($d['ordem'] ?? null, FILTER_VALIDATE_INT);
        if ($ordem === false || $ordem < 1) {
            throw new InvalidArgumentException('A ordem deve ser um número inteiro maior ou igual a 1.');
        }

        $dificuldade = (string) ($d['dificuldade'] ?? '');
        if (!in_array($dificuldade, self::DIFICULDADES, true)) {
            throw new InvalidArgumentException('Dificuldade inválida.');
        }

        $enunciado = trim((string) ($d['enunciado'] ?? ''));
        if ($enunciado === '') {
            throw new InvalidArgumentException('Informe o enunciado da fase.');
        }

        $entradasQuantidade = filter_var($d['entradas_quantidade'] ?? 2, FILTER_VALIDATE_INT);
        $entradasIniciais = (string) ($d['entradas_iniciais'] ?? '');
        if ($entradasQuantidade === false || $entradasQuantidade < 1 || $entradasQuantidade > 8) {
            throw new InvalidArgumentException('A fase deve ter entre 1 e 8 entradas.');
        }
        if ($entradasIniciais === '') {
            $entradasIniciais = str_repeat('0', $entradasQuantidade);
        }
        if (strlen($entradasIniciais) !== $entradasQuantidade || !preg_match('/^[01]+$/D', $entradasIniciais)) {
            throw new InvalidArgumentException('Informe um estado inicial 0 ou 1 para cada entrada, na ordem apresentada.');
        }

        $xp = filter_var($d['xp_recompensa'] ?? 0, FILTER_VALIDATE_INT);
        $pontos = filter_var($d['pontos_recompensa'] ?? 0, FILTER_VALIDATE_INT);
        if ($xp === false || $xp < 0 || $pontos === false || $pontos < 0) {
            throw new InvalidArgumentException('XP e pontos devem ser números inteiros maiores ou iguais a 0.');
        }

        return [
            'nome' => $nome,
            'ordem' => $ordem,
            'dificuldade' => $dificuldade,
            'enunciado' => $enunciado,
            'objetivo' => 1,
            'entradas_quantidade' => $entradasQuantidade,
            'entradas_iniciais' => $entradasIniciais,
            'xp_recompensa' => $xp,
            'pontos_recompensa' => $pontos,
            'admin_id' => (int) ($d['admin_id'] ?? 0),
        ];
    }

    /** $portas: [porta_id => quantidade]. Ignora quantidade 0; exige ao menos uma porta. */
    private function validatePortas(array $portas): array
    {
        $limpo = [];
        foreach ($portas as $portaId => $qtd) {
            $portaId = filter_var($portaId, FILTER_VALIDATE_INT);
            $qtd = filter_var($qtd === '' ? 0 : $qtd, FILTER_VALIDATE_INT);
            if ($portaId === false || $qtd === false || $qtd < 0) {
                throw new InvalidArgumentException('Quantidade de porta inválida.');
            }
            if ($qtd > 0) {
                $limpo[$portaId] = $qtd;
            }
        }

        if (!$limpo) {
            throw new InvalidArgumentException('Escolha ao menos uma porta lógica para a fase.');
        }
        if ((new GateModel($this->pdo))->countExisting(array_keys($limpo)) !== count($limpo)) {
            throw new InvalidArgumentException('Alguma porta escolhida não existe no catálogo.');
        }
        return $limpo;
    }

    private function replacePortas(int $faseId, array $portas): void
    {
        $this->pdo->prepare('DELETE FROM fase_porta WHERE fase_id = :id')->execute(['id' => $faseId]);

        $ins = $this->pdo->prepare(
            'INSERT INTO fase_porta (fase_id, porta_id, quantidade_disponivel) VALUES (:fase, :porta, :qtd)'
        );
        foreach ($portas as $portaId => $qtd) {
            $ins->execute(['fase' => $faseId, 'porta' => $portaId, 'qtd' => $qtd]);
        }
    }
}
