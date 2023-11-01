#!/bin/bash

sudo php -r "require_once 'MessageHelper.php'; (new MessageHelper())->setup();"

sudo cp transport_maps /etc/postfix/transport_maps
sudo postmap /etc/postfix/transport_maps

sudo chown www-data:www-data default -R
sudo chown www-data:www-data mail -R

sudo chmod 0700 pff-forwardmail.php
sudo chown www-data:www-data pff-forwardmail.php

sudo chmod 0700 pff-bulkbounce.php
sudo chown www-data:www-data pff-bulkbounce.php

sudo ~/mailinabox/tools/web_update
sudo service nginx restart

echo "# Listing domain conf file(s). Must have at least 1"
ls *.conf
echo
echo "# Done"
