#!/bin/bash

sudo rm /etc/postfix/main.cf.mailinabox
sudo rm /etc/postfix/master.cf.mailinabox
sudo rm /etc/postfix/main.cf
sudo rm /etc/postfix/master.cf
sudo apt -y remove postfix

sudo PRIMARY_HOSTNAME=auto NONINTERACTIVE=1 mailinabox

sudo cp /etc/postfix/main.cf /etc/postfix/main.cf.mailinabox
sudo cp /etc/postfix/master.cf /etc/postfix/master.cf.mailinabox

./setup.sh
