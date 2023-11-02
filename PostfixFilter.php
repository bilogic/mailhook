<?php

require_once 'Router.php';
require_once 'FileMutex.php';

use Xesau\HttpRequestException;

/**
 * Provide the basic functions for a postfix filter
 */
class PostfixFilter
{
    private $folder = null;

    /**
     * Folder to store the emails
     *
     * @param  string  $folder
     */
    public function folder($folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    public function setup($folder = null): self
    {
        $f = $folder ?? $this->folder;

        if ($f == null) {
            throw new Exception('Must specify a folder to setup');
        }
        $this->folder = $f;

        @mkdir(__DIR__."/{$this->folder}/lock", 0775, true); // use for mutex locks
        @mkdir(__DIR__."/{$this->folder}/tell", 0775, true); // track if remote end informed?
        @mkdir(__DIR__."/{$this->folder}/read", 0775, true); // track if email been read?

        @chmod(__DIR__."/{$this->folder}/lock", 0775);
        @chmod(__DIR__."/{$this->folder}/tell", 0775);
        @chmod(__DIR__."/{$this->folder}/read", 0775);

        @chown(__DIR__."/{$this->folder}/lock", 'www-data');
        @chown(__DIR__."/{$this->folder}/tell", 'www-data');
        @chown(__DIR__."/{$this->folder}/read", 'www-data');

        file_put_contents('transport_maps', $this->getTransportMaps());

        return $this;
    }

    public function remove()
    {
        $removables = glob(__DIR__."/{$this->folder}/read/*");
        foreach ($removables as $removable) {
            $filename = basename($removable);
            $mailfile = __DIR__."/{$this->folder}/$filename";
            $readfile = __DIR__."/{$this->folder}/read/$filename";

            @unlink($readfile);
            @unlink($mailfile);
        }
    }

    public function notify()
    {
        $removables = glob(__DIR__."/{$this->folder}/tell/*");

        foreach ($removables as $removable) {
            $filename = basename($removable);

            $lockfile = __DIR__."/{$this->folder}/lock/$filename";
            $mailfile = __DIR__."/{$this->folder}/$filename";
            $tellfile = __DIR__."/{$this->folder}/tell/$filename";
            $basename = basename($lockfile);

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {
                if (file_exists($tellfile)) {
                    if (! file_exists($mailfile)) {
                        unlink($tellfile);  // if mail no longer exists, then we don't need tell also
                    } else {

                        echo "- Need to notify for $basename\n";

                        $tell = json_decode(file_get_contents($tellfile), true);
                        $dst = strtolower($tell[0]);
                        $config = $this->getConfig();

                        if (! isset($config[$dst])) {
                            echo "- Cannot find config for $dst\n";
                        } else {
                            $url = $config[$dst].urlencode($basename);
                            // $message = "Mail for $dst, piping to: {$url}";
                            // echo "$message\n";
                            // file_put_contents('/var/log/pipe.log', $message, FILE_APPEND);

                            if ($this->isNotifyUrlSuccess($url)) {
                                echo "- Notified success for $url\n";
                                @unlink($tellfile);
                            } else {
                                echo "- Notified failed for $url\n";
                            }
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
        return $this->read($filename, true);
    }

    public function read($filename, $delete = false): self
    {
        $lockfile = __DIR__."/{$this->folder}/lock/$filename";
        $mailfile = __DIR__."/{$this->folder}/$filename";
        $readfile = __DIR__."/{$this->folder}/read/$filename";

        if (file_exists($mailfile)) {

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {
                header('Content-Type: text/plain');
                readfile($mailfile);
                touch($readfile);
                if ($delete) {
                    @unlink($mailfile);
                    @unlink($readfile);
                }
                $mutex->unlock();

                return $this;
            }

        }

        throw new HttpRequestException('Page not found', 404);
    }

    public function save()
    {
        global $argv;

        while (1) {
            $filename = $this->guidv4();
            $lockfile = __DIR__."/{$this->folder}/lock/$filename";
            $mailfile = __DIR__."/{$this->folder}/$filename";
            $tellfile = __DIR__."/{$this->folder}/tell/$filename";

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
            $transport_maps .= "/^$email/ forwardmail:dummy\r\n";
        }

        $transport_maps .= '/^bounce@e115.com/   bulkbounce:';
        $transport_maps .= '/.*/ :';

        return $transport_maps;
    }

    private function isNotifyUrlSuccess($url)
    {
        // $url = 'http://www.google.com/asdkfhasdf';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            return true;
        }

        return false;
    }
}
