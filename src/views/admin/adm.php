<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

Session::requireAdmin();
$d = AdminController::dashboardData();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    <main class="adm">
        <h2>Bem-vindo, <?= Http::e($_SESSION['username']) ?>!</h2>
        <div class="adm-sub">Painel do administrador</div>

        <div class="adm-grid">
            <a href="/admin/users" class="adm-card">
                <span class="adm-card-titulo">Usuários</span>
                <span class="adm-card-info"><?= (int) $d['usuarios'] ?> cadastrado(s)</span>
                <span class="adm-card-acao">Ver tabela de usuários →</span>
            </a>
            <a href="/admin/phases" class="adm-card">
                <span class="adm-card-titulo">Fases</span>
                <span class="adm-card-info"><?= (int) $d['fases'] ?> fase(s) criada(s)</span>
                <span class="adm-card-acao">Criar e editar fases →</span>
            </a>
            <a href="/admin/ranking" class="adm-card">
                <span class="adm-card-titulo">Ranking</span>
                <span class="adm-card-info"><?= $d['lider'] !== null ? 'Líder: ' . Http::e($d['lider']) : 'Ninguém pontuou ainda' ?></span>
                <span class="adm-card-acao">Ver ranking de cada jogador →</span>
            </a>
            <a href="/admin/stats" class="adm-card">
                <span class="adm-card-titulo">Estatísticas</span>
                <span class="adm-card-info"><?= (int) $d['ativos'] ?> jogador(es) ativo(s) agora</span>
                <span class="adm-card-acao">Pontuação e tempo no puzzle →</span>
            </a>
        </div>
    </main>
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
