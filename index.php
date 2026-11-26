<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\AuthService;
use App\Session;
use App\UserRepository;

Session::start();

$auth = new AuthService(new UserRepository());

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'register') {
            $userId = $auth->register(
                trim($_POST['name'] ?? ''),
                trim($_POST['email'] ?? ''),
                $_POST['password'] ?? ''
            );

            $auth->rememberUser($userId);

            echo 'Регистрация выполнена. Пользователь сохранён в cookies.';
            exit;
        }

        if ($action === 'login') {
            $user = $auth->login(
                trim($_POST['email'] ?? ''),
                $_POST['password'] ?? ''
            );

            if (isset($_POST['remember'])) {
                $auth->rememberUser((int) $user['id']);
            }

            echo 'Вы вошли как ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '.';
            exit;
        }

        if ($action === 'logout') {
            $auth->logout();

            echo 'Вы вышли из системы.';
            exit;
        }
    }

    $user = $auth->autoLogin();

    if ($user !== null) {
        echo 'Автоматический вход выполнен как ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '.';
        exit;
    }
} catch (\InvalidArgumentException $exception) {
    echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
</head>
<body>
    <h1>Регистрация</h1>
    <form method="post">
        <input type="hidden" name="action" value="register">
        <input type="text" name="name" placeholder="Имя" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Зарегистрироваться</button>
    </form>

    <h1>Вход</h1>
    <form method="post">
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <label>
            <input type="checkbox" name="remember" value="1">
            Запомнить меня
        </label>
        <button type="submit">Войти</button>
    </form>

    <h1>Выход</h1>
    <form method="post">
        <input type="hidden" name="action" value="logout">
        <button type="submit">Выйти</button>
    </form>
</body>
</html>