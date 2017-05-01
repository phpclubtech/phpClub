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

    protected function authGetRequest(Request $request, Response $response, array $args = []): Response
    {
        return ($this->authorizer->isLoggedIn())
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'login', ['errors' => []]);
    }

    protected function authPostRequest(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            return $response->withRedirect('/');
        }

        // In the future, this will use POST body from $request instance
        $errors = $this->authorizer->login();

        return (empty($errors))
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'login', ['errors' => $errors]);
    }

    protected function registrationGetRequest(Request $request, Response $response, array $args = []): Response
    {
        return ($this->authorizer->isLoggedIn())
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'registration', ['errors' => []]);
    }

    protected function registrationPostRequest(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            return $response->withRedirect('/');
        }

        $errors = $this->authorizer->register();

        return (empty($errors))
            ? $response->withRedirect('/')
            : $this->view->renderToResponse($response, 'registration', ['errors' => $errors]);
    }

    protected function configureGetRequest(Request $request, Response $response, array $args = []): Response
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

    protected function configurePostRequest(Request $request, Response $response, array $args = []): Response
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

    public function authAction(Request $request, Response $response, array $args = []): Response
    {
        return $request->isPost()
            ? $this->authPostRequest($request, $response, $args)
            : $this->authGetRequest($request, $response, $args);
    }

    public function registrationAction(Request $request, Response $response, array $args = []): Response
    {
        return $request->isPost()
            ? $this->registrationPostRequest($request, $response, $args)
            : $this->registrationGetRequest($request, $response, $args);
    }

    public function configureAction(Request $request, Response $response, array $args = []): Response
    {
        return $request->isPost()
            ? $this->configurePostRequest($request, $response, $args)
            : $this->configureGetRequest($request, $response, $args);
    }

    public function logOutAction(Request $request, Response $response, array $args = []): Response
    {
        if ($this->authorizer->isLoggedIn()) {
            $this->authorizer->logout();
        }

        return $response->withRedirect('/');
    }
}
