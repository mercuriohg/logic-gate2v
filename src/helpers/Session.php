<?php
declare(strict_types=1);

/**
 * Sessão, flash messages, CSRF e proteção de páginas.
 * Chaves de sessão usadas pelo projeto: username, is_admin (já existiam no header),
 * mais user_id e admin_id.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    // ---------- login ----------
    public static function loginUser(array $usuario): void
    {
        self::start();
        session_regenerate_id(true);
        unset($_SESSION['admin_id']);
        $_SESSION['user_id'] = (int) $usuario['id'];
        $_SESSION['username'] = (string) $usuario['nome_usuario'];
        $_SESSION['is_admin'] = false;
    }

    public static function loginAdmin(array $admin): void
    {
        self::start();
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['username'] = (string) $admin['login'];
        $_SESSION['is_admin'] = true;
    }

    /** Marca o jogador como ativo (grava ultimo_acesso no máx. 1x por minuto). */
    public static function registrarAtividade(): void
    {
        $uid = self::userId();
        if ($uid === null) {
            return;
        }
        if (time() - (int) ($_SESSION['ultimo_registro'] ?? 0) < 60) {
            return;
        }
        $_SESSION['ultimo_registro'] = time();
        try {
            (new UserModel())->registrarAcesso($uid);
        } catch (Throwable $e) {
            error_log('registrarAtividade: ' . $e->getMessage()); // não é essencial: nunca derruba a página
        }
    }

    public static function userId(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function adminId(): ?int
    {
        self::start();
        return !empty($_SESSION['is_admin']) && isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    /** Páginas do jogador: redireciona para /login se não houver jogador logado. */
    public static function requireUser(): int
    {
        $id = self::userId();
        if ($id === null) {
            Http::redirect('/login');
        }
        return $id;
    }

    /** Páginas do admin: redireciona para /login se não for admin. */
    public static function requireAdmin(): int
    {
        $id = self::adminId();
        if ($id === null) {
            Http::redirect('/login');
        }
        return $id;
    }

    // ---------- flash ----------
    public static function setFlash(string $key, string $message): void
    {
        self::start();
        $_SESSION['flash'][$key] = $message;
    }

    /** Lê e apaga a mensagem. */
    public static function flash(string $key): string
    {
        self::start();
        $msg = $_SESSION['flash'][$key] ?? '';
        unset($_SESSION['flash'][$key]);
        return (string) $msg;
    }

    // ---------- CSRF ----------
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . Http::e(self::csrfToken()) . '">';
    }

    /** Valida o token enviado (POST csrf_token ou header X-CSRF-Token). */
    public static function csrfValido(): bool
    {
        $enviado = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($enviado) && $enviado !== '' && hash_equals(self::csrfToken(), $enviado);
    }

    /** Para formulários HTML: se o token for inválido, volta para $voltarPara com mensagem. */
    public static function exigirCsrf(string $flashKey, string $voltarPara): void
    {
        if (!self::csrfValido()) {
            self::setFlash($flashKey, 'Sessão expirada. Tente novamente.');
            Http::redirect($voltarPara);
        }
    }
}
