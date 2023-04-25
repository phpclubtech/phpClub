# phpClub [![Build Status](https://travis-ci.org/richBlueElephant/phpClub.svg?branch=master)](https://travis-ci.org/richBlueElephant/phpClub)

## Установка
1. Склонируйте репозиторий: `git clone https://github.com/phpclubtech/phpClub.git`
2. Перейдите в папку с проектом и установите зависимости: `cd phpClub && composer install`
3. Скопируйте конфигурационный файл и отредактируйте доступы к БД: `cp .env.example .env`
4. Запустите миграции: `vendor/bin/doctrine-migrations migrations:migrate --no-interaction`
5. Импортируйте живые треды с 2ch.hk API: `./bin/console import-threads --source=2ch-api`. Обычно это 1-2 живущих в данный момент треда, остальные к тому времени уже удалены. Для разработки этого достаточно.

## Запуск

Для быстрого запуска можно использовать встроенный в PHP веб-сервер:

```sh
php -S 127.0.0.1:9001 -t public dev-server.php
```

## Запуск тестов
1. Создайте тестовую базу данных, отредактируйте переменную TEST_DB_NAME в `.env`-файле
2. Запустите миграции для тестовой БД: `APP_ENV=test vendor/bin/doctrine-migrations migrations:migrate --no-interaction`
3. Запустите тесты: `./vendor/bin/phpunit`

## Sphinx
Для работы поиска нужен Sphinx.
1. Скопируйте конфигурацию Sphinx по умолчанию и отредактируйте доступы к БД: `sudo cp config/sphinx.conf.example /etc/sphinxsearch/sphinx.conf`
2. Запустите индексацию: `sudo indexer --all --rotate`
3. Включите автозапуск демона: `sudo sed -i 's/START=no/START=yes/g' /etc/default/sphinxsearch`
4. Перезапустите сервис: `sudo systemctl restart sphinxsearch.service`

## Примеры импорта тредов из различных источников
1) Импортировать треды 1-14, 22-24, 26-78 из локальной папки
- `./bin/console import-threads --dir=/absolute/path/to/2ch/threads`

2) Импортировать треды 25, 79-95 из архивача:
- `./bin/console import-threads --source=arhivach`

## Вызов команд Доктрины

Для вызова команд Доктрины можно запускать утилиты `doctrine` и `doctrine-migrations`. Чтобы увидеть список доступных команд, запустите их с опцией `list`:

```sh
vendor/bin/doctrine-migrations list
vendor/bin/doctrine list
```

Чтобы увидеть справку по команде, запустите её c опцией `--help` (например: `./vendor/bin/doctrine orm:validate-schema --help`).

## Создание миграций

Как правило, миграции (изменения в схеме БД) генерируются на основе изменений в файлах сущностей (в папке [src/Entity](src/Entity)). Чтобы изменить что-то в БД, отредактируйте аннотации в сущностях, затем запустите утилиту Доктрины для генерации миграций: 

```sh
vendor/bin/doctrine-migrations migrations:diff --formatted
```

Она сгенерирует новый файл миграции. Чтобы выполнить её, снова запустите утилиту Доктрины:

```sh
vendor/bin/doctrine-migrations migrations:migrate
```
