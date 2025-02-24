<?php

namespace App;

ignore_user_abort(true);
error_reporting(E_ALL);
ini_set('display_errors', false);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/nginx/error.log');

require_once 'Router.php';
require_once 'FileMutex.php';

use Closure;
use Exception;
use Xesau\HttpRequestException;

/**
 * Provide the basic functions for a postfix filter
 */
class PostfixFilter
{
    private $folder = null;

    private $as = 'PostfixFilter';

    public function cache(): static
    {
        $code = <<<'PHP'
        <?php

        // THIS IS AUTOMATICALLY GENERATED FROM CONFIG.JSON
        // DO NOT MODIFY THIS FILE

        $_ENV = [           
        PHP;
        foreach ($this->getConfig() as $email => $params) {
            $code .= "'{$params['key']}' => '$email',".PHP_EOL;
        }
        $code .= '];';

        $filename = "{$this->folder}/../.env.php";
        file_put_contents($filename, $code);

        file_put_contents('sender_logins', $this->getSenderLogins());
        file_put_contents('transport_maps', $this->getTransportMaps());

        return $this;
    }

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

    public function as($as): static
    {
        $this->as = $as;

        return $this;
    }

    public function log($message)
    {
        syslog(LOG_INFO, "[{$this->as}] $message");

        echo '['.date('c').'] '.$message.PHP_EOL;
    }

    public function setup($folder = null): static
    {
        $f = $folder ?? $this->folder;

        if ($f == null) {
            throw new Exception('Must specify a folder to setup');
        }
        $this->folder = $f;
        $dirs[] = "{$this->folder}";
        $dirs[] = "{$this->folder}/lock"; // use for mutex locks
        $dirs[] = "{$this->folder}/tell"; // track if remote end informed?
        $dirs[] = "{$this->folder}/read"; // track if email been read?

        foreach ($dirs as $dir) {
            @mkdir($dir, 0775, true);

            @chmod($dir, 0775);
            @chown($dir, 'www-data');
            @chgrp($dir, 'www-data');
        }

        return $this;
    }

    public function remove()
    {
        $removables = glob("{$this->folder}/read/*");
        foreach ($removables as $removable) {
            $filename = basename($removable);
            $mailfile = "{$this->folder}/$filename";
            $readfile = "{$this->folder}/read/$filename";

            $this->log("- Removing $filename");

            @unlink($readfile);
            @unlink($mailfile);
        }
    }

    public function handler(Closure $closure): static
    {
        $path = realpath("{$this->folder}/tell");
        $tells = glob("$path/*");

        foreach ($tells as $tell) {
            $filename = basename($tell);

            $lockfile = "{$this->folder}/lock/$filename-notify";
            $mailfile = "{$this->folder}/$filename";
            $tellfile = "{$this->folder}/tell/$filename";

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {
                if (file_exists($tellfile)) {
                    if (! file_exists($mailfile)) {
                        unlink($tellfile);  // if mail no longer exists, then we don't need tell also
                    } else {

                        $this->log("- Need to notify for $filename");

                        $tell = json_decode(file_get_contents($tellfile), true);
                        $dst = strtolower($tell[0]);
                        $config = $this->getConfig();

                        if (! isset($config[$dst])) {
                            $this->log("- Cannot find config for $dst");
                        }

                        $result = ($closure)($this, $config[$dst], $tell, $mailfile);

                        // $url = $config[$dst].urlencode($filename);
                        // $message = "Mail for $dst, piping to: {$url}";
                        // $this->log( "$message");
                        // file_put_contents('/var/log/pipe.log', $message, FILE_APPEND);

                        // if ($this->isNotifyUrlSuccess($url)) {
                        if ($result) {
                            $this->log('- Handler was successful');
                            @unlink($tellfile);
                        } else {
                            $this->log('- Handler failed');
                        }

                    }
                }
                $mutex->unlock();
            }
        }

        return $this;
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
        $lockfile = "{$this->folder}/lock/$filename";
        $mailfile = "{$this->folder}/$filename";
        $readfile = "{$this->folder}/read/$filename";

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

    public function save(): static
    {
        global $argv;

        while (1) {
            $filename = $this->guidv4();
            $lockfile = "{$this->folder}/lock/$filename";
            $mailfile = "{$this->folder}/$filename";
            $tellfile = "{$this->folder}/tell/$filename";

            $mutex = (new FileMutex)->lockfile($lockfile);
            if ($mutex->lock()) {

                if (file_exists($mailfile)) {
                    $mutex->unlock();

                    continue; // exits to the while loop
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
                chmod($mailfile, 0770);
                chmod($tellfile, 0770);
                break;
            }
        }

        return $this;
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
    public function guidv4($data = null): string
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
        $configFile = "{$this->folder}/../config.json";
        $config = json_decode(file_get_contents($configFile), true);

        return $config ?? [];
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
            $transport_maps .= "/^$email/ mailhookforward:dummy\r\n";
        }

        $transport_maps .= "/^bounce@e115.com/   mailhookbounce:dummy\r\n";
        $transport_maps .= "/.*/ :\r\n";

        return $transport_maps;
    }

    /**
     * Generate a postfix transport_map contents from config
     */
    private function getSenderLogins(): string
    {
        // create a file that looks like this
        // /^me@e115.com/          myhook:dummy
        // /^ticket@bookfirst.cc/  myhook:dummy
        // /.*/                    :
        $records = $this->getConfig();
        $sender_logins = '';
        foreach ($records as $email => $record) {
            $sender_logins .= "/^.*@{$record['domain']}/    {$record['delivery_by']}".PHP_EOL;
            $sender_logins .= "/^.*@.*\.{$record['domain']}/    {$record['delivery_by']}".PHP_EOL;
        }
        // /^.*@.*\.bookfirst.cc/  sender@bookfirst.cc
        // /^.*@bookfirst.cc/      sender@bookfirst.cc

        return $sender_logins;
    }
}
