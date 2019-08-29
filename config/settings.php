<?php

use Monolog\Logger;
use phpClub\FileStorage\LocalFileStorage;
use phpClub\Util\Environment;

return [
    'settings' => [
        'displayErrorDetails' => !Environment::isProd(),
        'fileStorage'         => LocalFileStorage::class,
    ],
    'connections' => [
        'mysql' => [
            'driver'   => 'pdo_mysql',
            'charset'  => 'utf8',
            'host'     => getenv('DB_HOST'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'dbname'   => getenv('DB_NAME'),
        ],
        'mysql_test' => [
            'driver'   => 'pdo_mysql',
            'charset'  => 'utf8',
            'host'     => getenv('DB_HOST'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'dbname'   => getenv('TEST_DB_NAME'),
        ],
        'sphinx' => [
            'dsn' => getenv('SPHINX_DSN'),
        ],
    ],
    'logger' => [
        'name'  => 'phpClub',
        'path'  => __DIR__ . '/../var/log/app.log',
        'level' => Environment::isProd() ? Logger::ERROR : Logger::DEBUG,
    ],
];
