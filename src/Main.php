<?php

namespace App;

require_once 'PostfixFilter.php';
require_once '../.env.php';

use Xesau\Router;

syslog(LOG_INFO, '[Main] running as '.get_current_user());

$router = new Router(function ($method, $path, $statusCode, $exception) {
    http_response_code($statusCode);
    include 'views/error.html';
});

$router->get('/', function () {
    if (! isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Error';
        exit;
    }

    header('HTTP/1.0 401 Unauthorized');
    echo 'Error';
});

$router->post('/api/v3/mailgun', function () {
    if (! isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        // Text to send if user hits Cancel button
        echo 'Error';
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
        // $cmd = <<<CMD
        // /usr/sbin/sendmail -i -F "zz" -f {$_POST['from']} {$_POST['to']} < {$file}
        // CMD;

        // using cat allows us to inject additional headers
        // $header = 'X-track-id: '.$_POST['track_id'].PHP_EOL;
        $header = 'X-mailhook-id: '.(new PostfixFilter)->guidv4();
        $cmd = <<<CMD
        echo "$header" | cat - {$file} | /usr/sbin/sendmail -i -F "Ticket" -f {$_POST['from']} {$_POST['to']}
        CMD;
        $output = shell_exec($cmd);

        $pid = substr(md5(getmypid()), 6);

        syslog(LOG_INFO, "[$pid] >>> $cmd");
        syslog(LOG_INFO, "[$pid] <<< $output");
    }
});

$router->get('/pipe/(.*)', function ($a) {
    $delete = true;
    if (isset($_GET['delete']) && $_GET['delete'] == 0) {
        $delete = false;
    }

    $m = new PostfixFilter;
    $m->folder(__DIR__.'/../mail-forward')
        ->read($a, $delete);
});

$router->route(['OPTION', 'PUT'], '/test', 'PageController::test');
$router->dispatchGlobal();
