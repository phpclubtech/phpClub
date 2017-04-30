<?php
namespace App;

use App\Validator;

class Controller
{
    public function getNumberQuery()
    {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);

        $validatedNumber = Validator::validateThreadLink($path);

        if ($validatedNumber) {
            return $validatedNumber;
        }

        return false;
    }

    public function getChainQuery()
    {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);

        $validatedNumber = Validator::validateChainLink($path);

        if ($validatedNumber) {
            return $validatedNumber;
        }

        return false;
    }

    public function getSearchQuery()
    {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);

        $validatedSearchQuery = Validator::validateSearchLink($path);

        if ($validatedSearchQuery) {
            return $validatedSearchQuery;
        }

        return false;
    }

    public function render($path, array $varibles = array())
    {
        extract($varibles);

        $path = __DIR__ . '/../' . $path;

        if (file_exists($path)) {
            include $path;
        } else {
            throw new \Exception("Invalid template path");
        }
    }

    public static function redirect($location = "/")
    {
        if (!preg_match('!^/([^/]|\\Z)!', $location, $matches)) {
            $location = "/";
        }

        header("Location: " . $location);
    }

}