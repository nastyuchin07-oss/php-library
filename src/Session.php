<?php

namespace App;

class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(int $userId): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function userId(): ?int
    {
        self::start();

        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}