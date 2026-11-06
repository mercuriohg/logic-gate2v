<?php
declare(strict_types=1);

final class Nivel
{
    /** nivel = floor(xp_total / XP_POR_NIVEL) + 1  (XP_POR_NIVEL via variável de ambiente, padrão 100) */
    public static function calcular(int $xpTotal): int
    {
        $porNivel = max(1, (int) (getenv('XP_POR_NIVEL') ?: 100));
        return intdiv($xpTotal, $porNivel) + 1;
    }
}
