<?php
namespace App;

class Validator
{
    const PHPREGEXP = "/^(Клуб)[\s\w\W]*(PHP)[\s\w\W]*$/ui";
    const ARCHIVESREGEXP = "!^https?:\/\/((2ch\.hk\/pr\/arch\/\d{4}-\d{2}-\d{2}\/res\/\d+\.html)|(arhivach\.org\/thread\/\d+\/?))$!";

    const EMAIL_ERROR = "Некорректный адрес";
    const NAME_ERROR = "Имя должно быть короче 20 русских или английских символов";
    const PASSWORD_ERROR = "Пароль должен быть длиньше 6 символов и короче 20";
    const RETRY_PASSWORD_ERROR = "Пароли не совпадают";
    const NO_MATCHES = "Совпадений не найдено";

    const ARCHIVE_LINK_ERROR = "Ссылка должна начинаться с http(s):// и ссылаться либо на 2ch.hk/pr/arch/... либо на arhivach.org/thread/...";

    public static function validateThreadSubject($subject)
    {
        if (preg_match(self::PHPREGEXP, $subject)) {
            return true;
        }

        return false;
    }

    public static function validateThreadLink($path)
    {
        $matches = array();

        if (preg_match('!\/pr\/res\/(\d+)\.html(#\d+)?!', $path, $matches)) {
            $number = $matches[1];

            return $number;
        }

        return false;
    }

    public static function validateChainLink($path)
    {
        $matches = array();

        if (preg_match('!\/pr\/chain\/(\d+)(#\d+)?!', $path, $matches)) {
            $number = $matches[1];

            return $number;
        }

        return false;
    }

    public static function validateRegistrationLink($path)
    {
        if (preg_match('!\/registration\/?!', $path)) {
            return true;
        }

        return false;
    }

    public static function validateLoginLink($path)
    {
        if (preg_match('!\/login\/?!', $path)) {
            return true;
        }

        return false;
    }

    public static function validateConfigLink($path)
    {
        if (preg_match('!\/config\/?!', $path)) {
            return true;
        }

        return false;
    }

    public static function validateLogoutLink($path)
    {
        if (preg_match('!\/logout\/?!', $path)) {
            return true;
        }

        return false;
    }

    public static function validateAddArchiveLink($path)
    {
        if (preg_match('!\/addarchivelink\/?!', $path)) {
            return true;
        }

        return false;
    }

    public static function validateRemoveArchiveLink($path)
    {
        if (preg_match('!\/removearchivelink\/\d+\/?!', $path)) {
            return true;
        }

        return false;
    }

    public static function validateRefLinks($comment)
    {
        $regexp = '/<a href="[\S]+" class="post-reply-link" data-thread="(\d+)" data-num="(\d+)">/';
        $matches = array();

        preg_match_all($regexp, $comment, $matches);

        return $matches[2];
    }

    public static function validateEmail($email)
    {
        if (preg_match('/[^ ]+@[^ ]+\.[^ ]+/i', $email)) {
            return true;
        }

        return false;
    }

    public static function validateName($name)
    {
        if (preg_match('/^[a-zA-Zа-яёА-ЯЁ\-\'\ ]{1,20}$/u', $name)) {
            return true;
        }

        return false;
    }

    public static function validatePassword($password)
    {
        if (preg_match('/^(.){6,20}$/', $password)) {
            return true;
        }

        return false;
    }

    public static function isPasswordsEquals($password, $retryPassword) {
        if ($password === $retryPassword) {
            return true;
        }

        return false;
    }

    public static function validateArchiveLink($link)
    {
        if (preg_match(self::ARCHIVESREGEXP, $link)) {
            return true;
        }

        return false;
    }

    public static function validateRegistrationPost($post)
    {
        $errors = array();

        if (!Validator::validateEmail($post['email'])) {
            $errors['email'] = self::EMAIL_ERROR;
        }

        if (!Validator::validateName($post['name'])) {
            $errors['name'] = self::NAME_ERROR;
        }

        if (!Validator::validatePassword($post['password'])) {
            $error['password'] = self::PASSWORD_ERROR;
        }


        if (!Validator::isPasswordsEquals($post['password'], $post['retryPassword'])) {
            $errors['retryPassword'] = self::RETRY_PASSWORD_ERROR;
        }

        return $errors;
    }

    public static function validateLoginPost($post)
    {
        $errors = array();

        if (!Validator::validateEmail($post['email'])) {
            $errors['email'] = self::EMAIL_ERROR;
        }

        if (!Validator::validatePassword($post['password'])) {
            $errors['password'] = self::PASSWORD_ERROR;
        }

        return $errors;
    }

    public static function validateToken($token)
    {
        if (isset($_COOKIE['token'])) {
            if ($token != "" and $_COOKIE['token'] != "" and $token === $_COOKIE['token']) {
                return true;
            }
        }
        
        return false;
    }
}