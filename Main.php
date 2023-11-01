<?php

require_once 'PostfixFilter.php';

use Xesau\Router;

$router = new Router(function ($method, $path, $statusCode, $exception) {
    http_response_code($statusCode);
    include 'views/error.html';
});

$router->get('/', function () {
    // Home page
    echo 'Hi';
});

$router->get('/pipe/(.*)', function ($a) {
    $delete = true;
    if (isset($_GET['delete']) && $_GET['delete'] == 0) {
        $delete = false;
    }

    $m = new PostfixFilter;
    $m->read($a, $delete);
});

$router->route(['OPTION', 'PUT'], '/test', 'PageController::test');
$router->dispatchGlobal();
