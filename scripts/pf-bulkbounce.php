#!/usr/bin/php
<?php

// bulkbounce Postfix Filter
//
// see setup.sh, this file should be
// 1. owned by user-data
// 2. permission of 0700

syslog(LOG_INFO, '[pf-bulkbounce] running as '.get_current_user());
syslog(LOG_INFO, '[pf-bulkbounce] running in '.getcwd());

require_once __DIR__.'/../vendor/autoload.php';

use App\Dsn;
use App\PostfixFilter;

$filter = new PostfixFilter;
$filter->as('pf-bulkbounce')
    ->folder(__DIR__.'/../mail-bounced')
    // ->save()
    ->handler(function ($self, $fullConfig, $meta, $mailfile) {

        return false;

        $parser = (new Dsn)->parse($mailfile);

        if ($parser->isOutgoing()) {
            $headers = [];

            $dsn = $parser->getAllHeaders();
            $dst = $dsn['Return-Path'];

            $config = $fullConfig[$dst];
            $secret = $config['signing-secret'];
            $eventData = [
                'id' => 'DACSsAdVSeGpLid7TN03WA',
                'event' => 'failed',
                'log-level' => 'error',
                'timestamp' => strtotime('now'),
                'severity' => 'permanent',
                'recipient' => $dst,
                'recipient-domain' => 'recipient-domain',
                'message' => [
                    'headers' => $dsn,
                ],
                'user-variables' => [
                    'track_id' => 'asdfasdfasdfas',
                    'ignore_failure' => true,
                    'message' => 'fasdfasdf',
                ],
            ];

            $signature = [
                'timestamp' => strtotime('now'),
                'token' => $self->guidv4(),
            ];

            $plain = $signature['timestamp'].$signature['token'];
            $signature['signature'] = hash_hmac('sha256', $plain, $secret);

            $payload = [
                'signature' => $signature,
                'event-data' => $eventData,
            ];

            if ($parser->isHard()) {
                $eventData['severity'] = 'permanent';
                $url = $config['webhooks']['permanent_fail'];
            } else {
                $eventData['severity'] = 'temporary';
                $url = $config['webhooks']['temporary_fail'];
            }

            if (0) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
                //curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking

                $result = curl_exec($ch);

                // request headers
                $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($result !== false) {
                    $self->log("- cURL headers: [$headerSent]");
                    $self->log("- cURL output: [$result]");
                    $self->log("- cURL HTTP code: [$httpcode]");

                    if ($httpcode == 200) {
                        curl_close($ch);

                        // if webhook was successful
                        @unlink($mailfile);

                        return true;
                    }
                } else {
                    $self->log('- cURL err #: '.curl_errno($ch));
                    $self->log('- cURL error: '.curl_error($ch));
                }

                curl_close($ch);
            }

            if (1) {
                $curlHeaders[] = 'Content-Type: application/json';
                foreach ($headers as $param => $value) {
                    $curlHeaders[] = "$param: $value";
                }

                $json = json_encode($payload);
                $options = [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_HEADER => true, // we want the headers
                    CURLOPT_HTTPHEADER => $curlHeaders,
                    CURLOPT_POSTFIELDS => $json,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLINFO_HEADER_OUT => true,
                ];

                $ch = curl_init();
                curl_setopt_array($ch, $options);
                $responseBody = curl_exec($ch);
                if ($responseBody === false) {
                    $self->log('- cURL err #: '.curl_errno($ch));
                    $self->log('- cURL error: '.curl_error($ch));
                } else {
                    $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    $self->log("- cURL headers: [$headerSent]");
                    $self->log("- cURL output: [$responseBody]");
                    $self->log("- cURL HTTP code: [$httpcode]");

                    if ($httpcode >= 200 and $httpcode < 300) {
                        curl_close($ch);

                        @unlink($mailfile);

                        return true;
                    }
                }
                curl_close($ch);
            }

        }

        return false;
    });
