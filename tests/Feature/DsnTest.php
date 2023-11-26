<?php

namespace Tests\Feature;

use App\Dsn;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    public function test_can_parse_outgoing_bounce_does_not_accept_mail_dsn()
    {
        $parser = (new Dsn)->parse(__DIR__.'/postfix-dsn1a.txt');
        $headers = $parser->getAllHeaders();

        $expected['Return-Path'] = 'nonexist@ssdmeter.com';
        $expected['DKIM-Signature'] = '1';
        $expected['Received'] = "by box.e115.com (Postfix, from userid 1000)\r\n        id 613583FCF0; Sun, 26 Nov 2023 14:36:07 +0800 (+08)";
        $expected['To'] = 'nonexistent@example.com';
        $expected['Mailhook-Id'] = '1234567890';
        $expected['Subject'] = 'Hey, I successfully configured Postfix with sender-dependent SASL authentication!';
        $expected['Content-type'] = 'text/html';
        $expected['Message-Id'] = '20231126063607.613583FCF0@box.e115.com';
        $expected['Date'] = 'Sun, 26 Nov 2023 14:36:07 +0800';
        $expected['From'] = 'nonexist@ssdmeter.com';
        $expected['Final-Recipient'] = 'rfc822; nonexistent@example.com';
        $expected['Original-Recipient'] = 'rfc822;nonexistent@example.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.0';
        $expected['Diagnostic-Code'] = 'X-Postfix; Domain example.com does not accept mail';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }

        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_incoming_bounce_sender_does_not_exist_dsn()
    {
        $parser = (new Dsn)->parse(__DIR__.'/postfix-dsn1b.txt');
        $headers = $parser->getAllHeaders();

        $expected['Final-Recipient'] = 'rfc822; nonexist@ssdmeter.com';
        $expected['Original-Recipient'] = 'rfc822;nonexist@ssdmeter.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.1';
        $expected['Remote-MTA'] = 'dns; 127.0.0.1';
        $expected['Diagnostic-Code'] = "smtp; 550 5.1.1 <nonexist@ssdmeter.com> User doesn't exist: nonexist@ssdmeter.com";

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }
        $this->assertTrue(! $parser->isOutgoing());
    }

    public function test_can_parse_outgoing_bounce_user_does_not_exists_dsn()
    {
        $parser = (new Dsn)->parse(__DIR__.'/postfix-dsn2a.txt');
        $headers = $parser->getAllHeaders();

        $expected['Return-Path'] = 'root@box.e115.com';
        $expected['DKIM-Signature'] = '1';
        $expected['Received'] = "by box.e115.com (Postfix, from userid 0)\r\n        id 673CB3F3D1; Sun, 26 Nov 2023 06:47:18 +0800 (+08)";
        $expected['To'] = 'root@box.e115.com';
        $expected['Subject'] = 'Cron <root@box> test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily )';
        $expected['Message-Id'] = '20231125224718.673CB3F3D1@box.e115.com';
        $expected['Date'] = 'Sun, 26 Nov 2023 06:47:18 +0800';
        $expected['From'] = 'root@box.e115.com';
        $expected['Final-Recipient'] = 'rfc822; root@box.e115.com';
        $expected['Original-Recipient'] = 'rfc822;root@box.e115.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.1';
        $expected['Diagnostic-Code'] = "smtp; 550 5.1.1 <root@box.e115.com> User doesn't exist: root@box.e115.com";

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }
        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_incoming_bounce_root_does_not_exist_dsn()
    {
        $parser = (new Dsn)->parse(__DIR__.'/postfix-dsn2b.txt');
        $headers = $parser->getAllHeaders();

        $expected['Final-Recipient'] = 'rfc822; root@box.e115.com';
        $expected['Original-Recipient'] = 'rfc822;root@box.e115.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.1';
        $expected['Remote-MTA'] = 'dns; 127.0.0.1';
        $expected['Diagnostic-Code'] = "smtp; 550 5.1.1 <root@box.e115.com> User doesn't exist: root@box.e115.com";

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }
        $this->assertTrue(! $parser->isOutgoing());
    }
}
