#!/bin/bash

sudo apt install \
    libio-socket-ssl-perl \
    libdigest-hmac-perl \
    libterm-readkey-perl \
    libmime-lite-perl \
    libfile-libmagic-perl \
    libio-socket-inet6-perl

# wget https://raw.githubusercontent.com/mludvig/smtp-cli/smtp-cli
wget https://github.com/mludvig/smtp-cli/raw/master/smtp-cli
chmod +x smtp-cli

sudo apt install \
    libio-socket-ssl-perl \
    libdigest-hmac-perl \
    libterm-readkey-perl \
    libmime-lite-perl \
    libfile-libmagic-perl \
    libio-socket-inet6-perl

./smtp-cli \
    --from web@ecoflorasg.com \
    --user web@ecoflorasg.com \
    --password vjdh2EbYoy2Vg2 \
    --server 51.79.242.232:587 \
    --to xroute1@gmail.com \
    --subject "Test" \
    --body-plain "body" \
    --ipv4 \
    --missing-modules-ok

./smtp-cli \
    --from ticket@vxcharts.com \
    --user ticket@vxcharts.com \
    --password 2BpMsyYvQNn8EY \
    --server 51.79.242.232:587 \
    --to xroute1@gmail.com \
    --subject "Test" \
    --body-plain "body" \
    --ipv4 \
    --missing-modules-ok
