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

$nomeDificuldade = ['facil' => 'Fácil', 'medio' => 'Média', 'dificil' => 'Difícil'];
$portasDoCircuito = array_map(
    static fn(array $porta): array => [
        'type' => $porta['nome'],
        'quantity' => (int) $porta['quantidade_disponivel'],
    ],
    $fase['portas'] ?? []
);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fases - Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>

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

        <section id="fase-play" data-fase-id="<?= (int) $fase['id'] ?>" data-csrf="<?= Http::e(Session::csrfToken()) ?>">
            <script id="circuit-config" type="application/json"><?=
                json_encode([
                    'phaseId' => (int) $fase['id'],
                    'csrf' => Session::csrfToken(),
                    'expectedOutput' => 1,
                    'initialInputs' => str_split($fase['entradas_iniciais']),
                    'gates' => $portasDoCircuito,
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)
            ?></script>

            <nav class="circuit-toolbar" aria-label="Controles do simulador">
                <div class="circuit-toolbar-group">
                    <a href="/fase" class="btn-admin" aria-label="Voltar ao mapa de fases">← Fases</a>
                    <button type="button" id="circuit-reset" class="btn-admin">↺ Restaurar</button>
                </div>
                <span class="circuit-toolbar-title">Fase <?= (int) $fase['ordem'] ?> · <?= Http::e($fase['nome']) ?></span>
                <div class="circuit-toolbar-group">
                    <button type="button" id="circuit-test" class="btn-admin">TESTAR</button>
                    <button type="button" id="circuit-info-open" class="btn-admin" aria-label="Informações da fase">i</button>
                </div>
            </nav>

            <div class="circuit-workspace" aria-label="Área de montagem do circuito">
                <div id="circuit-board" class="circuit-board">
                    <svg id="circuit-wires" class="circuit-wires" aria-label="Conexões automáticas do circuito"></svg>
                    <div id="circuit-inputs" class="circuit-inputs" aria-label="Entradas binárias"></div>
                    <div id="circuit-gates" class="circuit-gates" aria-label="Portas definidas pelo administrador"></div>
                    <div id="circuit-led" class="circuit-led" aria-live="polite">
                        <span class="circuit-led-light" aria-hidden="true"></span>
                        <span>SAÍDA</span>
                        <strong id="circuit-led-value">?</strong>
                        <button type="button" class="circuit-pin circuit-pin-input" aria-label="Entrada do LED"></button>
                    </div>
                </div>
            </div>

            <p id="fase-resultado" class="fase-resultado" role="status" aria-live="polite">
                Clique nas portas disponíveis para adicioná-las ao circuito. Altere as entradas e pressione TEST para obter saída 1.
            </p>
            <section id="gate-palette" class="gate-palette" aria-label="Adicionar portas disponíveis ao circuito"></section>

            <dialog id="circuit-info" class="circuit-info">
                <div class="circuit-info-content">
                    <h2>Como montar o circuito</h2>
                    <p><?= nl2br(Http::e($fase['enunciado'])) ?></p>
                    <p>As portas disponíveis são definidas pelo administrador. Clique em uma porta na faixa inferior para adicioná-la ao circuito; as conexões são refeitas automaticamente. Use × na porta para removê-la.</p>
                    <p>O botão TEST calcula a saída real pelas portas conectadas. A fase é concluída quando o LED recebe <strong>1</strong>.</p>
                    <p>Use Restaurar entradas para voltar aos estados iniciais configurados para esta fase.</p>
                    <form method="dialog"><button class="btn-admin">Fechar</button></form>
                </div>
            </dialog>
        </section>
        <script src="/assets/js/LogicaCircuito.js?v=2" defer></script>
        <script src="/assets/js/Fase.js?v=2" defer></script>

    <?php else: ?>
        <!-- ============ MAPA DE FASES ============ -->
        <h1>Fases</h1>
        <?php if ($erro): ?><p class="flash flash-erro"><?= Http::e($erro) ?></p><?php endif; ?>

        <?php include __DIR__ . '/components/fase-map.php'; ?>
    <?php endif; ?>
    </main>
</body>
</html>
