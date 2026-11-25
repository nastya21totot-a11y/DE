<?php

require_once __DIR__ . '/../helpers/db.php';

$pageTitle = 'Авторизация — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php'],
    ['title' => 'Авторизация', 'url' => '#']
];

if (isset($_SESSION['user_id'])) {
    header('Location: '.((int)$_SESSION['user_role'] === 1 ? 'admin.php' : 'zayavki.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && coursePasswordMatches($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_name'] = $user['fio'];
            $_SESSION['user_role'] = $user['role_id'];
            header('Location: '.((int)$user['role_id']===1?'admin.php':'zayavki.php'));
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных.';
    }
}

ob_start();
?>
<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 20px;">Авторизация</h2>

    <?php if ($error): ?><div style="color: red; text-align: center; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="" method="POST"><?= csrf() ?>
        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" name="login" id="login" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Войти в аккаунт</button>
            <a href="register.php" class="btn-link">Еще не зарегистрированы? Регистрация</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
