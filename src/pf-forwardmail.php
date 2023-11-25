#!/usr/bin/php
<?php

// forwardmail Postfix Filter
//
// this file should be owned by www-data
// and permission of 0700, see setup.sh

require_once 'PostfixFilter.php';

syslog(LOG_INFO, '[pf-forwardmail] running as '.get_current_user());

$m = new PostfixFilter;
$m->folder('../mail-forward')
    ->save()
    ->notify();
