<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\MailMimeParser;

class DSNTest extends TestCase
{
    protected $activity = null;

    public function test_activity_can_be_deleted()
    {
        $parser = new MailMimeParser();
        $handle = fopen(__DIR__.'/dsn.txt', 'r');
        $message = $parser->parse($handle, false);

        foreach ($message->getAllParts() as $part) {
            switch ($part->getContentType()) {
                case 'message/delivery-status':
                    $dsn = $parser->parse($part->getContentStream(), false);
                    $report = $parser->parse($dsn->getContentStream(), false);
                    break;
                case 'text/rfc822-headers':
                    $original = $parser->parse($part->getContentStream(), false);
                    break;
            }
        }

        $this->assertEquals('1234567890', $original->getHeader('Mailhook-Id')->getValue());
        $this->assertEquals('box@senderdomain.com', $original->getHeader('Return-Path')->getValue());

        $this->assertEquals('rfc822;nonexistent@example.com', $report->getHeader('Original-Recipient')->getValue());
        $this->assertEquals('5.1.0', $report->getHeader('Status')->getValue());
        $this->assertEquals('X-Postfix; Domain example.com does not accept mail', $report->getHeader('Diagnostic-Code')->getValue());

        fclose($handle);

    }
}
