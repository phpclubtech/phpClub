#!/bin/bash

ssh -A developer@209.250.236.29 "cd /var/www/phpClub \
    && git checkout master \
    && git pull origin master \
    && vendor/bin/doctrine-migrations migrations:migrate --no-interaction \
    && composer install"
