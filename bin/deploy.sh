#!/bin/bash

ssh -A phpclub "cd /var/www/phpClub \
    && git checkout master \
    && git pull origin master \
    && vendor/bin/doctrine-migrations migrations:migrate --no-interaction \
    && composer install"
