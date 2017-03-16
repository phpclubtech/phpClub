<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

use App\ActiveRecord;
use App\Thread;
use App\Post;
use App\File;

use App\Threader;
use App\Router;

$container = new Container();

$container['PDO'] = function () {
    $config = parse_ini_file(__DIR__ . '/config.ini');

    $pdo = new \PDO(
        "mysql:host={$config['host']}; dbname={$config['name']}; charset=utf8",
        $config['user'],
        $config['password']
    );

    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $query = $pdo->prepare("SET sql_mode = 'STRICT_ALL_TABLES'");
    $query->execute();
    
    return $pdo;
};

$container['ActiveRecord'] = function ($c) {
    return new ActiveRecord($c['PDO']);
};


$container['Threader'] = function ($c) {
    return new Threader($c['PDO']);
};

$container['Router'] = function ($c) {
    return new Router($c['Threader']);
};