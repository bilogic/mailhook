#!/bin/bash

sudo git pull

# setup the mail and bounce folders
sudo php -r "require_once 'src/PostfixFilter.php'; (new PostfixFilter())->setup('../mail-forward')->setup('../mail-bounced');"

# setup postfix filters
sudo chown user-data:user-data * -R
sudo chmod 0644 * -R
sudo chmod +x *.sh
sudo chmod 0770 mail-bounced -R
sudo chmod 0770 mail-forward -R

# setup php files
sudo adduser www-data user-data
sudo chown www-data:www-data .*php -R
sudo chown www-data:www-data src/*php -R
sudo chown www-data:www-data default/*php -R
sudo chmod 0750 default
sudo chmod 0750 src
sudo chmod 0700 src/pf-forwardmail.php
sudo chmod 0700 src/pf-bulkbounce.php

# customize and restart postfix

## customize master.cf
sudo cp /etc/postfix/master.cf.mailinabox /etc/postfix/master.cf
sudo tee -a /etc/postfix/master.cf >/dev/null <<'EOF'

###############################
# custom mailhook settings
###############################

forwardmail unix - n n - - pipe
  flags=F user=www-data argv=/home/ubuntu/miab-data/www/src/pf-forwardmail.php ${recipient} ${sender} ${size}

bulkbounce unix - n n - - pipe
  flags=FRq user=www-data argv=/home/ubuntu/miab-data/www/src/pf-bulkbounce.php ${recipient} ${sender} ${size}
EOF

## customize main.cf
sudo cp /etc/postfix/main.cf.mailinabox /etc/postfix/main.cf
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

sudo ~/mailinabox/tools/web_update
sudo service nginx restart

echo
echo "# Done"
