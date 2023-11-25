#!/bin/bash

sudo git pull

# setup the mail and bounce folders
sudo php -r "require_once 'PostfixFilter.php'; (new PostfixFilter())->setup('mail')->setup('bounce');"

# setup postfix filters
sudo chmod 0644 *php
sudo chown user-data:user-data *

sudo chmod 0700 pf-forwardmail.php
sudo chown www-data:www-data pf-forwardmail.php

sudo chmod 0700 pf-bulkbounce.php
sudo chown www-data:www-data pf-bulkbounce.php

# customize and restart postfix

## customize master.cf
sudo tee -a /etc/postfix/master.cf >/dev/null <<'EOF'

###############################
# custom mailhook settings
###############################

forwardmail unix - n n - - pipe
  flags=F user=www-data argv=/home/ubuntu/miab-data/www/pf-forwardmail.php ${recipient} ${sender} ${size}

bulkbounce unix - n n - - pipe
  flags=FRq user=www-data argv=/home/ubuntu/miab-data/www/pf-bulkbounce.php ${recipient} ${sender} ${size}
EOF

## customize main.cf
sudo tee -a /etc/postfix/main.cf >/dev/null <<'EOF'

###############################
# custom mailhook settings
###############################

delay_warning_time=1m
notify_classes = 2bounce, bounce, delay, resource, software
bounce_notice_recipient = bounce@e115.com
2bounce_notice_recipient = bounce@e115.com
delay_notice_recipient = bounce@e115.com
error_notice_recipient = bounce@e115.com
transport_maps = regexp:/etc/postfix/transport_maps
EOF

sudo cp transport_maps /etc/postfix/transport_maps
sudo postmap /etc/postfix/transport_maps
sudo service postfix restart

# make sure http is ok
echo "# Listing domain conf file(s). Must have at least 1"
ls *.conf

sudo chown user-data:user-data default -R
sudo ~/mailinabox/tools/web_update
sudo service nginx restart

echo
echo "# Done"
