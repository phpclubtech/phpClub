<?php
/**
 * Created by PhpStorm.
 * User: main
 * Date: 4/30/2017
 * Time: 5:50 PM
 */

namespace phpClub\Service;

use Doctrine\ORM\EntityManager;
use phpClub\Entity\User;
use phpClub\Repository\UserRepository;
use Symfony\Component\Cache\Simple\FilesystemCache;

class Authorizer
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function isLoggedIn()
    {
        if (isset($_COOKIE['id'])) {
            $student = $this->userRepository->find($_COOKIE['id']);

            if (isset($_COOKIE['token'])) {
                if ($student->getHash() == $_COOKIE['hash']) {
                    return $student;
                }
            }
        }

        return false;
    }

    public function register(): array
    {
        $post = $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post['email'] = (isset($_POST['email']) and is_scalar($_POST['email'])) ? $_POST['email'] : '';
            $post['name'] = (isset($_POST['name']) and is_scalar($_POST['name'])) ? $_POST['name'] : '';
            $post['password'] = (isset($_POST['password']) and is_scalar($_POST['password'])) ? $_POST['password'] : '';
            $post['retryPassword'] = (isset($_POST['retryPassword']) and is_scalar($_POST['retryPassword']))
                ? $_POST['retryPassword']
                : '';

            $post['email'] = trim($post['email']);
            $post['name'] = trim($post['name']);
            $post['password'] = trim($post['password']);
            $post['retryPassword'] = trim($post['retryPassword']);

            $errors = Validator::validateRegistrationPost($post);

            if ($this->userRepository->findOneBy(['email' => $post['email']])) {
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

                $this->userRepository->persist($user);
                $this->userRepository->flush();

                $this->login();
            }
        }

        return $errors;
    }

    public function login(): array
    {
        $post = $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post['email'] = (isset($_POST['email']) and is_scalar($_POST['email'])) ? $_POST['email'] : '';
            $post['password'] = (isset($_POST['password']) and is_scalar($_POST['password'])) ? $_POST['password'] : '';

            $post['email'] = trim($post['email']);
            $post['password'] = trim($post['password']);

            $errors = Validator::validateLoginPost($post);

            if (empty($errors)) {
                $user = $this->userRepository->findOneBy(['email' => $post['email']]);

                if ($user) {
                    if ($user->getHash() == Helper::generateHash($post['password'], $user->getSalt())) {
                        $expires = 60 * 60 * 24 * 30 * 12 * 3;

                        setcookie('id', $user->getId(), time() + $expires, '/', null, null, true);
                        setcookie('hash', $user->getHash(), time() + $expires, '/', null, null, true);
                        setcookie('token', Helper::generateToken(), time() + $expires, '/', null, null, true);
                    } else {
                        $errors['email'] = Validator::NO_MATCHES;
                    }
                } else {
                    $errors['email'] = Validator::NO_MATCHES;
                }
            }
        }

        return $errors;
    }

    public function configure(User $user): array
    {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['name'])) {
                $post['name'] = (is_scalar($_POST['name'])) ? $_POST['name'] : '';

                if (Validator::validateName($post['name'])) {
                    $user->setName($post['name']);

                    $this->userRepository->flush();
                } else {
                    $errors['name'] = Validator::NAME_ERROR;
                }
            }

            if (isset($_POST['email'])) {
                $post['email'] = (is_scalar($_POST['email'])) ? $_POST['email'] : '';

                if (Validator::validateEmail($post['email'])) {
                    $user->setEmail($post['email']);

                    $this->userRepository->flush();
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

                            $user->setHash($hash);
                            $user->setSalt($salt);

                            $expires = 60 * 60 * 24 * 30 * 12 * 3;
                            setcookie('hash', $user->getHash(), time() + $expires, '/', null, null, true);

                            $this->userRepository->flush();
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

        return $errors;
    }

    public function logout()
    {        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (Validator::validateToken($_POST['token']) and $this->isLoggedIn()) {
                setcookie('id', null, time()-1, '/');
                setcookie('hash', null, time()-1, '/');
                setcookie('token', null, time()-1, '/');
            }
        }
    }
}
