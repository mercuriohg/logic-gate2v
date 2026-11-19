<?php
require_once __DIR__ . '/../config/bootstrap.php';
Session::start();
$error = Session::flash('password_reset_error');
$notice = Session::flash('password_reset_notice');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha - Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=20261118-3">
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    <main id="container-main">
        <div id="login-container" class="password-reset-card">
            <form action="/redefinir-senha" method="post" id="login-form" class="password-reset-form">
                <?= Session::csrfField() ?>
                <h2>Redefinir senha</h2>
                <p class="form-description">Digite o código de 6 dígitos recebido por e-mail e escolha uma nova senha.</p>
                <div id="user-box">
                    <label for="code">Código:</label>
                    <input type="text" id="code" placeholder="000000" class="input-user" name="code"
                        inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                </div>
                <div id="password-box">
                    <label for="password">Nova senha:</label>
                    <input type="password" id="password" placeholder="Nova senha" class="input-user" name="password"
                        minlength="6" autocomplete="new-password" required>
                    <label for="confirm-password">Confirmar senha:</label>
                    <input type="password" id="confirm-password" placeholder="Confirmar senha" class="input-user"
                        name="confirm-password" minlength="6" autocomplete="new-password" required>
                </div>
                <div id="button-box">
                    <input type="submit" value="Redefinir senha" class="btn-login">
                </div>
                <div class="labels">
                    <span class="form-message form-message-notice"><?= Http::e($notice) ?></span>
                    <span class="form-message form-message-error"><?= Http::e($error) ?></span>
                    <label class="style-label"><a href="/recuperar-senha">Solicitar outro código</a></label>
                    <label class="style-label"><a href="/login">Voltar para o login</a></label>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
