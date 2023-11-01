#!/bin/bash

php -r "require_once 'MessageHelper.php'; (new MessageHelper())->setup();"

sudo cp transport_maps /etc/postfix/transport_maps
postmap /etc/postfix/transport_maps

sudo chown www-data:www-data mail -R
sudo chmod +x postfix-filter.php
