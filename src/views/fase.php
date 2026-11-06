<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/PhaseController.php';

$userId = Session::requireUser();
$faseId = (int) ($_GET['id'] ?? 0);
$fase = null;
$erro = Session::flash('fase_error');

if ($faseId > 0) {
    try {
        $fase = PhaseController::open($userId, $faseId);
    } catch (HttpError $e) {
        Session::setFlash('fase_error', $e->getMessage());
        Http::redirect('/fase');
    }
}
$mapa = $fase ? [] : PhaseController::map($userId);

// imagens já existentes em /assets/img
$imagemPorta = [
    'AND' => 'and.png', 'OR' => 'or.png', 'NOT' => 'not.png', 'XOR' => 'xor2.png',
    'NAND' => 'nand.png', 'NOR' => 'nor.png', 'XNOR' => 'xnor.png',
];
$nomeDificuldade = ['facil' => 'Fácil', 'medio' => 'Média', 'dificil' => 'Difícil'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fases - Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>

    <main id="container-main" class="fase-page">
    <?php if ($fase): ?>
        <!-- ============ TELA DE UMA FASE ============ -->
        <h1>Fase <?= (int) $fase['ordem'] ?> — <?= Http::e($fase['nome']) ?></h1>
        <p class="fase-meta">
            <?= Http::e($nomeDificuldade[$fase['dificuldade']] ?? $fase['dificuldade']) ?> ·
            +<?= (int) $fase['xp_recompensa'] ?> XP · +<?= (int) $fase['pontos_recompensa'] ?> pontos
            <?= $fase['concluida'] ? '· ✔ concluída' : '' ?>
        </p>

        <section class="fase-enunciado">
            <p><?= nl2br(Http::e($fase['enunciado'])) ?></p>
            <p class="fase-objetivo">Objetivo: chegar na saída <strong><?= (int) $fase['objetivo'] ?></strong></p>
        </section>

        <h2>Portas disponíveis</h2>
        <div id="portas" class="fase-portas">
            <?php foreach ($fase['portas'] as $porta): ?>
                <div class="logica-door" title="<?= Http::e($porta['tabela_verdade']) ?>">
                    <h3><?= Http::e($porta['nome']) ?> × <?= (int) $porta['quantidade_disponivel'] ?></h3>
                    <img src="/assets/img/<?= Http::e($imagemPorta[$porta['nome']] ?? 'logic-gate.png') ?>"
                         alt="Porta lógica <?= Http::e($porta['nome']) ?>" height="100em">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Área do circuito: o editor visual do jogo entra aqui.
             Quando o jogador terminar de montar o circuito, o JS do jogo chama
             LogicGate.enviarSaida(0 ou 1) (definido em /assets/js/Fase.js). -->
        <section id="fase-play" data-fase-id="<?= (int) $fase['id'] ?>" data-csrf="<?= Http::e(Session::csrfToken()) ?>">
            <div id="circuito"></div>

            <?php if ((getenv('APP_ENV') ?: '') === 'dev'): ?>
                <!-- SOMENTE DESENVOLVIMENTO (APP_ENV=dev): permite testar o backend sem o editor de circuito. -->
                <div class="fase-dev">
                    <p>Modo desenvolvimento — simular saída do circuito:</p>
                    <button type="button" class="btn-admin" data-saida="0">Enviar saída 0</button>
                    <button type="button" class="btn-admin" data-saida="1">Enviar saída 1</button>
                </div>
            <?php endif; ?>

            <p id="fase-resultado" class="fase-resultado" aria-live="polite"></p>
            <a href="/fase" class="btn-admin">← Todas as fases</a>
        </section>
        <script src="/assets/js/Fase.js" defer></script>

    <?php else: ?>
        <!-- ============ MAPA DE FASES ============ -->
        <h1>Fases</h1>
        <?php if ($erro): ?><p class="flash flash-erro"><?= Http::e($erro) ?></p><?php endif; ?>

        <?php include __DIR__ . '/components/fase-map.php'; ?>
    <?php endif; ?>
    </main>
</body>
</html>
