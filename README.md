# phpClub
Demonstration: http://phpclub.rf.gd/

## Installation
1. `git clone https://github.com/someApprentice/phpClub.git`
1. `composer install`
1. `mysql -u YOUR_NAME -p YOUR_DATABASE_NAME < phpclub.sql`
1. config db connection in `src/config.ini`
1. `sudo a2enmod rewrite`, change `AllowOverride` from `None` to `All` in your apache config file
1. make cron task `*/5 * * * * php your/server/directory/update.php`
