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

## Thread import
- Import alive threads from 2ch.hk API: `php cli/import_threads.php phpClub:import-threads 2ch-api`
- Import all threads from local folder: `php cli/import_threads.php phpClub:import-threads <path-to-threads> <board>`
- Import threads from arhivach by thread id (TODO)

## Testing
1. Create test database, edit config.testing.ini
2. Run migrations for test database `composer migrate-test`
3. Run tests using `composer test`
