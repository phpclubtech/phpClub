<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 8:55 PM
 */

namespace phpClub\Controller;

use Slim\Http\Response;
use Slim\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer as View;
use phpClub\Service\Searcher;
use phpClub\Service\Authorizer;

/**
 * Class SearchController
 *
 * @package phpClub\Controller
 * @author foobar1643 <foobar76239@gmail.com>
 */
class SearchController
{
    /**
     * @var \Slim\Views\PhpRenderer
     */
    protected $view;

    /**
     * @var \phpClub\Service\Searcher
     */
    protected $searcher;

    /**
     * @var \phpClub\Service\Authorizer
     */
    protected $authorizer;

    public function __construct(Searcher $searcher, Authorizer $authorizer, View $view)
    {
        $this->view = $view;

        $this->searcher = $searcher;

        $this->authorizer = $authorizer;
    }

    public function searchAction(Request $request, Response $response, array $args = []): ResponseInterface
    {

        return $this->view->render($response, '/searchResults.phtml', [
            'logged' => $this->authorizer->isLoggedIn(),
            'posts' => $this->searcher->search($args['searchQuery'])
        ]);
    }
}
