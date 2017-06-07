# phpClub [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/someApprentice_phpClub/Lobby)
Demonstration: http://phpclub.rf.gd/

## Installation
1. `git clone https://github.com/someApprentice/phpClub.git`
1. `composer install`
1. config db connection in `src/config.ini`
1. run migrations `vendor/bin/doctrine-migrations migrations:migrate`
1. `sudo a2enmod rewrite`, change `AllowOverride` from `None` to `All` in your apache config file
1. make cron task `*/5 * * * * php your/server/directory/update.php`
