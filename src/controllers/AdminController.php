<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

/** Ações do painel administrativo. Todas exigem login de administrador. */
final class AdminController
{
    // ---------- dados para as views ----------

    /** Dados da página /admin/phases (lista, catálogo de portas e fase em edição, se ?edit=ID). */
    public static function phasesPageData(): array
    {
        $phases = new PhaseModel();
        $editar = null;
        $editId = (int) ($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $editar = $phases->getById($editId);
        }

        return [
            'fases' => $phases->getAll(),
            'portas' => (new GateModel())->getAll(),
            'editar' => $editar,
        ];
    }

    /** Cartões do painel inicial (/admin): contagens rápidas. */
    public static function dashboardData(): array
    {
        $users = new UserModel();
        $top = (new ProgressModel())->ranking(1)[0] ?? null;
        return [
            'usuarios' => $users->contarTodos(),
            'ativos' => $users->contarAtivos(),
            'fases' => count((new PhaseModel())->getAll()),
            'lider' => $top && (int) $top['pontos_total'] > 0 ? $top['nome_usuario'] : null,
        ];
    }

    public static function rankingPageData(): array
    {
        return ['ranking' => (new ProgressModel())->ranking(100)];
    }

    /** Página de estatísticas: jogadores ativos, pontuação e tempo no puzzle. */
    public static function statsPageData(): array
    {
        $users = new UserModel();
        $progress = new ProgressModel();
        $geral = $progress->adminOverview();
        $total = $users->contarTodos();

        return [
            'ativos' => $users->contarAtivos(),
            'total_jogadores' => $total,
            'pontos_distribuidos' => $geral['pontos_distribuidos'],
            'pontos_por_jogador' => $total > 0 ? $geral['pontos_distribuidos'] / $total : 0,
            'fases_concluidas' => $geral['fases_concluidas'],
            'tempo_medio' => $geral['tempo_medio'],
            'tempo_total' => $geral['tempo_total'],
            'por_fase' => $progress->adminPerPhase(),
        ];
    }

    public static function usersPageData(): array
    {
        return ['usuarios' => (new UserModel())->listAll()];
    }

    // ---------- ações (POST) ----------

    /** POST /admin/phases — cria (sem id) ou edita (com id) uma fase e suas portas. */
    public static function savePhase(): void
    {
        $adminId = Session::requireAdmin();
        Session::exigirCsrf('admin_error', '/admin/phases');

        $id = (int) ($_POST['id'] ?? 0);
        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'ordem' => $_POST['ordem'] ?? null,
            'dificuldade' => $_POST['dificuldade'] ?? '',
            'enunciado' => $_POST['enunciado'] ?? '',
            'entradas_quantidade' => $_POST['entradas_quantidade'] ?? 2,
            'entradas_iniciais' => $_POST['entradas_iniciais'] ?? '',
            'xp_recompensa' => $_POST['xp_recompensa'] ?? 0,
            'pontos_recompensa' => $_POST['pontos_recompensa'] ?? 0,
            'admin_id' => $adminId,
        ];
        $portas = is_array($_POST['portas'] ?? null) ? $_POST['portas'] : [];

        $phases = new PhaseModel();
        try {
            if ($id > 0) {
                if (!$phases->getById($id)) {
                    throw new InvalidArgumentException('Fase não encontrada.');
                }
                $phases->update($id, $dados, $portas);
                Session::setFlash('admin_ok', 'Fase atualizada com sucesso.');
            } else {
                $phases->create($dados, $portas);
                Session::setFlash('admin_ok', 'Fase criada com sucesso.');
            }
        } catch (InvalidArgumentException $e) {
            Session::setFlash('admin_error', $e->getMessage());
            Http::redirect($id > 0 ? '/admin/phases?edit=' . $id : '/admin/phases');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                Session::setFlash('admin_error', 'Já existe uma fase com essa ordem.');
                Http::redirect($id > 0 ? '/admin/phases?edit=' . $id : '/admin/phases');
            }
            throw $e;
        }

        Http::redirect('/admin/phases');
    }

    /** POST /admin/phases/delete */
    public static function deletePhase(): void
    {
        Session::requireAdmin();
        Session::exigirCsrf('admin_error', '/admin/phases');

        $ok = (new PhaseModel())->delete((int) ($_POST['id'] ?? 0));
        $ok
            ? Session::setFlash('admin_ok', 'Fase removida.')
            : Session::setFlash('admin_error', 'Fase não encontrada.');
        Http::redirect('/admin/phases');
    }

    /** POST /admin/users/delete */
    public static function deleteUser(): void
    {
        Session::requireAdmin();
        Session::exigirCsrf('admin_error', '/admin/users');

        $ok = (new UserModel())->delete((int) ($_POST['id'] ?? 0));
        $ok
            ? Session::setFlash('admin_ok', 'Usuário removido.')
            : Session::setFlash('admin_error', 'Usuário não encontrado.');
        Http::redirect('/admin/users');
    }
}
