#!/bin/bash

sudo php -r "require_once 'PostfixFilter.php'; (new PostfixFilter())->setup('mail')->setup('bounce');"

# setup postfix filters
sudo chmod 0644 *php
sudo chown ubuntu:ubuntu *php

sudo chmod 0700 pf-forwardmail.php
sudo chown www-data:www-data pf-forwardmail.php

sudo chmod 0700 pf-bulkbounce.php
sudo chown www-data:www-data pf-bulkbounce.php

# restart postfix
sudo cp transport_maps /etc/postfix/transport_maps
sudo postmap /etc/postfix/transport_maps
sudo service postfix restart

# make sure http is ok
echo "# Listing domain conf file(s). Must have at least 1"
ls *.conf

sudo chown www-data:www-data default -R
sudo ~/mailinabox/tools/web_update
sudo service nginx restart

echo
echo "# Done"
