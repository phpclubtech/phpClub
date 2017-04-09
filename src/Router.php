<?php
namespace App;

use App\Threader;
use App\Validator;

class Router
{
    protected $threader;

    public function __construct(Threader $threader)
    {
        $this->threader = $threader;
    }

    public function run()
    {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);

        if ($path == '/') {
            $this->threader->runThreads();
        } elseif (Validator::validateThreadLink($path)) {
            $this->threader->runThread();
        } elseif (Validator::validateChainLink($path)) {
            $this->threader->runChain();
        } else {
            header("HTTP/1.0 404 Not Found");
            
            $this->threader->render('templates/404.html');
        }
    }
}