#!/bin/bash

while (true); do
  clear
  ls -l mail-bounced/
  mailq
  sleep 1
done
