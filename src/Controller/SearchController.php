<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 8:55 PM
 */

namespace phpClub\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use phpClub\Service\View;
use phpClub\Service\Searcher;

/**
 * Class SearchController
 *
 * @package phpClub\Controller
 * @author foobar1643 <foobar76239@gmail.com>
 */
class SearchController
{
    /**
     * @var \phpClub\Service\View
     */
    protected $view;

    /**
     * @var \phpClub\Service\Searcher
     */
    protected $searcher;

    public function __construct(Searcher $searcher, View $view)
    {
        $this->view = $view;

        $this->searcher = $searcher;
    }

    public function searchAction(Request $request, Response $response, array $args = [])
    {
        $searchQuery = $args['searchQuery'];
        return $this->view->renderToResponse($response, 'searchResults', $this->searcher->search($searchQuery));
    }
}
