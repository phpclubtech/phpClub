<?php


/**
 * A router script for PHP builtin web server. Calls index.php for
 * handling all requests except for static files.
 *
 * Examples of other routing scripts:
 *
 * - https://github.com/symfony/web-server-bundle/blob/master/Resources/router.php
 * - https://github.com/drupal/drupal/blob/8.5.x/.ht.router.php
 *
 * Reference: http://php.net/manual/en/features.commandline.webserver.php
 *
 * Command to start server: php -S 127.0.0.1:9001 -t public ../dev-server.php
 *
 * For sake of simplicity we do not support PATH_INFO here
 * (/script.php/path/info will be routed to index.php)
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$staticPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
$ext = pathinfo($path, PATHINFO_EXTENSION);

// Paths '/' and '' are not for static files
if (mb_strlen($path) > 1 && file_exists($staticPath) && $ext != 'php') {
    return false;
}

$scriptName = ($ext == 'php') ? $path : '/index.php';

// Print request to console as dynamic requests are not printed by default
error_log(sprintf('%s %s', $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']), 4);

$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] .
    str_replace('/', DIRECTORY_SEPARATOR, $scriptName);

$_SERVER['SCRIPT_NAME'] = $scriptName;
$_SERVER['PHP_SELF'] = $scriptName;

unset($path, $staticPath, $scriptName, $ext);
require __DIR__ . '/public' . $_SERVER['SCRIPT_NAME'];
