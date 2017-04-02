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
}