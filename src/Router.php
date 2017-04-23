<?php
namespace App;

use App\Threader;
use App\Authorizer;
use App\ArchiveLinkController;
use App\Validator;

class Router
{
    protected $threader;
    protected $authorizer;
    protected $archiveLinkController;

    public function __construct(Threader $threader, Authorizer $authorizer, ArchiveLinkController $archiveLinkController)
    {
        $this->threader = $threader;
        $this->authorizer = $authorizer;
        $this->archiveLinkController = $archiveLinkController;
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
        } elseif (Validator::validateRegistrationLink($path)) {
            $this->authorizer->register();
        } elseif (Validator::validateLoginLink($path)) {
            $this->authorizer->login();
        } elseif (Validator::validateConfigLink($path)) {
            $this->authorizer->configurate();
        } elseif(Validator::validateLogoutLink($path)) {
            $this->authorizer->logout();
        } elseif(Validator::validateAddArchiveLink($path)) {
            $this->archiveLinkController->addLink();
        } elseif(Validator::validateRemoveArchiveLink($path)) {
            $this->archiveLinkController->removeLink();
        } else {
            header("HTTP/1.0 404 Not Found");
            
            $this->threader->render('public/404.php');
        }
    }
}