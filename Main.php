<?php

// If you don't have an autoloader
require_once 'Router.php';

use Xesau\HttpRequestException;
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
    $file = __DIR__."/mail/$a";
    if (file_exists($file)) {
        header('Content-Type: text/plain');
        readfile(__DIR__.'/mail/email');
    } else {
        throw new HttpRequestException('Page not found', 404);
    }

});

$router->route(['OPTION', 'PUT'], '/test', 'PageController::test');
$router->dispatchGlobal();
