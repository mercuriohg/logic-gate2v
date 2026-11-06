<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/UserController.php';

$userId = Session::requireUser();          // sem login -> /login
$perfil = UserController::profileData($userId);
if ($perfil === null) {                    // usuário apagado pelo admin com sessão aberta
    Http::redirect('/logout');
}

$username = $perfil['username'];
$profilePhoto = $_SESSION['profile_photo'] ?? null;
$score = $perfil['score'];
$xp = $perfil['xp'];
$nivel = $perfil['nivel'];
$completedPhases = $perfil['completed_phases'];
$averageTimeSeconds = $perfil['average_time_seconds'];
$initials = strtoupper(substr($username, 0, 2));

$formattedAverageTime = 'Ainda não disponível';
if (is_numeric($averageTimeSeconds) && (float) $averageTimeSeconds >= 0) {
    $minutes = floor((float) $averageTimeSeconds / 60);
    $seconds = (int) $averageTimeSeconds % 60;
    $formattedAverageTime = sprintf('%02d:%02d min', $minutes, $seconds);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil | Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>

    <main class="profile-page">
        <section class="profile-hero" aria-labelledby="profile-title">
            <div class="profile-avatar" aria-label="Foto de perfil de <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($profilePhoto): ?>
                    <img src="<?php echo htmlspecialchars($profilePhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
                <?php else: ?>
                    <span><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-heading">
                <p class="profile-kicker">Área do jogador</p>
                <h1 id="profile-title"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="profile-status">Nível <?php echo (int) $nivel; ?> · Pronto para o próximo desafio?</p>
            </div>
            <a class="profile-logout" href="/logout">Sair</a>
        </section>

        <section class="profile-stats" aria-label="Resumo do desempenho">
            <article class="profile-stat">
                <span class="stat-label">Pontuação</span>
                <strong><?php echo number_format($score, 0, ',', '.'); ?></strong>
                <span class="stat-detail">pontos acumulados</span>
            </article>
            <article class="profile-stat profile-stat-highlight">
                <span class="stat-label">XP total</span>
                <strong><?php echo number_format($xp, 0, ',', '.'); ?></strong>
                <span class="stat-detail">experiência conquistada</span>
            </article>
            <article class="profile-stat">
                <span class="stat-label">Fases concluídas</span>
                <strong><?php echo number_format($completedPhases, 0, ',', '.'); ?></strong>
                <span class="stat-detail">desafios vencidos</span>
            </article>
            <article class="profile-stat">
                <span class="stat-label">Média de tempo</span>
                <strong class="stat-time"><?php echo htmlspecialchars($formattedAverageTime, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="stat-detail">por fase concluída</span>
            </article>
        </section>

        <section class="profile-progress" aria-labelledby="progress-title">
            <div>
                <p class="profile-kicker">Seu progresso</p>
                <h2 id="progress-title">Continue evoluindo</h2>
                <p>Complete novas fases para aumentar sua pontuação e acumular XP.</p>
            </div>
            <a class="profile-primary-action" href="/fase">Jogar agora</a>
        </section>
    </main>
</body>
</html>