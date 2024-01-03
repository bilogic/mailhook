<?php

namespace Tests\Feature;

use App\Dsn;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    public function test_can_parse_postmaster_hard_bounce_remote_does_not_accept_mail()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn1a.txt');
        $headers = $parser->getAllHeaders();

        $expected['Final-Recipient'] = 'rfc822; nonexistent@example.com';
        $expected['Original-Recipient'] = 'rfc822;nonexistent@example.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.0';
        $expected['Diagnostic-Code'] = 'X-Postfix; Domain example.com does not accept mail';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }

        $expected = [];
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

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers['original'][$key]);
        }

        $this->assertTrue($parser->isHard());
        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_postmaster_hard_bounce_sender_does_not_exist()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn1b.txt');
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

        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_postmaster_hard_bounce_user_does_not_exists()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn2a.txt');
        $headers = $parser->getAllHeaders();

        $expected['Final-Recipient'] = 'rfc822; root@box.e115.com';
        $expected['Original-Recipient'] = 'rfc822;root@box.e115.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.1';
        $expected['Diagnostic-Code'] = "smtp; 550 5.1.1 <root@box.e115.com> User doesn't exist: root@box.e115.com";

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }

        $expected = [];
        $expected['Return-Path'] = 'root@box.e115.com';
        $expected['DKIM-Signature'] = '1';
        $expected['Received'] = "by box.e115.com (Postfix, from userid 0)\r\n        id 673CB3F3D1; Sun, 26 Nov 2023 06:47:18 +0800 (+08)";
        $expected['To'] = 'root@box.e115.com';
        $expected['Subject'] = 'Cron <root@box> test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily )';
        $expected['Message-Id'] = '20231125224718.673CB3F3D1@box.e115.com';
        $expected['Date'] = 'Sun, 26 Nov 2023 06:47:18 +0800';
        $expected['From'] = 'root@box.e115.com';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers['original'][$key]);
        }
        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_postmaster_hard_bounce_root_does_not_exist()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn2b.txt');
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

        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_postmaster_soft_bounce_connection_timeout()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn3.txt');
        $headers = $parser->getAllHeaders();

        $expected['Original-Recipient'] = 'rfc822;SoftBounce@bounce-testing.postmarkapp.com';
        $expected['Action'] = 'delayed';
        $expected['Status'] = '4.4.1';
        $expected['Diagnostic-Code'] = 'X-Postfix; connect to bounce-testing.postmarkapp.com[50.31.156.110]:25: Connection timed out';
        $expected['Will-Retry-Until'] = 'Tue, 28 Nov 2023 20:38:46 +0800';
        $expected['Final-Recipient'] = 'rfc822; SoftBounce@bounce-testing.postmarkapp.com';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }

        $expected = [];
        $expected['Return-Path'] = 'box@ssdmeter.com';
        $expected['DKIM-Signature'] = '1';
        $expected['Received'] = "by box.e115.com (Postfix, from userid 1000)\r\n        id 5B9103FCF0; Sun, 26 Nov 2023 20:38:46 +0800 (+08)";
        $expected['To'] = 'SoftBounce@bounce-testing.postmarkapp.com';
        $expected['Mailhook-Id'] = '1234567890';
        $expected['Subject'] = 'Hey, I successfully configured Postfix with sender-dependent SASL authentication!';
        $expected['Content-type'] = 'text/html';
        $expected['Message-Id'] = '20231126123846.5B9103FCF0@box.e115.com';
        $expected['Date'] = 'Sun, 26 Nov 2023 20:38:46 +0800';
        $expected['From'] = 'box@ssdmeter.com';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers['original'][$key]);
        }

        $this->assertFalse($parser->isHard());
        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_postmaster_hard_bounce_does_not_accept_mail_with_mailgun_variables()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn4.txt');
        $headers = $parser->getAllHeaders();

        $expected['Final-Recipient'] = 'rfc822; nonexistent@example.com';
        $expected['Original-Recipient'] = 'rfc822;nonexistent@example.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.0';
        $expected['Diagnostic-Code'] = 'X-Postfix; Domain example.com does not accept mail';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }

        $expected = [];
        $expected['Return-Path'] = 'ticket@bookfirst.cc';
        $expected['DKIM-Signature'] = '1';
        $expected['X-mailhook-id'] = '1701020791-f4fca396-b0f0-4739-929d-389b34098751';
        $expected['Date'] = 'Mon, 27 Nov 2023 01:46:31 +0800';
        $expected['From'] = 'ticket@bookfirst.cc';
        $expected['Reply-To'] = 'ticket@bookfirst.cc';
        $expected['Message-ID'] = '9b5012106049e0f1eb490a1923cd804f@bookfirst.cc';
        $expected['X-Mailer'] = 'PHPMailer 5.2.2-rc1';
        $expected['MIME-Version'] = '1.0';
        $expected['Content-Type'] = 'multipart/alternative';
        $expected['To'] = 'nonexistent@example.com';
        $expected['Subject'] = '[#45J-DGZ-6PS9] BookFirst Support: aasdfasdf';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers['original'][$key]);
        }

        $variables = json_decode($headers['original']['X-Mailgun-Variables'], true);
        $this->assertEquals('45J-DGZ-6PS9', $variables['track_id']);
        $this->assertTrue($parser->isHard());
        $this->assertTrue($parser->isOutgoing());
    }

    public function test_can_parse_sender_hard_bounce_remote_does_not_accept_mail_with_mailgun_variables()
    {
        $parser = (new Dsn)->parse(__DIR__.'/../Fixtures/postfix-dsn5.txt');
        $headers = $parser->getAllHeaders();

        $expected['Final-Recipient'] = 'rfc822; adfasdfasdfasdfasdf@example.com';
        $expected['Original-Recipient'] = 'rfc822;adfasdfasdfasdfasdf@example.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.0';
        $expected['Diagnostic-Code'] = 'X-Postfix; Domain example.com does not accept mail';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }

        $variables = json_decode($headers['original']['X-Mailgun-Variables'], true);

        $this->assertEquals('UP6-74G-V82Z', $variables['track_id']);

    }
}
