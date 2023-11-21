#!/bin/bash

# syslog includes mail.log

tail -f -n 300 \
    /var/log/syslog \
    /var/log/nginx/access.log \
    /var/log/nginx/error.log \
    /var/log/php-error.log

# /var/log/mail.log \
