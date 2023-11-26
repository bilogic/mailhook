#!/bin/bash

sudo php -r "require_once 'src/PostfixFilter.php'; (new App\PostfixFilter())->folder('mail-forward')->notify();"
