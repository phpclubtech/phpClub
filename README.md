# phpClub [![Build Status](https://travis-ci.org/richBlueElephant/phpClub.svg?branch=master)](https://travis-ci.org/richBlueElephant/phpClub) [![StyleCI](https://styleci.io/repos/85222499/shield?branch=master)](https://styleci.io/repos/85222499) [![Slack](https://cdn.rawgit.com/foobar1643/90576e886c2c2ef22726e66a643a9c92/raw/dcaa60aafbb87f70c5310ea9875f35fe79c8ad7e/slack.svg)](https://join.slack.com/t/phpclub-group/shared_invite/enQtMzA2MjcyMTAwNjc5LTNlZTI3ZjE5MTgyZWVhZjc3MmMyMzlhZGJmYTg0ODQ3YjAzYWRmMGNjZmJhYjdlMWFhZjg5MzNhNWE1YzdmNjc)
Demonstration: http://phpclub.tech/

## Installation
1. `git clone https://github.com/someApprentice/phpClub.git`
2. `composer install`
3. `cp .env.example .env`, config db connection
4. Run migrations `composer migrate`

## Thread import syntax
Run `./bin/console import-threads` with following arguments:

- `--source=2ch-api` - Import alive threads from 2ch.hk API:
- `--source=arhivach` - Import [list of threads](https://github.com/someApprentice/phpClub/blob/experimental/src/Command/ImportThreadsCommand.php#L134) from arhivach.org:
- `--dir=/var/www/threads/` - Import 2ch.hk threads from local folder:

## Full import example
1) Import threads 1-24, 26-78 from local folder (currently without threads 15-21):
- `./bin/console import-threads --dir=/absolute/path/to/2ch/threads`

2) Import threads 25, 79-95 from arhivach:
- `./bin/console import-threads --source=arhivach`

## Testing
1. Create test database, edit TEST_DB_NAME variable in `.env` file
2. Run migrations for test database `composer migrate-test`
3. Run tests using `composer test`
