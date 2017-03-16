<?php
namespace App;

class Validator
{
    const PHPREGEXP = "/^(Клуб)[\s\w\W]*(PHP)[\s\w\W]*$/ui";

    public static function validateThreadSubject($subject)
    {
        if (preg_match(self::PHPREGEXP, $subject)) {
            return true;
        }

        return false;
    }

    public static function validateThreadLink($path)
    {
        $matches = array();

        if (preg_match('!\/pr\/res\/(\d+)\.html(#\d+)?!', $path, $matches)) {
            $number = $matches[1];

            return $number;
        }

        return false;
    }

    public static function validateChainLink($path)
    {
        $matches = array();

        if (preg_match('!\/pr\/chain\/(\d+)(#\d+)?!', $path, $matches)) {
            $number = $matches[1];

            return $number;
        }

        return false;
    }
}