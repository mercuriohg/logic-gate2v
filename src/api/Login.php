<?php
// Chamado pelo RouterController em POST /login
require_once __DIR__ . '/../controllers/LoginController.php';

LoginController::login();
