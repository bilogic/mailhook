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
        // $this->markTestIncomplete();
        @mkdir(__DIR__.'/mail');
        @mkdir(__DIR__.'/mail/tell');
        @mkdir(__DIR__.'/mail/lock');

        // when a new mail arrives, we inform hesk and ask it to pull from url
        $json['ticket@vxcharts.com']['pipe'] = 'https://vxcharts.com/support/admin/imn.php?url=https://box.e115.com/pipe/';
        $json['ticket@vxcharts.com']['signing-secret'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        $json['ticket@bookfirst.cc']['pipe'] = 'https://bookfirst.cc/support/admin/imn.php?url=https://box.e115.com/pipe/"';
        $json['ticket@bookfirst.cc']['signing-secret'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        $json['ticket@bookfirst.cc']['key'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $json['ticket@bookfirst.cc']['comments'] = '`delivery_by` can impersonate anyone from `domain`';
        $json['ticket@bookfirst.cc']['delivery_by'] = 'sender@bookfirst.cc';
        $json['ticket@bookfirst.cc']['domain'] = 'bookfirst.cc';
        $json['ticket@bookfirst.cc']['webhooks']['incoming'] = 'https://bookfirst.cc/mailgun/webhook';
        $json['ticket@bookfirst.cc']['webhooks']['complained'] = 'https://bookfirst.cc/mailgun/webhook';
        $json['ticket@bookfirst.cc']['webhooks']['opened'] = 'https://bookfirst.cc/mailgun/webhook';
        $json['ticket@bookfirst.cc']['webhooks']['permanent_fail'] = 'https://bookfirst.cc/mailgun/webhook';
        $json['ticket@bookfirst.cc']['webhooks']['temporary_fail'] = 'https://bookfirst.cc/mailgun/webhook';

        file_put_contents(__DIR__.'/config.json', json_encode($json));

        copy(__DIR__.'/../Fixtures/postfix-dsn1a.txt', __DIR__.'/mail/newmail');
        $contents = json_encode([
            'ticket@bookfirst.cc',
            'c@d.com',
            1111,
        ]);
        file_put_contents(__DIR__.'/mail/tell/newmail', $contents);

        $callback = false;

        $filter = new PostfixFilter;
        $filter->as('pf-forwardmail')
            ->folder(__DIR__.'/mail')
            ->handler(function ($self, $config, $meta, $mailfile) use (&$callback) {
                $callback = true;

                return true;
            });

        $this->assertTrue($callback);

        $this->rrmdir(__DIR__.'/mail');
    }
}
