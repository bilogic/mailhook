<?php

namespace Tests\Feature;

use App\PostfixFilter;
use PHPUnit\Framework\TestCase;

class PostfixFilterTest extends TestCase
{
    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir.DIRECTORY_SEPARATOR.$object) && ! is_link($dir.'/'.$object)) {
                        $this->rrmdir($dir.DIRECTORY_SEPARATOR.$object);
                    } else {
                        unlink($dir.DIRECTORY_SEPARATOR.$object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function test_can_handler()
    {
        @mkdir(__DIR__.'/mail');
        @mkdir(__DIR__.'/mail/tell');
        @mkdir(__DIR__.'/mail/lock');

        copy(__DIR__.'/postfix-dsn1a.txt', __DIR__.'/mail/newmail');
        $contents = json_encode([
            'ticket@bookfirst.cc',
            'c@d.com',
            1111,
        ]);
        file_put_contents(__DIR__.'/mail/tell/newmail', $contents);

        $callback = false;

        $filter = new PostfixFilter;
        $filter->as('pf-forwardmail')
            ->folder('../tests/Feature/mail')
            ->handler(function ($self, $config, $meta, $mailfile) use (&$callback) {
                $callback = true;
                return true;
            });

        $this->assertTrue($callback);

        $this->rrmdir(__DIR__.'/mail');
    }
}
