
<!DOCTYPE html>
<?php
require_once __DIR__ . '/../config/bootstrap.php';
Session::start();

$loginError = Session::flash('login_error');
?>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
          <?php include __DIR__ . '/components/header.php'; ?>    

        <main id="container-main">
            <div id="login-container">
            <form action="/login" method="post" id="login-form">
            <?= Session::csrfField() ?>
            <h2>Login</h2>
            <div id="user-box">
              <label>Usuário ou e-mail:</label>
              <input type="text" id="username" placeholder="Usuário ou e-mail" class="input-user" name="username">
            </div>

            <div id="password-box">
              <label>Senha:</label>
              <input type="password" id="password" placeholder="Senha" class="input-user" name="password">
                </div>
                <div id="button-box">
            <input type="submit" value="Login" class="btn-login">
                </div>
                <div class="labels">
                  <span id="error-message"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></span>
                    <label class="style-label">Não tem uma conta? <a href="/cadastro">Cadastre-se</a></label>
                   <label  class="style-label"><a href="#">Esqueci minha senha</a></label> 
                </div>
                </form>
            </div>
        </main>
</body>
</html>
