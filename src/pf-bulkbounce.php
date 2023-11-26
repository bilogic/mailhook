#!/usr/bin/php
<?php

use App\PostfixFilter;

// forwardmail Postfix Filter
//
// this file should be owned by www-data
// and permission of 0700, see setup.sh

require_once 'PostfixFilter.php';

syslog(LOG_INFO, '[pf-bulkbounce.php] running as '.get_current_user());

$m = new PostfixFilter;
$m->folder('../mail-bounced')
    ->save()
    ->handler(function ($self, $config, $meta, $mailfile) {
    });
