<?php
namespace App;

class Helper
{

    public static function createRefMap(array $posts)
    {
        $refmap = array();

        //Validator
        $regexp = '/<a href="[\S]+" class="post-reply-link" data-thread="(\d+)" data-num="(\d+)">/';
        $matches = array();

        foreach ($posts as $post) {
            //Validator
            if (preg_match_all($regexp, $post->getComment(), $matches)) {
                foreach ($matches[2] as $reflink) {
                    $refmap[] = array('number' => $post->getPost(), 'reflink' => $reflink);
                }
            }
        }

        return $refmap;
    }

    public static function createChain($number, $refmap)
    {
        static $chain = array();

        if (!in_array($number, $chain)) {
            $chain[] = $number;
        
            foreach($refmap as $ref) {
                if ($ref['number'] == $number) {
                    Helper::createChain($ref['reflink'], $refmap);
                } elseif($ref['reflink'] == $number) {
                    Helper::createChain($ref['number'], $refmap);
                }
            }
        }

        return $chain;
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