<?php
declare(strict_types=1);

final class Http
{
    public static function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Escapa texto para HTML. */
    public static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    /** Tamanho em caracteres (UTF-8) sem depender da extensão mbstring. */
    public static function len(string $texto): int
    {
        return (int) preg_match_all('/./su', $texto);
    }

    /** Segundos -> "2m 05s", "1h 03m" ou "—" quando não há dado. */
    public static function duracao(int|float|string|null $segundos): string
    {
        if ($segundos === null || $segundos === '' || !is_numeric($segundos)) {
            return '—';
        }
        $s = (int) round((float) $segundos);
        if ($s >= 3600) {
            return intdiv($s, 3600) . 'h ' . str_pad((string) intdiv($s % 3600, 60), 2, '0', STR_PAD_LEFT) . 'm';
        }
        if ($s >= 60) {
            return intdiv($s, 60) . 'm ' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT) . 's';
        }
        return $s . 's';
    }
}
