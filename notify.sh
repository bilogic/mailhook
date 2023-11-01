#!/bin/bash

sudo php -r "require_once 'PostfixFilter.php'; (new PostfixFilter())->notify();"
