<?php
namespace phpClub\Service;

use phpClub\Entity\RefLink;
use phpClub\Repository\PostRepository;
use phpClub\Repository\RefLinkRepository;

class Helper
{
    public static function generateSalt()
    {
        $salt = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}'), 0, 44);

        return $salt;
    }

    public static function generateHash($password, $salt)
    {
        return md5($password . $salt);
    }

    public static function generateToken()
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 32);
    }

    public static function getToken()
    {
        return (isset($_COOKIE['token'])) ? $_COOKIE['token'] : self::generateToken();
    }
}
