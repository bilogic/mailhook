#!/bin/bash

sudo rm /etc/postfix/main.cf
sudo rm /etc/postfix/master.cf

sudo mailinabox

./setup.sh
