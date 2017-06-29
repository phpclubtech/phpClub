# phpClub [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/someApprentice_phpClub/Lobby)
Demonstration: http://phpclub.rf.gd/

## Installation
1. `git clone https://github.com/someApprentice/phpClub.git`
2. `composer install`
3. config db connection in `src/config.ini`  
	If you using MySQL, disable `ONLY_FULL_GROUP_BY` option:  
	`SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));`
4. run migrations `vendor/bin/doctrine-migrations migrations:migrate`
5. `sudo a2enmod rewrite`, change `AllowOverride` from `None` to `All` in your apache config file
7. make cron task `*/5 * * * * php your/server/directory/update.php`
