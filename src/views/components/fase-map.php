<?php
/**
 * Mapa de fases em trilha (esqueleto "formato de fases do projeto").
 * Entrada: $mapa = PhaseController::map($usuarioId)
 */
$n = count($mapa);
$nomeDificuldade = ['facil' => 'Fácil', 'medio' => 'Média', 'dificil' => 'Difícil'];

if ($n === 0): ?>
    <div class="trilha-vazia">Ainda não há fases cadastradas. Volte em breve!</div>
<?php return; endif;

$concluidas = count(array_filter($mapa, static fn($f) => $f['concluida']));
$W = 640;
$passoY = 105;
$margemY = 60;
$H = $margemY * 2 + ($n - 1) * $passoY;

// posição de cada fase: trilha sinuosa descendo pela tela
$pontos = [];
$atual = null;
foreach ($mapa as $i => $f) {
    $pontos[$i] = [(int) round($W / 2 + 210 * sin($i * 0.85)), $margemY + $i * $passoY];
    if ($atual === null && $f['desbloqueada'] && !$f['concluida']) {
        $atual = $i;
    }
}
$curto = static function (string $t, int $max = 22): string {
    if (Http::len($t) <= $max) {
        return $t;
    }
    preg_match('/^.{' . ($max - 1) . '}/su', $t, $m);
    return ($m[0] ?? '') . '…';
};
?>
<div class="trilha">
    <div class="trilha-progresso">
        <?= $concluidas ?> de <?= $n ?> fases concluídas
        <span class="trilha-barra"><span style="width: <?= (int) round($concluidas / $n * 100) ?>%"></span></span>
    </div>

    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="group" aria-label="Mapa de fases">
        <?php for ($i = 1; $i < $n; $i++): ?>
            <line class="trilha-linha <?= $mapa[$i - 1]['concluida'] ? 'done' : '' ?>"
                  x1="<?= $pontos[$i - 1][0] ?>" y1="<?= $pontos[$i - 1][1] ?>"
                  x2="<?= $pontos[$i][0] ?>" y2="<?= $pontos[$i][1] ?>"></line>
        <?php endfor; ?>

        <?php foreach ($mapa as $i => $f):
            [$cx, $cy] = $pontos[$i];
            $estado = $f['concluida'] ? 'concluida' : ($f['desbloqueada'] ? ($i === $atual ? 'atual' : 'liberada') : 'bloqueada');
            $dica = 'Fase ' . $f['ordem'] . ' — ' . $f['nome'] . ' (' . ($nomeDificuldade[$f['dificuldade']] ?? $f['dificuldade'])
                  . ') · +' . $f['xp_recompensa'] . ' XP · +' . $f['pontos_recompensa'] . ' pts'
                  . ($f['concluida'] ? ' · concluída' : (!$f['desbloqueada'] ? ' · bloqueada' : ''));
            $tag = $f['desbloqueada'] ? 'a' : 'g';
            $href = $f['desbloqueada'] ? ' href="/fase?id=' . (int) $f['id'] . '"' : '';
        ?>
            <<?= $tag ?> class="node <?= $estado ?>"<?= $href ?>>
                <title><?= Http::e($dica) ?></title>
                <rect x="<?= $cx - 28 ?>" y="<?= $cy - 28 ?>" width="56" height="56" rx="10"></rect>
                <text class="num" x="<?= $cx ?>" y="<?= $cy ?>" dy=".35em"><?= $f['concluida'] ? '✔' : (int) $f['ordem'] ?></text>
                <text class="label" x="<?= $cx ?>" y="<?= $cy + 48 ?>"><?= Http::e($curto($f['nome'])) ?></text>
                <?php if ($i === 0): ?><text class="inicio" x="<?= $cx ?>" y="<?= $cy - 38 ?>">INÍCIO</text><?php endif; ?>
                <?php if ($i === $atual): ?><text class="aqui" x="<?= $cx + 44 ?>" y="<?= $cy ?>" dy=".35em">◀ jogar</text><?php endif; ?>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </svg>
</div>
