<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    public function test_can_parse_outgoing_bounce_remote_does_not_accept_mail_dsn()
    {
        $this->markTestIncomplete();
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'asdfasdf',
                        'subject' => 'asdfasdf',
                    ],
                ],
                'severity' => 'permanent',
                'recipient' => 'asdfasdfasdf',
                'recipient-domain' => 'recipient-domain',
                'user-variables' => [
                    'track_id' => 'asdfasdfasdfas',
                    'ignore_failure' => true,
                    'message' => 'fasdfasdf',
                ],
            ],
        ];

    }
}
