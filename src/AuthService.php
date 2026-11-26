<?php

namespace App;

class AuthService
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function register(string $name, string $email, string $password): int
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Пользователь с таким email уже существует.');
        }

        return $this->users->create(
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT)
        );
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            throw new \InvalidArgumentException('Неверный email или пароль.');
        }

        Session::login((int) $user['id']);

        return $user;
    }

    public function rememberUser(int $userId): void
    {
        $config = require __DIR__ . '/config.php';
        $cookie = $config['cookie'];

        setcookie(
            $cookie['name'],
            (string) $userId,
            [
                'expires' => time() + $cookie['lifetime'],
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    public function autoLogin(): ?array
    {
        $config = require __DIR__ . '/config.php';
        $cookieName = $config['cookie']['name'];

        if (!isset($_COOKIE[$cookieName])) {
            return null;
        }

        $userId = (int) $_COOKIE[$cookieName];

        if ($userId <= 0) {
            return null;
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->forgetUser();
            return null;
        }

        Session::login($userId);

        return $user;
    }

    public function forgetUser(): void
    {
        $config = require __DIR__ . '/config.php';
        $cookieName = $config['cookie']['name'];

        setcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public function logout(): void
    {
        $this->forgetUser();
        Session::logout();
    }
}