<?php
// Chamado pelo RouterController em POST /cadastro
require_once __DIR__ . '/../controllers/CadastroController.php';

CadastroController::register();
