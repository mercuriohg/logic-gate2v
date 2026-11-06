  <?php
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
?>
  <!doctype html>
  <html lang="pt-br">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Projeto</title>
    <link rel="stylesheet" href="/assets/css/style.css" />
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

    <main id="container-main">
      <h2>Sobre o Projeto</h2>
      <p>
        O Logic Gate é uma plataforma web educacional gamificada criada com o
        objetivo de facilitar o aprendizado de lógica computacional para
        estudantes iniciantes da área da computação. A proposta do projeto é
        transformar conteúdos considerados complexos em uma experiência mais
        interativa, intuitiva e divertida, utilizando desafios em formato de
        fases e puzzles lógicos.<br /><br />

        A plataforma simula circuitos digitais de maneira visual, permitindo que
        os usuários interajam diretamente com portas lógicas e resolvam desafios
        progressivos para avançar no sistema. Inspirado em jogos arcade e retrô,
        o projeto busca unir aprendizado e entretenimento, incentivando a
        prática da lógica de forma dinâmica e motivadora.<br /><br />

        Além de auxiliar no desenvolvimento do raciocínio lógico, o projeto
        também tem como foco proporcionar uma experiência moderna e interativa,
        aproximando os estudantes da computação através de uma abordagem mais
        prática e acessível.
      </p>
      <h2>Como funciona a logica das portas?</h2>
      <div id="info-porta">

      </div>
      <div id="portas">
        <div class="logica-door" id="and">
          <h3>Porta AND</h3>
          <img src="/assets/img/and.png" alt="Porta lógica and" height="100em">
        </div>
        <div class="logica-door" id="not">
          <h3>Porta NOT</h3>
          <img src="/assets/img/not.png" alt="Porta lógica not" height="100em">

        </div>
        <div class="logica-door" id="or">
          <h3>Porta OR</h3>
          <img src="/assets/img/or.png" alt="Porta lógica or" height="100em">
        </div>
        <div class="logica-door" id="xor">
          <h3>Porta XOR</h3>
          <img src="/assets/img/xor2.png" alt="Porta lógica xor" height="100em">
        </div>
        <div class="logica-door" id="nand">
          <h3>Porta NAND</h3>
          <img src="/assets/img/nand.png" alt="Porta lógica nand" height="100em">

        </div>
        <div class="logica-door" id="nor">
          <h3>Porta NOR</h3>
          <img src="/assets/img/nor.png" alt="Porta lógica nor" height="100em">

        </div>
        <div class="logica-door" id="xnor">
          <h3>Porta XNOR</h3>
          <img src="/assets/img/xnor.png" alt="Porta lógica xnor" height="100em">
        </div>
      </div>
    </main>
    <script src="/assets/js/Card.js"></script>
  </body>
  </html>
