<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

Session::requireAdmin();
$ranking = AdminController::rankingPageData()['ranking'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Ranking</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    <main class="adm">
        <h2>Ranking de cada jogador</h2>
        <div class="adm-sub">Ordenado por pontuação (desempate por XP)</div>

        <div class="admin-tabela-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>Pos.</th><th>Usuário</th><th>Nível</th><th>XP</th><th>Pontos</th><th>Fases concluídas</th></tr>
            </thead>
            <tbody>
                <?php if (!$ranking): ?>
                    <tr><td colspan="6">Nenhum jogador cadastrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($ranking as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?>º</td>
                        <td><?= Http::e($r['nome_usuario']) ?></td>
                        <td><?= (int) $r['nivel'] ?></td>
                        <td><?= (int) $r['xp_total'] ?></td>
                        <td><?= (int) $r['pontos_total'] ?></td>
                        <td><?= (int) $r['fases_concluidas'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="admin-options">
            <div class="box-admin"><a href="/admin/users" class="btn-admin">Usuários</a></div>
            <div class="box-admin"><a href="/admin/phases" class="btn-admin">Fases</a></div>
            <div class="box-admin"><a href="/admin/stats" class="btn-admin">Estatísticas</a></div>
            <div class="box-admin"><a href="/admin" class="btn-admin">Painel</a></div>
        </div>
    </main>
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
