#!/bin/bash

sudo rm /etc/postfix/main.cf
sudo rm /etc/postfix/master.cf

sudo PRIMARY_HOSTNAME=auto NONINTERACTIVE=1 mailinabox

./setup.sh
