<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

Session::requireAdmin();
$e = AdminController::statsPageData();
$nomeDificuldade = ['facil' => 'Fácil', 'medio' => 'Média', 'dificil' => 'Difícil'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Estatísticas</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    <main class="adm">
        <h2>Estatísticas</h2>
        <div class="adm-sub">Visão geral do jogo</div>

        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-valor"><?= (int) $e['ativos'] ?></span>
                <span class="stat-rotulo">Jogadores ativos agora<br><small>(últimos 5 minutos)</small></span>
            </div>
            <div class="stat-card">
                <span class="stat-valor"><?= (int) $e['total_jogadores'] ?></span>
                <span class="stat-rotulo">Jogadores cadastrados</span>
            </div>
            <div class="stat-card">
                <span class="stat-valor"><?= number_format($e['pontos_distribuidos'], 0, ',', '.') ?></span>
                <span class="stat-rotulo">Pontos distribuídos<br><small>média de <?= number_format($e['pontos_por_jogador'], 1, ',', '.') ?> por jogador</small></span>
            </div>
            <div class="stat-card">
                <span class="stat-valor"><?= (int) $e['fases_concluidas'] ?></span>
                <span class="stat-rotulo">Fases concluídas</span>
            </div>
            <div class="stat-card">
                <span class="stat-valor"><?= Http::e(Http::duracao($e['tempo_medio'])) ?></span>
                <span class="stat-rotulo">Tempo médio por fase</span>
            </div>
            <div class="stat-card">
                <span class="stat-valor"><?= Http::e(Http::duracao($e['tempo_total'])) ?></span>
                <span class="stat-rotulo">Tempo total no puzzle</span>
            </div>
        </div>

        <h3 class="adm-titulo-tabela">Por fase</h3>
        <div class="admin-tabela-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>Fase</th><th>Dificuldade</th><th>Jogadores</th><th>Concluíram</th><th>Tentativas (média)</th><th>Tempo médio</th></tr>
            </thead>
            <tbody>
                <?php if (!$e['por_fase']): ?>
                    <tr><td colspan="6">Nenhuma fase cadastrada ainda.</td></tr>
                <?php endif; ?>
                <?php foreach ($e['por_fase'] as $f): ?>
                    <tr>
                        <td><?= (int) $f['ordem'] ?> — <?= Http::e($f['nome']) ?></td>
                        <td><?= Http::e($nomeDificuldade[$f['dificuldade']] ?? $f['dificuldade']) ?></td>
                        <td><?= (int) $f['jogadores'] ?></td>
                        <td><?= (int) $f['concluiram'] ?></td>
                        <td><?= $f['tentativas_media'] !== null ? number_format((float) $f['tentativas_media'], 1, ',', '.') : '—' ?></td>
                        <td><?= Http::e(Http::duracao($f['tempo_medio'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="admin-options">
            <div class="box-admin"><a href="/admin/users" class="btn-admin">Usuários</a></div>
            <div class="box-admin"><a href="/admin/phases" class="btn-admin">Fases</a></div>
            <div class="box-admin"><a href="/admin/ranking" class="btn-admin">Ranking</a></div>
            <div class="box-admin"><a href="/admin" class="btn-admin">Painel</a></div>
        </div>
    </main>
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
