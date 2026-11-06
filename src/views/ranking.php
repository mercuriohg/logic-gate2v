<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/ProgressController.php';
Session::start();
$ranking = ProgressController::ranking(50);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css"> 
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>    

    <main id="container-main">
        <h1>Ranking dos Usuários</h1>
        <table id="ranking-table">
            <thead>
                <tr>
                    <th>Posição</th>
                    <th>Usuário</th>
                    <th>Pontuação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$ranking): ?>
                    <tr><td colspan="3">Ainda não há jogadores no ranking.</td></tr>
                <?php endif; ?>
                <?php foreach ($ranking as $i => $linha): ?>
                    <tr>
                        <td><?= $i + 1 ?>º</td>
                        <td><?= Http::e($linha['nome_usuario']) ?></td>
                        <td><?= number_format((int) $linha['pontos_total'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>