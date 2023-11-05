<?php

require_once 'PostfixFilter.php';
require_once '.env.php';

use Xesau\Router;

$router = new Router(function ($method, $path, $statusCode, $exception) {
    http_response_code($statusCode);
    include 'views/error.html';
});

$router->get('/', function () {
    // Home page
    echo 'Hi';
});

$router->post('/', function () {
    if (! isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Text to send if user hits Cancel button';
        exit;
    }

    $userOk = ($_SERVER['PHP_AUTH_USER'] == 'api');
    if (isset($_ENV[$_SERVER['PHP_AUTH_PW']])) {
        $passOk = true;
    }

    if ($userOk and $passOk) {
        $file = ($_FILES['message']['tmp_name']);
        // https://docstore.mik.ua/orelly/networking_2ndEd/tcp/appe_02.htm
        // -t read the to address from the mail
        // -i ignore any dots found in the mail
        // -f send-as who?
        $cmd = "/usr/sbin/sendmail -i -F 'ZZ' -f {$_POST['from']} {$_POST['to']} < {$file}";
        $output = shell_exec($cmd);
        echo $cmd;
        echo $output;
    }
});

$router->get('/pipe/(.*)', function ($a) {
    $delete = true;
    if (isset($_GET['delete']) && $_GET['delete'] == 0) {
        $delete = false;
    }

    $m = new PostfixFilter;
    $m->folder('mail')
        ->read($a, $delete);
});

$router->route(['OPTION', 'PUT'], '/test', 'PageController::test');
$router->dispatchGlobal();
