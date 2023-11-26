#!/usr/bin/php
<?php

use App\PostfixFilter;

// forwardmail Postfix Filter
//
// this file should be owned by www-data
// and permission of 0700, see setup.sh

require_once '../src/PostfixFilter.php';

syslog(LOG_INFO, '[pf-forwardmail] running as '.get_current_user());

$filter = (new PostfixFilter)
    ->as('pf-forwardmail');

$filter->folder(__DIR__.'/../mail-forward')
    ->save();

$filter->handler(function ($self, $config, $meta, $mailfile) {

    $dst = strtolower($meta[0]);
    $url = $config[$dst].urlencode(basename($mailfile));

    // $url = 'http://www.google.com/asdkfhasdf';
    $self->log("- Notifying [$url]");
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

            return true;
        }
    } else {
        $self->log('- cURL err #: '.curl_errno($ch));
        $self->log('- cURL error: '.curl_error($ch));
    }

    curl_close($ch);

    return false;
});
