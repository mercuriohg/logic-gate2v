<?php
require_once __DIR__ . '/../config/bootstrap.php';
Session::start();
$error = Session::flash('password_reset_error');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=20261118-3">
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    <main id="container-main">
        <div id="login-container" class="password-reset-card">
            <form action="/recuperar-senha" method="post" id="login-form" class="password-reset-form">
                <?= Session::csrfField() ?>
                <h2>Recuperar senha</h2>
                <p class="form-description">Informe o e-mail cadastrado para receber seu código de recuperação.</p>
                <div id="email-box">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" placeholder="Seu e-mail" class="input-user" name="email"
                        maxlength="150" autocomplete="email" required>
                </div>
                <div id="button-box">
                    <input type="submit" value="Enviar código" class="btn-login">
                </div>
                <div class="labels">
                    <span class="form-message form-message-error"><?= Http::e($error) ?></span>
                    <label class="style-label"><a href="/login">Voltar para o login</a></label>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
