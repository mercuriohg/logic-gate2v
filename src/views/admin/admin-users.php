<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

Session::requireAdmin();
$usuarios = AdminController::usersPageData()['usuarios'];
$ok = Session::flash('admin_ok');
$erro = Session::flash('admin_error');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Usuários</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    <main class="adm">
        <h2>Bem-vindo, <?= Http::e($_SESSION['username']) ?>!</h2>
        <p>Aqui você pode gerenciar os usuários do site.</p>

        <?php if ($ok): ?><p class="flash flash-ok"><?= Http::e($ok) ?></p><?php endif; ?>
        <?php if ($erro): ?><p class="flash flash-erro"><?= Http::e($erro) ?></p><?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Usuário</th><th>E-mail</th><th>Nível</th><th>XP</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php if (!$usuarios): ?>
                    <tr><td colspan="6">Nenhum usuário cadastrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td><?= Http::e($u['nome_usuario']) ?></td>
                        <td><?= Http::e($u['email']) ?></td>
                        <td><?= (int) $u['nivel'] ?></td>
                        <td><?= (int) $u['xp_total'] ?></td>
                        <td class="admin-actions">
                            <form action="/admin/users/delete" method="POST"
                                  onsubmit="return confirm('Excluir este usuário e todo o progresso dele?')">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn-admin">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="admin-options">
            <div class="box-admin"><a href="/admin/phases" class="btn-admin">Fases</a></div>
            <div class="box-admin"><a href="/admin/ranking" class="btn-admin">Ranking</a></div>
            <div class="box-admin"><a href="/admin/stats" class="btn-admin">Estatísticas</a></div>
            <div class="box-admin"><a href="/admin" class="btn-admin">Painel</a></div>
        </div>
    </main>
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
