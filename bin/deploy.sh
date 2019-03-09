#!/bin/bash

if [ "$BRANCH" == "master" ]; then
    ssh -i ./deploy_key -A developer@209.250.236.29 -o StrictHostKeyChecking=no "cd /var/www/phpClub \
      && date >> /var/www/deploy-log.txt \
      && git fetch origin master \
      && git reset --hard origin/master \
      && vendor/bin/doctrine-migrations migrations:migrate --no-interaction \
      && composer install"
fi

