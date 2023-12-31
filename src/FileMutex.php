<?php

namespace App;

class FileMutex
{
    private $resource = null;

    private $timeout = 3;

    private $lockfile = null;

    public function lockfile($lockfile)
    {
        $this->lockfile = $lockfile;

        return $this;
    }

    public function lock()
    {
        $this->resource = fopen($this->lockfile, 'w');

        if ($this->resource === false) {
            $error = print_r(error_get_last(), true);
            syslog(LOG_INFO, '[FileMutex.php] running as '.get_current_user());
            syslog(LOG_INFO, '[FileMutex.php] '.$error);
        } else {
            $lock = false;
            for ($i = 0; $i < $this->timeout && ! ($lock = flock($this->resource, LOCK_EX | LOCK_NB)); $i++) {
                sleep(1);
            }

            if (! $lock) {
                return false;
            }

            return true;
        }

        sleep(1);

        return false;
    }

    public function unlock()
    {
        $result = flock($this->resource, LOCK_UN);
        fclose($this->resource);

        @unlink($this->lockfile);

        return $result;
    }
}
