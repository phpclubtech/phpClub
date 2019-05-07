<?php

/** @var \Slim\App $app */
use phpClub\Controller\ApiController;
use phpClub\Controller\BoardController;
use phpClub\Controller\SearchController;

$app->get('/', BoardController::class . ':indexAction')->setName('index');
$app->get('/pr/res/{thread:[0-9]+}.html', BoardController::class . ':threadAction')->setName('thread');
$app->get('/pr/chain/{post:[0-9]+}/', BoardController::class . ':chainAction')->setName('chain');
$app->get('/about/', BoardController::class . ':aboutAction')->setName('about');
$app->get('/search/', SearchController::class . ':searchAction')->setName('search');

$app->get('/api/board/get/message/{id:[0-9]+}/', ApiController::class . ':getPost');
