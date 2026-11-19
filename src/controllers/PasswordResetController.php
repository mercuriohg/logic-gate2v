<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/EmailController.php';

final class PasswordResetController
{
    public static function requestCode(): void
    {
        Session::start();
        Session::exigirCsrf('password_reset_error', '/recuperar-senha');

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            Session::setFlash('password_reset_error', 'Informe um e-mail válido.');
            Http::redirect('/recuperar-senha');
        }

        $_SESSION['password_reset_email'] = $email;
        $user = (new UserModel())->getByEmail($email);

        if ($user) {
            $reset = new PasswordResetModel();
            $code = (string) random_int(100000, 999999);

            if ($reset->issueCode((int) $user['id'], password_hash($code, PASSWORD_DEFAULT))) {
                try {
                    EmailController::sendPasswordResetCode($email, $code);
                } catch (PHPMailer\PHPMailer\Exception | RuntimeException $e) {
                    $reset->deleteCode((int) $user['id']);
                    error_log('Falha ao enviar código de recuperação: ' . $e->getMessage());
                    Session::setFlash(
                        'password_reset_error',
                        'Não foi possível enviar o código agora. Tente novamente mais tarde.'
                    );
                    Http::redirect('/recuperar-senha');
                }
            }
        }

        Session::setFlash(
            'password_reset_notice',
            'Se o e-mail estiver cadastrado, você receberá um código em instantes.'
        );
        Http::redirect('/validar-codigo');
    }

    public static function resetPassword(): void
    {
        Session::start();
        Session::exigirCsrf('password_reset_error', '/validar-codigo');

        $email = $_SESSION['password_reset_email'] ?? '';
        $code = trim((string) ($_POST['code'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['confirm-password'] ?? '');

        if (!is_string($email) || $email === '') {
            Session::setFlash('password_reset_error', 'Solicite um novo código de recuperação.');
            Http::redirect('/recuperar-senha');
        }
        if (!preg_match('/^\d{6}$/', $code)) {
            Session::setFlash('password_reset_error', 'Informe o código de 6 dígitos recebido por e-mail.');
            Http::redirect('/validar-codigo');
        }
        if (strlen($password) < 6) {
            Session::setFlash('password_reset_error', 'A senha deve ter pelo menos 6 caracteres.');
            Http::redirect('/validar-codigo');
        }
        if ($password !== $confirmation) {
            Session::setFlash('password_reset_error', 'As senhas não coincidem.');
            Http::redirect('/validar-codigo');
        }

        $user = (new UserModel())->getByEmail($email);
        $reset = $user && (new PasswordResetModel())->resetPassword(
            (int) $user['id'],
            $code,
            $password
        );

        if (!$reset) {
            Session::setFlash('password_reset_error', 'Código inválido ou expirado. Solicite outro código.');
            Http::redirect('/validar-codigo');
        }

        unset($_SESSION['password_reset_email']);
        Session::setFlash('login_notice', 'Senha redefinida com sucesso. Faça login.');
        Http::redirect('/login');
    }
}
