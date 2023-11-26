<?php

namespace Tests\Feature;

use App\Dsn;
use PHPUnit\Framework\TestCase;

class DSNTest extends TestCase
{
    protected $activity = null;

    public function test_can_parse_postfix_dsn()
    {
        $parser = (new Dsn)->parse(__DIR__.'/postfix-dsn.txt');
        $pair = $parser->getAllHeaders();

        $this->assertEquals('1234567890', $pair['Mailhook-Id']);
        $this->assertEquals('box@senderdomain.com', $pair['Return-Path']);

        $this->assertEquals('rfc822;nonexistent@example.com', $pair['Original-Recipient']);
        $this->assertEquals('5.1.0', $pair['Status']);
        $this->assertEquals('X-Postfix; Domain example.com does not accept mail', $pair['Diagnostic-Code']);
    }
}
