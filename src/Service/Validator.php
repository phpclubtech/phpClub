<?php

namespace phpClub\Service;

class Validator
{
    const PHPREGEXP = "/^(Клуб)[\s\w\W]*(PHP)[\s\w\W]*$/ui";

    const EMAIL_ERROR = 'Некорректный адрес';
    const NAME_ERROR = 'Имя должно быть короче 20 русских или английских символов';
    const PASSWORD_ERROR = 'Пароль должен быть длиньше 6 символов и короче 20';
    const RETRY_PASSWORD_ERROR = 'Пароли не совпадают';
    const NO_MATCHES = 'Совпадений не найдено';

    const ARCHIVE_LINK_ERROR = 'Ссылка должна начинаться с http(s):// и ссылаться либо на 2ch.hk/pr/arch/... либо на arhivach.org/thread/...';

    public static function validateThreadSubject($subject)
    {
        return boolval((preg_match(self::PHPREGEXP, $subject)));
    }

    public static function validateThreadLink($path)
    {
        return (boolval((preg_match('!\/pr\/res\/(\d+)\.html(#\d+)?!', $path, $matches)))) ? $matches[1] : false;
    }

    public static function validateRegistrationLink($path)
    {
        return boolval((preg_match('!\/registration\/?!', $path)));
    }

    public static function validateLoginLink($path)
    {
        return boolval((preg_match('!\/login\/?!', $path)));
    }

    public static function validateConfigLink($path)
    {
        return boolval(preg_match('!\/config\/?!', $path));
    }

    public static function validateLogoutLink($path): bool
    {
        return boolval(preg_match('!\/logout\/?!', $path));
    }

    public static function validateAddArchiveLink($path): bool
    {
        return boolval(preg_match('!\/addarchivelink\/?!', $path));
    }

    public static function validateRemoveArchiveLink($path): bool
    {
        return boolval(preg_match('!\/removearchivelink\/\d+\/?!', $path));
    }

    public static function validateSearchLink($path)
    {
        return (boolval(preg_match('!\/search\/(.+[^/])\/?!ui', $path, $matches))) ? $matches[1] : false;
    }

    public static function validateEmail($email): bool
    {
        return boolval(preg_match('/[^ ]+@[^ ]+\.[^ ]+/i', $email));
    }

    public static function validateName($name): bool
    {
        return boolval((preg_match('/^[a-zA-Zа-яёА-ЯЁ\-\'\ ]{1,20}$/u', $name)));
    }

    public static function validatePassword($password): bool
    {
        return boolval(preg_match('/^(.){6,20}$/', $password));
    }

    public static function isPasswordsEquals($password, $retryPassword): bool
    {
        return $password === $retryPassword;
    }

    public static function validateRegistrationPost($post)
    {
        $errors = [];

        if (!self::validateEmail($post['email'])) {
            $errors['email'] = self::EMAIL_ERROR;
        }

        if (!self::validateName($post['name'])) {
            $errors['name'] = self::NAME_ERROR;
        }

        if (!self::validatePassword($post['password'])) {
            $error['password'] = self::PASSWORD_ERROR;
        }

        if (!self::isPasswordsEquals($post['password'], $post['retryPassword'])) {
            $errors['retryPassword'] = self::RETRY_PASSWORD_ERROR;
        }

        return $errors;
    }

    public static function validateLoginPost($post)
    {
        $errors = [];

        if (!self::validateEmail($post['email'])) {
            $errors['email'] = self::EMAIL_ERROR;
        }

        if (!self::validatePassword($post['password'])) {
            $errors['password'] = self::PASSWORD_ERROR;
        }

        return $errors;
    }

    public static function validateToken($token)
    {
        $isValidToken = isset($_COOKIE['token']) && $token != '' && $_COOKIE['token'] != '' && $token === $_COOKIE['token'];

        return $isValidToken;
    }
}
