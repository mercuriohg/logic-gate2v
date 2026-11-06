<?php

require_once __DIR__ . '/../controllers/RouterController.php';

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

RouterController::route($uri);