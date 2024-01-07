#!/bin/bash

# delete all mails that have been read/downloaded by endpoints
# we know because once an email has been read/download by endpoints, a log record is created in the /read folder

sudo php -r "require_once 'src/PostfixFilter.php'; (new App\PostfixFilter())->folder('mail-forward')->remove();"
