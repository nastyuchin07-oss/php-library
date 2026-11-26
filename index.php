<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\AuthService;
use App\Session;
use App\UserRepository;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

Session::start();

$auth = new AuthService(new UserRepository());
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action'] ?? '') {
            case 'register':
                $auth->register(
                    trim($_POST['name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    $_POST['password'] ?? '',
                    $_POST['bg_color'] ?? '',
                    $_POST['text_color'] ?? ''
                );
                break;
            case 'login':
                $auth->login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '', isset($_POST['remember']));
                break;
            case 'settings':
                $auth->saveSettings($_POST['bg_color'] ?? '', $_POST['text_color'] ?? '');
                break;
            case 'logout':
                $auth->logout();
                break;
        }
        header('Location: index.php');
        exit;
    } catch (\InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}

$user = $auth->currentUser();

$bg = AuthService::DEFAULT_BG;
$text = AuthService::DEFAULT_TEXT;
if ($user !== null) {
    $bg = AuthService::validColor($_COOKIE['bg_color'] ?? $user['bg_color'], AuthService::DEFAULT_BG);
    $text = AuthService::validColor($_COOKIE['text_color'] ?? $user['text_color'], AuthService::DEFAULT_TEXT);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
</head>
<body style="background: <?= e($bg) ?>; color: <?= e($text) ?>; font-family: Arial, sans-serif; padding: 20px;">
<?php if ($error !== null): ?>
    <p style="color: red;"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($user !== null): ?>
    <h1>Здравствуйте, <?= e($user['name']) ?>!</h1>
    <p>Вы авторизованы. Настройки хранятся в базе данных и передаются в cookie.</p>

    <h2>Настройки</h2>
    <form method="post">
        <input type="hidden" name="action" value="settings">
        <label>Цвет фона: <input type="color" name="bg_color" value="<?= e($bg) ?>"></label><br><br>
        <label>Цвет текста: <input type="color" name="text_color" value="<?= e($text) ?>"></label><br><br>
        <button type="submit">Сохранить настройки</button>
    </form>

    <form method="post" style="margin-top: 20px;">
        <input type="hidden" name="action" value="logout">
        <button type="submit">Выйти</button>
    </form>
<?php else: ?>
    <h1>Вход</h1>
    <form method="post">
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <label><input type="checkbox" name="remember" value="1"> Запомнить меня</label>
        <button type="submit">Войти</button>
    </form>

    <h1>Регистрация</h1>
    <form method="post">
        <input type="hidden" name="action" value="register">
        <input type="text" name="name" placeholder="Имя" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль (от 8 символов)" required>
        <label>Цвет фона: <input type="color" name="bg_color" value="#ffffff"></label>
        <label>Цвет текста: <input type="color" name="text_color" value="#000000"></label>
        <button type="submit">Зарегистрироваться</button>
    </form>
<?php endif; ?>
</body>
</html>