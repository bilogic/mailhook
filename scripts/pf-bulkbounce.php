#!/usr/bin/php
<?php

// bulkbounce Postfix Filter
//
// see setup.sh, this file should be
// 1. owned by user-data
// 2. permission of 0700

syslog(LOG_INFO, '[pf-bulkbounce.php] running as '.get_current_user());
syslog(LOG_INFO, '[pf-bulkbounce.php] running in '.getcwd());

require_once __DIR__.'/../vendor/autoload.php';

use App\Dsn;
use App\PostfixFilter;

$filter = new PostfixFilter;
$filter->as('pf-bulkbounce')
    ->folder(__DIR__.'/../mail-bounced')
    ->save()
    ->handler(function ($self, $config, $meta, $mailfile) {

        $parser = (new Dsn)->parse($mailfile);

        if ($parser->isOutgoing()) {
            // @unlink($mailfile);
            $headers = $parser->getAllHeaders();
        }

        return true; // delete away these emails
    });
