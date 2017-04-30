<?php
namespace App;

use \Doctrine\ORM\EntityManager;

use App\Controller;
use App\Validator;
use App\Helper;

use App\Entities\User;

class Authorizer extends Controller
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function isLoggedIn()
    {
        if (isset($_COOKIE['id'])) {
            $student = $this->em->getRepository('App\Entities\User')->find($_COOKIE['id']);

            if (isset($_COOKIE['token'])) {
                if ($student->getHash() == $_COOKIE['hash']) {
                    return $student;
                }
            }
        }

        return false;
    }

    public function register()
    {
        if ($this->isLoggedIn()) {
            $this->redirect();

            die();
        }

        $post = array();

        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post['email'] = (isset($_POST['email']) and is_scalar($_POST['email'])) ? $_POST['email'] : '';
            $post['name'] = (isset($_POST['name']) and is_scalar($_POST['name'])) ? $_POST['name'] : '';
            $post['password'] = (isset($_POST['password']) and is_scalar($_POST['password'])) ? $_POST['password'] : '';
            $post['retryPassword'] = (isset($_POST['retryPassword']) and is_scalar($_POST['retryPassword'])) ? $_POST['retryPassword'] : '';

            $post['email'] = trim($post['email']);
            $post['name'] = trim($post['name']);
            $post['password'] = trim($post['password']);
            $post['retryPassword'] = trim($post['retryPassword']);

            $errors = Validator::validateRegistrationPost($post);

            if ($this->em->getRepository('App\Entities\User')->findOneBy(['email' => $post['email']])) {
                $errors['email'] = "Почта уже занята";
            }

            if (empty($errors)) {
                $salt = Helper::generateSalt();
                $hash = Helper::generateHash($post['password'], $salt);

                $user = new User();
                $user->setEmail($post['email']);
                $user->setName($post['name']);
                $user->setHash($hash);
                $user->setSalt($salt);

                $this->em->persist($user);
                $this->em->flush();

                $this->login();
            }
        }

        $this->render('public/registration.php', compact('post', 'errors'));
    }

    public function login()
    {
        if ($this->isLoggedIn()) {
            $this->redirect();

            die();
        }

        $post = array();

        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post['email'] = (isset($_POST['email']) and is_scalar($_POST['email'])) ? $_POST['email'] : '';
            $post['password'] = (isset($_POST['password']) and is_scalar($_POST['password'])) ? $_POST['password'] : '';

            $post['email'] = trim($post['email']);
            $post['password'] = trim($post['password']);

            $errors = Validator::validateLoginPost($post);

            if (empty($errors)) {
                $user = $this->em->getRepository('App\Entities\User')->findOneBy(['email' => $post['email']]);

                if ($user) {
                    if ($user->getHash() == Helper::generateHash($post['password'], $user->getSalt())) {
                        $expires = 60 * 60 * 24 * 30 * 12 * 3;

                        setcookie('id', $user->getId(), time() + $expires, '/', null, null, true);
                        setcookie('hash', $user->getHash(), time() + $expires, '/', null, null, true);
                        setcookie('token', Helper::generateToken(), time() + $expires, '/', null, null, true);

                        $this->redirect();

                        die();
                    } else {
                        $errors['email'] = Validator::NO_MATCHES;
                    }
                } else {
                    $errors['email'] = Validator::NO_MATCHES;
                }
            }
        }

        $this->render('public/login.php', compact('post', 'errors'));
    }

    public function configurate()
    {
        $logged = $this->isLoggedIn();

        if (!$logged) {
            $this->redirect();

            die();
        }

        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['name'])) {
                $post['name'] = (is_scalar($_POST['name'])) ? $_POST['name'] : '';

                if (Validator::validateName($post['name'])) {
                    $logged->setName($post['name']);

                    $this->em->flush();

                    $this->redirect('/config/');

                    die();
                } else {
                    $errors['name'] = Validator::NAME_ERROR;
                }
            }

            if (isset($_POST['email'])) {
                $post['email'] = (is_scalar($_POST['email'])) ? $_POST['email'] : '';

                if (Validator::validateEmail($post['email'])) {
                    $logged->setEmail($post['email']);

                    $this->em->flush();

                    $this->redirect('/config/');

                    die();
                } else {
                    $errors['email'] = Validator::EMAIL_ERROR;
                }
            }

            if (isset($_POST['password'])) {
                $post['password'] = (is_scalar($_POST['password'])) ? $_POST['password'] : '';

                if (Validator::validatePassword($post['password'])) {
                    if (isset($_POST['retryPassword'])) {
                        $post['retryPassword'] = (is_scalar($_POST['retryPassword'])) ? $_POST['retryPassword'] : '';

                        if (Validator::isPasswordsEquals($post['password'], $post['retryPassword'])) {
                            $salt = Helper::generateSalt();
                            $hash = Helper::generateHash($post['password'], $salt);

                            $logged->setHash($hash);
                            $logged->setSalt($salt);

                            $expires = 60 * 60 * 24 * 30 * 12 * 3;
                            setcookie('hash', $logged->getHash(), time() + $expires, '/', null, null, true);

                            $this->em->flush();

                            $this->redirect('/config/');

                            die();
                        } else {
                            $errors['retryPassword'] = Validator::RETRY_PASSWORD_ERROR;
                        }
                    } else {
                        $errors['retryPassword'] = Validator::RETRY_PASSWORD_ERROR;
                    }
                } else {
                    $errors['password'] = Validator::PASSWORD_ERROR;
                }
            }
        }

        $this->render('public/config.php', compact('logged', 'errors'));
    }

    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (Validator::validateToken($_POST['token']) and $this->isLoggedIn()) {
                setcookie('id', null, time()-1, '/');
                setcookie('hash', null, time()-1, '/');
                setcookie('token', null, time()-1, '/');
            }

            $this->redirect();

            die();
        }
    }
}