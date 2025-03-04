#!/bin/bash

# run `sudo mailinabox` and install mailhook

function modifyMasterCf() {
	## customize master.cf
	sudo cp /etc/postfix/master.cf.mailinabox /etc/postfix/master.cf
	sudo tee -a /etc/postfix/master.cf >/dev/null <<-'EOF'
		###############################
		# custom mailhook settings
		###############################

		mailhookforward unix - n n - - pipe
		  flags=F user=user-data argv=/home/ubuntu/miab-data/www/scripts/pf-forwardmail.php ${recipient} ${sender} ${size}

		mailhookbounce unix - n n - - pipe
		  flags=FRq user=user-data argv=/home/ubuntu/miab-data/www/scripts/pf-bulkbounce.php ${recipient} ${sender} ${size}
	EOF
}

function modifyMainCf() {
	## customize main.cf
	sudo cp /etc/postfix/main.cf.mailinabox /etc/postfix/main.cf
	sudo sed -i -e 's/maximal_queue_lifetime/# maximal_queue_lifetime/g' /etc/postfix/main.cf
	sudo sed -i -e 's/delay_warning_time/# delay_warning_time/g' /etc/postfix/main.cf
	sudo sed -i -e 's/smtpd_sender_login_maps/# smtpd_sender_login_maps/g' /etc/postfix/main.cf
	sudo tee -a /etc/postfix/main.cf >/dev/null <<-'EOF'
		###############################
		# custom mailhook settings
		###############################

		maximal_queue_lifetime=1d
		delay_warning_time=1m
		notify_classes = 2bounce, bounce, delay, resource, software
		bounce_notice_recipient = bounce@e115.com
		2bounce_notice_recipient = bounce@e115.com
		delay_notice_recipient = bounce@e115.com
		error_notice_recipient = bounce@e115.com
		transport_maps = regexp:/etc/postfix/transport_maps
		smtpd_sender_login_maps=unionmap:{sqlite:/etc/postfix/sender-login-maps.cf, pcre:/etc/postfix/sender_logins}
		# smtpd_sender_login_maps=unionmap:{pcre:/etc/postfix/sender_logins, sqlite:/etc/postfix/sender-login-maps.cf}
		# smtpd_sender_login_maps=pcre:/etc/postfix/sender_logins
	EOF
}

function installMailhook() {
	sudo git pull

	# setup the mail and bounce folders
	sudo php -r "require_once 'src/PostfixFilter.php'; (new App\PostfixFilter())->setup(__DIR__.'/mail-forward')->setup(__DIR__.'/mail-bounced')->cache();"

	# setup postfix filters
	sudo chown user-data:user-data * -R
	sudo chmod 0644 * -R
	sudo chmod +x *.sh
	sudo chmod 0770 mail-bounced -R
	sudo chmod 0770 mail-forward -R

	# setup php files
	sudo adduser www-data user-data
	sudo chown www-data:www-data .*php -R
	sudo chown www-data:www-data default/*php -R
	sudo chown www-data:www-data src/*php -R
	sudo chown user-data:user-data vendor/*php -R
	sudo find . -type d -exec chmod 775 {} +
	sudo chmod g+w vendor/* -R
	# sudo chmod 0755 default
	# sudo chmod 0755 src
	# sudo chmod 0755 vendor

	sudo chown user-data:user-data scripts/pf-forwardmail.php
	sudo chown user-data:user-data scripts/pf-bulkbounce.php
	# sudo chmod 0755 scripts
	sudo chmod 0700 scripts/pf-forwardmail.php
	sudo chmod 0700 scripts/pf-bulkbounce.php

	COMPOSER_ALLOW_SUPERUSER=1 composer update

	# customize and restart postfix

	modifyMasterCf
	modifyMainCf

	sudo cp sender_logins /etc/postfix/sender_logins
	sudo postmap /etc/postfix/sender_logins

	sudo cp transport_maps /etc/postfix/transport_maps
	sudo postmap /etc/postfix/transport_maps

	sudo service postfix restart

	# make sure http is ok
	echo "# Listing domain conf file(s). Must have at least 1"
	ls *.conf

	sudo /root/mailinabox/tools/web_update
	sudo service nginx restart

	mailq

	echo
	echo "# Done"

}

sudo rm /etc/postfix/main.cf.mailinabox
sudo rm /etc/postfix/master.cf.mailinabox
sudo rm /etc/postfix/main.cf
sudo rm /etc/postfix/master.cf
sudo apt -y purge postfix

sudo PRIMARY_HOSTNAME=auto NONINTERACTIVE=1 mailinabox

sudo cp /etc/postfix/main.cf /etc/postfix/main.cf.mailinabox
sudo cp /etc/postfix/master.cf /etc/postfix/master.cf.mailinabox

installMailhook
