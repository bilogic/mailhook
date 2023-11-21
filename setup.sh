#!/bin/bash

# setup the mail and bounce folders
sudo php -r "require_once 'PostfixFilter.php'; (new PostfixFilter())->setup('mail')->setup('bounce');"

# setup postfix filters
sudo chmod 0644 *php
sudo chown user-data:user-data *php

sudo chmod 0700 pf-forwardmail.php
sudo chown user-data:user-data pf-forwardmail.php

sudo chmod 0700 pf-bulkbounce.php
sudo chown user-data:user-data pf-bulkbounce.php

# restart postfix
sudo cp transport_maps /etc/postfix/transport_maps
sudo postmap /etc/postfix/transport_maps
sudo service postfix restart

# make sure http is ok
echo "# Listing domain conf file(s). Must have at least 1"
ls *.conf

sudo chown user-data:user-data default -R
sudo ~/mailinabox/tools/web_update
sudo service nginx restart

sudo touch /var/log/php-error.log
sudo chown user-data:user-data /var/log/php-error.log

echo
echo "# Done"
