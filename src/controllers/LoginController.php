<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

final class LoginController
{
    /**
     * POST /login. O formulário tem um único campo "username":
     *  1) tenta como login do administrador (tabela admin);
     *  2) senão, tenta como nome de usuário ou e-mail do jogador.
     */
    public static function login(): void
    {
        Session::start();
        Session::exigirCsrf('login_error', '/login');

        $login = trim((string) ($_POST['username'] ?? ''));
        $senha = (string) ($_POST['password'] ?? '');

        if ($login === '' || $senha === '') {
            Session::setFlash('login_error', 'Informe usuário e senha.');
            Http::redirect('/login');
        }

        $admin = (new AdminModel())->authenticate($login, $senha);
        if ($admin) {
            Session::loginAdmin($admin);
            Http::redirect('/admin');
        }

        $usuario = (new UserModel())->authenticate($login, $senha);
        if ($usuario) {
            Session::loginUser($usuario);
            Http::redirect('/');
        }

        Session::setFlash('login_error', 'Credenciais inválidas.');
        Http::redirect('/login');
    }
}
