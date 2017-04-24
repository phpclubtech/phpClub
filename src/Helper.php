<?php
namespace App;

use \Doctrine\ORM\EntityManager;

class Helper
{
    public static function getChain($number, EntityManager $em)
    {
        static $chain = [];

        if (!in_array($number, $chain)) {
            $chain[] = $number;

            $links = $em->getRepository('App\Entities\RefLink')->findBy(['post' => $number]);

            foreach ($links as $link) {
                Helper::getChain($link->getReference(), $em);
            }

            $links = $em->getRepository('App\Entities\RefLink')->findBy(['reference' => $number]);

            foreach ($links as $link) {
                Helper::getChain($link->getPost(), $em);
            }
        }

        usort($chain, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return $chain;
    }

    public static function generateSalt()
    {
        $salt = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}'), 0, 44);

        return $salt;
    }

    public static function generateHash($password, $salt) {
        $hash = md5($password . $salt);

        return $hash;
    }

    public static function generateToken()
    {
        $token = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 32);
        
        return $token;
    }

    public static function getToken() 
    {
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        } else {
            $token = $this->generateToken();
        }

        return $token;
    }

    public static function getArchiveIconUrl($link)
    {
        if (preg_match("!^https?:\/\/2ch\.hk\/pr\/arch\/\d{4}-\d{2}-\d{2}\/res\/\d+\.html$!", $link)) {
            return '/2ch.ico';
        }

        if (preg_match("!^https?:\/\/arhivach\.org\/thread\/\d+\/?$!", $link)) {
            return '/arhivach.ico';
        }
    }

    public static function getCatalogUrl()
    {
        return "https://2ch.hk/pr/catalog.json";
    }

    public static function getThreadUrl($number)
    {
        return "https://2ch.hk/pr/res/{$number}.json";
    }

    public static function getSrcUrl($filepath)
    {
        return "https://2ch.hk{$filepath}";
    }

    public static function getThumbUrl($thumbpath)
    {
        return "https://2ch.hk{$thumbpath}";
    }

    public static function getSrcDirectroyPath($number)
    {
        return __DIR__ . "/../pr/src/{$number}";
    }

    public static function getThumbDirectroyPath($number)
    {
        return __DIR__ . "/../pr/thumb/{$number}";
    }

    public static function getSrcPath($filepath)
    {
        return __DIR__ . "/..{$filepath}";
    }

    public static function getThumbPath($thumbpath)
    {
        return __DIR__ . "/..{$thumbpath}";
    }

    public static function getJsonPath($threadnumber)
    {
        return __DIR__ . "/../json/{$threadnumber}.json";
    }
}