<?php

require_once 'MessageHelper.php';

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

    $m = new MessageHelper;
    $m->read($a);
    // $m->readAndDelete($a);

});

$router->route(['OPTION', 'PUT'], '/test', 'PageController::test');
$router->dispatchGlobal();
