<?php

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

        $lock = false;
        for ($i = 0; $i < $this->timeout && ! ($lock = flock($this->resource, LOCK_EX | LOCK_NB)); $i++) {
            sleep(1);
        }

        if (! $lock) {
            return false;
        }

        return true;
    }

    public function unlock()
    {
        $result = flock($this->resource, LOCK_UN);
        fclose($this->resource);

        @unlink($this->lockfile);

        return $result;
    }
}
