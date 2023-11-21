#!/bin/bash

tail -f -n 300 /var/log/mail.log /var/log/nginx/access.log /var/log/nginx/error.log /var/log/syslog /var/log/php-error.log
