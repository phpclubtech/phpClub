<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 9:24 PM
 */

namespace phpClub\Controller;

use Slim\Http\Response;
use Slim\Http\Request;
use phpClub\Service\Authorizer;
use phpClub\Service\View;

/**
 * Class UsersController
 *
 * @package phpClub\Controller
 * @author foobar1643 <foobar76239@gmail.com>
 *
 * @todo Request handling based on GET\POST request type should be done better than separate actions for each type.
 */
class UsersController
{
    /**
     * @var \phpClub\Service\View
     */
    protected $view;

    /**
     * @var \phpClub\Service\Authorizer
     */
    protected $authorizer;

    public function __construct(Authorizer $authorizer, View $view)
    {
        $this->view = $view;

        $this->authorizer = $authorizer;
    }

    public function displayAuthAction(Request $request, Response $response, array $args = []): Response
    {
        return ($this->authorizer->isLoggedIn())
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'login', ['errors' => []]);
    }

    public function displayRegistrationAction(Request $request, Response $response, array $args = []): Response
    {
        return ($this->authorizer->isLoggedIn())
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'registration', ['errors' => []]);
    }

    public function displayConfigureAction(Request $request, Response $response, array $args = []): Response
    {
        $user = $this->authorizer->isLoggedIn();
        if (!$user) {
            return $response->withRedirect('/');
        }

        return $this->view->renderToResponse(
            $response,
            'configure',
            [
                'errors' => [],
                'logged' => $user
            ]
        );
    }

    public function preformConfigureAction(Request $request, Response $response, array $args = []): Response
    {
        $user = $this->authorizer->isLoggedIn();

        if (!$user) {
            return $response->withRedirect('/');
        }

        $errors = $this->authorizer->configure($user);

        return (empty($errors))
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'configure', ['errors' => $errors]);
    }

    public function preformAuthAction(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            return $response->withRedirect('/');
        }

        $errors = $this->authorizer->login();

        return (empty($errors))
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'login', ['errors' => $errors]);
    }

    public function preformRegistrationAction(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            return $response->withRedirect('/');
        }

        $errors = $this->authorizer->register();

        return (empty($errors))
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'registration', ['errors' => $errors]);
    }

    public function preformLogOutAction(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            $this->authorizer->logout();
        }

        return $response->withRedirect('/');
    }
}
