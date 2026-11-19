<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$isAdmin = !empty($_SESSION['is_admin']);
$username = $_SESSION['username'] ?? null;
?>
<header id="container-header">
  <img src="/assets/img/logic-gate.png" alt="Logo do Logic Gate" id="logo">
  <div id="toogle-div">
    <i id="menu-toogle" class="menu-btn" aria-label="Abrir menu">☰</i>
    <i id="x-mark" class="menu-btn close" aria-label="Fechar menu">×</i>
  </div>
  <ul id="list-ul">
      <li><a href="/ranking" class="btn">Ranking</a></li>
      <li><a href="/projeto" class="btn">Projeto</a></li>
      <?php if ($isAdmin): ?>
          <li><a href="/admin" class="btn">Admin</a></li>
          <li><a href="/logout" class="btn">Logout</a></li>
        <?php elseif ($username): ?>
          <li><a href="/profile" class="btn"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php else: ?>
          <li><a href="/login" class="btn">Login</a></li>
      <?php endif; ?>
      <li><a href="/" class="btn">Inicio</a></li>
  </ul>
</header>
<script src="/assets/js/Toogle.js" defer></script>