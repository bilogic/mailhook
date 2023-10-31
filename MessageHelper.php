<?php

require_once 'FileMutex.php';
require_once 'Router.php';

use Xesau\HttpRequestException;

class MessageHelper
{
    public function remove()
    {
        $removables = glob(__DIR__.'/mail/read/*');
        foreach ($removables as $removable) {
            $filename = basename($removable);
            $mailfile = __DIR__."/mail/$filename";
            $readfile = __DIR__."/mail/read/$filename";

            @unlink($readfile);
            @unlink($mailfile);
        }
    }

    public function notify()
    {
        $url = '';
        $removables = glob(__DIR__.'/mail/meta/*');
        foreach ($removables as $removable) {
            $filename = basename($removable);

            $lockfile = __DIR__."/mail/lock/$filename";
            $metafile = __DIR__."/mail/meta/$filename";

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {

                if (file_exists($metafile)) {
                    $ok = file_get_contents("$url?email=$lockfile");
                    if ($ok) {
                        @unlink($metafile);
                    }
                }

                $mutex->unlock();
            }
        }
    }

    public function read($filename)
    {
        $lockfile = __DIR__."/mail/lock/$filename";
        $mailfile = __DIR__."/mail/$filename";
        $readfile = __DIR__."/mail/read/$filename";

        if (file_exists($mailfile)) {

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {
                header('Content-Type: text/plain');
                readfile($mailfile);
                touch($readfile);
                @unlink($mailfile);
                @unlink($readfile);
                $mutex->unlock();
            }

        } else {
            throw new HttpRequestException('Page not found', 404);
        }

    }

    public function save()
    {
        global $argv;

        while (1) {
            $filename = uuid();
            $lockfile = __DIR__."/mail/lock/$filename";
            $mailfile = __DIR__."/mail/$filename";
            $metafile = __DIR__."/mail/meta/$filename";

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {

                if (file_exists($mailfile)) {
                    $mutex->unlock();

                    continue;
                } else {
                    $output = fopen($mailfile, 'a');

                    $input = fopen('php://stdin', 'r');
                    while (! feof($input)) {
                        $line = fread($input, 1024);
                        fwrite($output, $line);
                    }
                    fclose($input);
                    fclose($output);

                    file_put_contents($metafile, json_encode(array_slice($argv, 1)));
                }

                $mutex->unlock();
                break;
            }
        }
    }
}
