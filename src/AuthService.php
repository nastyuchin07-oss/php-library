<?php

namespace App;

class AuthService
{
    public const DEFAULT_BG = '#ffffff';
    public const DEFAULT_TEXT = '#000000';

    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public static function validColor(mixed $color, string $default): string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $default;
    }

    public function register(string $name, string $email, string $password, string $bg, string $text): void
    {
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Укажите имя и корректный email.');
        }
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Пароль должен быть не короче 8 символов.');
        }
        if ($this->users->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Пользователь с таким email уже существует.');
        }

        $bg = self::validColor($bg, self::DEFAULT_BG);
        $text = self::validColor($text, self::DEFAULT_TEXT);

        $userId = $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $bg, $text);

        Session::login($userId);
        $this->setSettingsCookies($bg, $text);
        $this->rememberUser($userId);
    }

    public function login(string $email, string $password, bool $remember): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            throw new \InvalidArgumentException('Неверный email или пароль.');
        }

        Session::login((int) $user['id']);
        $this->setSettingsCookies($user['bg_color'], $user['text_color']);

        if ($remember) {
            $this->rememberUser((int) $user['id']);
        }
    }

    public function saveSettings(string $bg, string $text): void
    {
        $userId = Session::userId();
        if ($userId === null) {
            throw new \InvalidArgumentException('Сначала войдите в систему.');
        }

        $bg = self::validColor($bg, self::DEFAULT_BG);
        $text = self::validColor($text, self::DEFAULT_TEXT);

        $this->users->updateSettings($userId, $bg, $text);
        $this->setSettingsCookies($bg, $text);
    }

    public function currentUser(): ?array
    {
        $userId = Session::userId();
        if ($userId !== null) {
            $user = $this->users->findById($userId);
            if ($user !== null) {
                return $user;
            }
        }

        return $this->autoLogin();
    }

    public function logout(): void
    {
        $userId = Session::userId();
        if ($userId !== null) {
            $this->users->saveRememberToken($userId, null);
        }

        $config = require __DIR__ . '/config.php';
        $this->setCookie($config['cookie']['name'], '', time() - 3600);
        $this->setCookie('bg_color', '', time() - 3600, false);
        $this->setCookie('text_color', '', time() - 3600, false);

        Session::logout();
    }

    private function autoLogin(): ?array
    {
        $config = require __DIR__ . '/config.php';
        $name = $config['cookie']['name'];

        if (!isset($_COOKIE[$name])) {
            return null;
        }

        $parts = explode(':', (string) $_COOKIE[$name]);
        $user = count($parts) === 2 ? $this->users->findById((int) $parts[0]) : null;

        if ($user === null || !hash_equals((string) $user['remember_token_hash'], hash('sha256', $parts[1]))) {
            $this->setCookie($name, '', time() - 3600);
            return null;
        }

        Session::login((int) $user['id']);
        $this->setSettingsCookies($user['bg_color'], $user['text_color']);

        return $user;
    }

    private function rememberUser(int $userId): void
    {
        $config = require __DIR__ . '/config.php';
        $token = bin2hex(random_bytes(32));

        $this->users->saveRememberToken($userId, hash('sha256', $token));
        $this->setCookie(
            $config['cookie']['name'],
            $userId . ':' . $token,
            time() + $config['cookie']['lifetime']
        );
    }

    private function setSettingsCookies(string $bg, string $text): void
    {
        $config = require __DIR__ . '/config.php';
        $expires = time() + $config['cookie']['lifetime'];

        $this->setCookie('bg_color', $bg, $expires, false);
        $this->setCookie('text_color', $text, $expires, false);
    }

    private function setCookie(string $name, string $value, int $expires, bool $httpOnly = true): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);
    }
}