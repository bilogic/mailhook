<?php

require_once 'Router.php';
require_once 'FileMutex.php';

use Xesau\HttpRequestException;

class MessageHelper
{
    public function setup()
    {
        @mkdir(__DIR__.'/mail/lock', 0775, true); // use for mutex locks
        @mkdir(__DIR__.'/mail/tell', 0775, true); // track if remote end informed?
        @mkdir(__DIR__.'/mail/read', 0775, true); // track if email been read?

        @chmod(__DIR__.'/mail/lock', 0775);
        @chmod(__DIR__.'/mail/tell', 0775);
        @chmod(__DIR__.'/mail/read', 0775);

        file_put_contents('transport_maps', $this->getTransportMaps());
    }

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
        $removables = glob(__DIR__.'/mail/tell/*');

        foreach ($removables as $removable) {
            $filename = basename($removable);

            $lockfile = __DIR__."/mail/lock/$filename";
            $tellfile = __DIR__."/mail/tell/$filename";

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {

                if (file_exists($tellfile)) {
                    echo "Found $tellfile\n";

                    $tell = json_decode(file_get_contents($tellfile), true);
                    $dst = strtolower($tell[0]);
                    $config = $this->getConfig();

                    if (! isset($config[$dst])) {
                        echo "Cannot find $dst\n";
                    } else {
                        echo "Found $dst\n";
                        $url = $config[$dst].urlencode(basename($lockfile));
                        $message = "Mail for $dst, piping to: {$url}";
                        echo "$message\n";
                        file_put_contents('/var/log/pipe.log', $message, FILE_APPEND);

                        // $ok = file_get_contents("$url?email=$lockfile");
                        $ok = true;

                        if ($ok) {
                            @unlink($tellfile);
                        }
                    }

                }

                $mutex->unlock();
            }
        }
    }

    /**
     * Read an email, it will be deleted once read
     *
     * @param  string  $filename
     */
    public function readAndDelete($filename): self
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

        return $this;
    }

    public function save()
    {
        global $argv;

        while (1) {
            $filename = $this->guidv4();
            $lockfile = __DIR__."/mail/lock/$filename";
            $mailfile = __DIR__."/mail/$filename";
            $tellfile = __DIR__."/mail/tell/$filename";

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

                    file_put_contents($tellfile, json_encode(array_slice($argv, 1)));
                }

                $mutex->unlock();
                break;
            }
        }
        // $this->notify();
    }

    /**
     * 8888888b.          d8b                   888
     * 888   Y88b         Y8P                   888
     * 888    888                               888
     * 888   d88P 888d888 888 888  888  8888b.  888888 .d88b.
     * 8888888P"  888P"   888 888  888     "88b 888   d8P  Y8b
     * 888        888     888 Y88  88P .d888888 888   88888888
     * 888        888     888  Y8bd8P  888  888 Y88b. Y8b.
     * 888        888     888   Y88P   "Y888888  "Y888 "Y8888
     */
    /**
     * Generate a v4 guid prefixed with timestamp
     *
     * @param  string  $data
     */
    private function guidv4($data = null): string
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        // Output the 36 character UUID.
        return strtotime('now').'-'.vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Read our email and URL settings
     */
    private function getConfig(): array
    {
        $configFile = __DIR__.'/config.json';
        $config = json_decode(file_get_contents($configFile), true);

        return $config;
    }

    /**
     * Generate a postfix transport_map contents from config
     */
    private function getTransportMaps(): string
    {
        // create a file that looks like this
        // /^me@e115.com/          myhook:dummy
        // /^ticket@bookfirst.cc/  myhook:dummy
        // /.*/                    :
        $records = $this->getConfig();
        $transport_maps = '';
        foreach ($records as $email => $record) {
            $transport_maps .= "/^$email/ myhook:dummy\r\n";
        }
        $transport_maps .= '/.*/ :';

        return $transport_maps;
    }
}
