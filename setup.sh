#!/bin/bash

sudo php -r "require_once 'MessageHelper.php'; (new MessageHelper())->setup();"

sudo cp transport_maps /etc/postfix/transport_maps
sudo postmap /etc/postfix/transport_maps

sudo chown www-data:www-data default -R
sudo chown www-data:www-data mail -R
sudo chmod +x postfix-filter.php

sudo ~/mailinabox/tools/web_update
sudo service nginx restart
