#!/usr/bin/php
<?php

// this file should be owned by www-data
// and permission of 0700
//
// chmod 0700 postfix-filter.php
// chown www-data:www-data postfix-filter.php

require_once 'MessageHelper.php';

$m = new MessageHelper;
$m->notify();
