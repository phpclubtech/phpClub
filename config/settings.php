<?php

use phpClub\FileStorage\LocalFileStorage;

return [
    'settings' => [
        'displayErrorDetails' => getenv('APP_ENV') !== 'prod',
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
];