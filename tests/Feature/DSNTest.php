<?php

namespace Tests\Feature;

use App\Dsn;
use PHPUnit\Framework\TestCase;

class DSNTest extends TestCase
{
    public function test_can_parse_postfix_dsn()
    {
        $parser = (new Dsn)->parse(__DIR__.'/postfix-dsn.txt');
        $headers = $parser->getAllHeaders();

        $expected['Return-Path'] = 'box@senderdomain.com';
        $expected['DKIM-Signature'] = '1';
        $expected['Received'] = "by box.smtpdomain.com (Postfix, from userid 1000)\r\n        id A492D3F147; Sat, 25 Nov 2023 19:51:36 +0800 (+08)";
        $expected['To'] = 'nonexistent@example.com';
        $expected['Mailhook-Id'] = '1234567890';
        $expected['Subject'] = 'Hey, I successfully configured Postfix with sender-dependent SASL authentication!';
        $expected['Content-type'] = 'text/html';
        $expected['Message-Id'] = '20231125115136.A492D3F147@box.smtpdomain.com';
        $expected['Date'] = 'Sat, 25 Nov 2023 19:51:36 +0800';
        $expected['From'] = 'box@senderdomain.com';
        $expected['Final-Recipient'] = 'rfc822; nonexistent@example.com';
        $expected['Original-Recipient'] = 'rfc822;nonexistent@example.com';
        $expected['Action'] = 'failed';
        $expected['Status'] = '5.1.0';
        $expected['Diagnostic-Code'] = 'X-Postfix; Domain example.com does not accept mail';

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $headers[$key]);
        }
    }
}
