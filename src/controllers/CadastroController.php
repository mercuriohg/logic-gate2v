<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

final class CadastroController
{
    /** POST /cadastro — campos: username, email, password, confirm-password. */
    public static function register(): void
    {
        Session::start();
        Session::exigirCsrf('cadastro_error', '/cadastro');

        $nome = trim((string) ($_POST['username'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['password'] ?? '');
        $confirmacao = (string) ($_POST['confirm-password'] ?? '');

        $erro = self::validar($nome, $email, $senha, $confirmacao);
        if ($erro !== null) {
            Session::setFlash('cadastro_error', $erro);
            Http::redirect('/cadastro');
        }

        $users = new UserModel();
        $emUso = $users->campoEmUso($nome, $email);
        if ($emUso === 'nome' || (new AdminModel())->loginExists($nome)) {
            Session::setFlash('cadastro_error', 'Esse nome de usuário já está em uso.');
            Http::redirect('/cadastro');
        }
        if ($emUso === 'email') {
            Session::setFlash('cadastro_error', 'Esse e-mail já está cadastrado.');
            Http::redirect('/cadastro');
        }

        try {
            // A confirmação de senha só é validada aqui; nunca é salva no banco.
            $id = $users->create($nome, $email, $senha);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) { // corrida entre dois cadastros iguais
                Session::setFlash('cadastro_error', 'Nome de usuário ou e-mail já cadastrado.');
                Http::redirect('/cadastro');
            }
            throw $e;
        }

        Session::loginUser(['id' => $id, 'nome_usuario' => $nome]);
        Http::redirect('/');
    }

    private static function validar(string $nome, string $email, string $senha, string $confirmacao): ?string
    {
        if ($nome === '' || $email === '' || $senha === '' || $confirmacao === '') {
            return 'Por favor, preencha todos os campos.';
        }
        if (Http::len($nome) < 3 || Http::len($nome) > 50) {
            return 'O nome de usuário deve ter entre 3 e 50 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            return 'E-mail inválido.';
        }
        if (strlen($senha) < 6) {
            return 'A senha deve ter pelo menos 6 caracteres.';
        }
        if ($senha !== $confirmacao) {
            return 'As senhas não coincidem.';
        }
        return null;
    }
}
