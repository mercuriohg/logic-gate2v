<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

Session::requireAdmin();
$dados = AdminController::phasesPageData();
$fases = $dados['fases'];
$portas = $dados['portas'];
$editar = $dados['editar'];

$ok = Session::flash('admin_ok');
$erro = Session::flash('admin_error');

// quantidade de cada porta na fase em edição: [porta_id => quantidade]
$qtd = [];
foreach (($editar['portas'] ?? []) as $p) {
    $qtd[(int) $p['id']] = (int) $p['quantidade_disponivel'];
}
$v = static fn(string $campo, mixed $padrao = '') => Http::e($editar[$campo] ?? $padrao);
$proximaOrdem = $fases ? ((int) end($fases)['ordem'] + 1) : 1;
$nomeDificuldade = ['facil' => 'Fácil', 'medio' => 'Média', 'dificil' => 'Difícil'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Fases</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    <main class="adm">
        <h2>Bem-vindo, <?= Http::e($_SESSION['username']) ?>!</h2>
        <p>Aqui você pode gerenciar as fases do site.</p>

        <?php if ($ok): ?><p class="flash flash-ok"><?= Http::e($ok) ?></p><?php endif; ?>
        <?php if ($erro): ?><p class="flash flash-erro"><?= Http::e($erro) ?></p><?php endif; ?>

        <div id="admin-new-phase">
            <form action="/admin/phases" method="POST" class="admin-form">
                <?= Session::csrfField() ?>
                <?php if ($editar): ?>
                    <input type="hidden" name="id" value="<?= (int) $editar['id'] ?>">
                    <h3>Editando a fase #<?= (int) $editar['id'] ?></h3>
                <?php else: ?>
                    <h3>Nova fase</h3>
                <?php endif; ?>

                <input type="text" name="nome" placeholder="Nome da fase" class="form-control" required maxlength="100"
                       value="<?= $v('nome') ?>">
                <input type="number" name="ordem" placeholder="Ordem na sequência" class="form-control" required min="1"
                       value="<?= $editar ? (int) $editar['ordem'] : $proximaOrdem ?>">

                <select name="dificuldade" class="form-control" required>
                    <?php foreach ($nomeDificuldade as $valor => $rotulo): ?>
                        <option value="<?= $valor ?>" <?= ($editar['dificuldade'] ?? 'facil') === $valor ? 'selected' : '' ?>>
                            <?= $rotulo ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <textarea name="enunciado" placeholder="Enunciado do desafio" class="form-control" rows="4" required><?= $v('enunciado') ?></textarea>

                <p class="fase-objetivo">Saída-alvo de todas as fases: <strong>1</strong></p>

                <label for="entradas-quantidade">Quantidade de entradas (1 a 8)</label>
                <input id="entradas-quantidade" type="number" name="entradas_quantidade" class="form-control"
                       min="1" max="8" required value="<?= (int) ($editar['entradas_quantidade'] ?? 2) ?>">
                <label for="entradas-iniciais">Estados iniciais (um 0 ou 1 para cada entrada, na ordem)</label>
                <input id="entradas-iniciais" type="text" name="entradas_iniciais" class="form-control"
                       inputmode="numeric" pattern="[01]{1,8}" maxlength="8" required
                       value="<?= Http::e($editar['entradas_iniciais'] ?? '00') ?>">

                <input type="number" name="xp_recompensa" placeholder="XP da fase" class="form-control" min="0" required
                       value="<?= (int) ($editar['xp_recompensa'] ?? 50) ?>">
                <input type="number" name="pontos_recompensa" placeholder="Pontuação da fase" class="form-control" min="0" required
                       value="<?= (int) ($editar['pontos_recompensa'] ?? 100) ?>">

                <fieldset class="gate-fieldset">
                    <legend>Portas disponíveis (quantidade; 0 = não disponível)</legend>
                    <?php foreach ($portas as $porta): ?>
                        <label class="gate-qty">
                            <span><?= Http::e($porta['nome']) ?></span>
                            <input type="number" name="portas[<?= (int) $porta['id'] ?>]" min="0" max="99"
                                   class="form-control" value="<?= $qtd[(int) $porta['id']] ?? 0 ?>">
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <button type="submit" class="btn-admin"><?= $editar ? 'Salvar alterações' : '+ Adicionar Fase' ?></button>
                <?php if ($editar): ?><a href="/admin/phases" class="btn-admin">Cancelar</a><?php endif; ?>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr><th>Ordem</th><th>Nome</th><th>Dificuldade</th><th>Entradas</th><th>Obj.</th><th>XP</th><th>Pontos</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php if (!$fases): ?>
                    <tr><td colspan="8">Nenhuma fase cadastrada ainda.</td></tr>
                <?php endif; ?>
                <?php foreach ($fases as $f): ?>
                    <tr>
                        <td><?= (int) $f['ordem'] ?></td>
                        <td><?= Http::e($f['nome']) ?></td>
                        <td><?= Http::e($nomeDificuldade[$f['dificuldade']] ?? $f['dificuldade']) ?></td>
                        <td><?= (int) $f['entradas_quantidade'] ?></td>
                        <td>1</td>
                        <td><?= (int) $f['xp_recompensa'] ?></td>
                        <td><?= (int) $f['pontos_recompensa'] ?></td>
                        <td class="admin-actions">
                            <a href="/admin/phases?edit=<?= (int) $f['id'] ?>" class="btn-admin">Editar</a>
                            <form action="/admin/phases/delete" method="POST"
                                  onsubmit="return confirm('Remover a fase &quot;<?= Http::e(addslashes($f['nome'])) ?>&quot;? O progresso dos jogadores nela também será apagado.')">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                <button type="submit" class="btn-admin">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="admin-options">
            <div class="box-admin"><a href="/admin/users" class="btn-admin">Gerenciar Usuários</a></div>
            <div class="box-admin"><a href="/admin/ranking" class="btn-admin">Ranking</a></div>
            <div class="box-admin"><a href="/admin/stats" class="btn-admin">Estatísticas</a></div>
            <div class="box-admin"><a href="/admin" class="btn-admin">Painel</a></div>
        </div>
    </main>
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
