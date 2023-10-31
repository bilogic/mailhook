<?php

require_once 'Router.php';
require_once 'FileMutex.php';

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

    private public function guidv4($data = null)
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        // Output the 36 character UUID.
        return strtotime("now")."-".vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function save()
    {
        global $argv;

        @mkdir(__DIR__."/mail/lock", 0644);
        @mkdir(__DIR__."/mail/meta", 0644);

        while (1) {
            $filename = $this->guidv4();
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