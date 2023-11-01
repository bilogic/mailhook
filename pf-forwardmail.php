#!/usr/bin/php
<?php

// forwardmail Postfix Filter
//
// this file should be owned by www-data
// and permission of 0700, see setup.sh

require_once 'PostfixFilter.php';

$m = new PostfixFilter;
$m->save();
