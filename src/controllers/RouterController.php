<?php

require_once __DIR__ . '/../config/bootstrap.php';

class RouterController
{
    public static function route($uri)
    {
        $routes = [
            // PAGES
            '' => __DIR__ . '/../views/index.php',
            'login' => __DIR__ . '/../views/login.php',
            'projeto' => __DIR__ . '/../views/projeto.php',
            'cadastro' => __DIR__ . '/../views/cadastro.php',
            'profile' => __DIR__ . '/../views/profile.php',
            'fase' => __DIR__ . '/../views/fase.php',
            'admin' => __DIR__ . '/../views/admin/adm.php',
            'ranking' => __DIR__ . '/../views/ranking.php',
            'admin/phases' => __DIR__ . '/../views/admin/admin-phases.php',
            'admin/users' => __DIR__ . '/../views/admin/admin-users.php',
            'admin/ranking' => __DIR__ . '/../views/admin/admin-ranking.php',
            'admin/stats' => __DIR__ . '/../views/admin/admin-stats.php',

        ];

        $method = $_SERVER['REQUEST_METHOD'];

        Session::registrarAtividade();

        if ($uri === 'login' && $method === 'POST') {
            require __DIR__ . '/../api/Login.php';
            return;
        }

        if ($uri === 'cadastro' && $method === 'POST') {
            require __DIR__ . '/../api/Cadastro.php';
            return;
        }

        if ($uri === 'logout') {
            require_once __DIR__ . '/../api/Logout.php';
            Logout::logout();
            return;
        }

        // ---- ações do painel admin (POST) ----
        if ($method === 'POST') {
            $postRoutes = [
                'admin/phases' => ['AdminController', 'savePhase'],
                'admin/phases/delete' => ['AdminController', 'deletePhase'],
                'admin/users/delete' => ['AdminController', 'deleteUser'],
                'api/fase/tentativa' => ['ProgressController', 'attempt'],
            ];
            if (isset($postRoutes[$uri])) {
                [$classe, $acao] = $postRoutes[$uri];
                require_once __DIR__ . "/{$classe}.php";
                $classe::$acao();
                return;
            }
        }

        // ---- API JSON do jogo (GET) ----
        if ($method === 'GET') {
            $getRoutes = [
                'api/fases' => ['PhaseController', 'apiMap'],
                'api/fase' => ['PhaseController', 'apiOpen'],
            ];
            if (isset($getRoutes[$uri])) {
                [$classe, $acao] = $getRoutes[$uri];
                require_once __DIR__ . "/{$classe}.php";
                $classe::$acao();
                return;
            }
        }

        if ($method === 'GET' && isset($routes[$uri])) {
            require $routes[$uri];
            return;
        }

        http_response_code(404);
        require __DIR__ . '/../views/404.php';
    }
}
