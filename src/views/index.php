<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/PhaseController.php';

Session::start();
$userId = Session::userId();
$isAdmin = Session::adminId() !== null;
$mapa = $userId !== null ? PhaseController::map($userId) : [];
?>
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Logic Gate</title>
    <link rel="stylesheet" href="/assets/css/style.css">
     <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
      integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
  </head>
  <body>
   <?php include __DIR__ . '/components/header.php'; ?>

   <main id="container-main" class="home">
     <?php if ($userId !== null): ?>
        <!-- jogador logado: mapa de fases -->
        <h2>Escolha sua fase</h2>
        <?php include __DIR__ . '/components/fase-map.php'; ?>

     <?php elseif ($isAdmin): ?>
        <!-- administrador -->
        <div class="home-hero">
          <h2>Bem-vindo, administrador</h2>
          <div class="home-text">Gerencie usuários, fases, ranking e estatísticas do jogo.</div>
          <div class="home-actions"><a href="/admin" class="btn">Abrir painel</a></div>
        </div>

     <?php else: ?>
        <!-- visitante -->
        <div class="home-hero">
          <h2>Logic Gate</h2>
         
        </div>
     <?php endif; ?>
   </main>

  </body>
</html>
