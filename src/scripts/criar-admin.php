<?php
// Cria (ou troca a senha de) um administrador.
// Docker:  docker compose exec php php scripts/criar-admin.php <login> <senha>
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Execute apenas pela linha de comando.\n");
}
require_once __DIR__ . '/../config/bootstrap.php';

[$login, $senha] = [trim($argv[1] ?? ''), $argv[2] ?? ''];
if ($login === '' || strlen($senha) < 6) {
    fwrite(STDERR, "Uso: php scripts/criar-admin.php <login> <senha (mín. 6 caracteres)>\n");
    exit(1);
}

$pdo = Database::getConnection();
$admins = new AdminModel($pdo);

if ($admins->loginExists($login)) {
    $pdo->prepare('UPDATE admin SET senha_hash = :h WHERE login = :l')
        ->execute([':h' => password_hash($senha, PASSWORD_DEFAULT), ':l' => $login]);
    echo "Senha do admin '{$login}' atualizada.\n";
} else {
    $id = $admins->create($login, $senha);
    echo "Admin criado: id={$id} login={$login}\n";
}
