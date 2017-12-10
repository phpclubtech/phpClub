# phpClub [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/someApprentice_phpClub/Lobby)
Demonstration: http://phpclub.tech/

## Installation
1. `git clone https://github.com/someApprentice/phpClub.git`
2. `composer install`
3. `cp .env.example .env`, config db connection
4. Run migrations `composer migrate`

## Thread import
Run `./bin/console phpClub:import-threads` with following arguments. Examples:

- Import alive threads from 2ch.hk API:
`--source=2ch-api`
- (TODO) Import all archived PHP threads from arhivach.org:
`--source=arhivach`
- Import 2ch.hk threads from local folder:
`--dir=/var/www/threads/ --board=2ch`
- Import 2ch.hk threads saved in arhivach from local folder:
`--dir=/var/www/threads/ --board=arhivach`
- Import specific threads from arhivach.org:
`--source=arhivach --urls http://arhivach.org/thread/254710/,http://arhivach.org/thread/261841/`

## Testing
1. Create test database, edit TEST_DB_NAME variable in `.env` file
2. Run migrations for test database `composer migrate-test`
3. Run tests using `composer test`
