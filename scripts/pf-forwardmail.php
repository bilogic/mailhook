#!/usr/bin/php
<?php

// forwardmail Postfix Filter
//
// see setup.sh, this file should be
// 1. owned by user-data
// 2. permission of 0700

syslog(LOG_INFO, '[pf-forwardmail] running as '.get_current_user());
syslog(LOG_INFO, '[pf-forwardmail] running in '.getcwd());

require_once __DIR__.'/../vendor/autoload.php';

use App\PostfixFilter;

$filter = (new PostfixFilter)
    ->as('pf-forwardmail')
    ->folder(__DIR__.'/../mail-forward')
    ->save()
    ->handler(function ($self, $config, $meta, $mailfile) {

        $url = $config['pipe'].urlencode(basename($mailfile));

        $self->log("- Notifying [$url]");
        $ch = curl_init($url);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);    // capture request headers
        curl_setopt($ch, CURLOPT_HEADER, true);         // capture response headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // capture body
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $config);

        $result = curl_exec($ch);
        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result !== false) {
            $self->log("- cURL headers: [$headerSent]");
            $self->log("- cURL output: [$result]");
            $self->log("- cURL HTTP code: [$httpcode]");

            if ($httpcode >= 200 and $httpcode < 300) {
                curl_close($ch);

                return true;
            }
        } else {
            $self->log('- cURL err #: '.curl_errno($ch));
            $self->log('- cURL error: '.curl_error($ch));
        }
        curl_close($ch);

        return false;
    });
