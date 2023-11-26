#!/usr/bin/php
<?php

use App\PostfixFilter;

// forwardmail Postfix Filter
//
// this file should be owned by www-data
// and permission of 0700, see setup.sh

require_once 'src/PostfixFilter.php';

syslog(LOG_INFO, '[pf-bulkbounce.php] running as '.get_current_user());

$filter = new PostfixFilter;
$filter->as('pf-bulkbounce')
    ->folder(__DIR__.'/../mail-bounced')
    ->save()
    ->handler(function ($self, $config, $meta, $mailfile) {
    });
