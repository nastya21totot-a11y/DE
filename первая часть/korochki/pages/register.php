<?php

require_once __DIR__ . '/../helpers/db.php';

$pageTitle = 'Регистрация — Корочки.Есть';
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php'],
    ['title' => 'Регистрация', 'url' => '#']
];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $fio = trim($_POST['fio'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!preg_match('/^[A-Za-z0-9]{6,50}$/D', $login)) {
        $error='Логин: только латиница и цифры, от 6 до 50 символов.';
    } elseif (mb_strlen($password)<8) {
        $error='Пароль: минимум 8 символов.';
    } elseif (!preg_match('/^[А-Яа-яЁё]+(?: +[А-Яа-яЁё]+)*$/uD',$fio) || mb_strlen($fio)>100) {
        $error='ФИО: только кириллица и пробелы, до 100 символов.';
    } elseif (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100) {
        $error='Неверный формат электронной почты.';
    } elseif (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/D',$phone)) {
        $error='Телефон: используйте формат 8(999)123-45-67.';
    } else {
        try {
            $stmt=$pdo->prepare('INSERT INTO users(login,password,fio,email,phone,role_id) VALUES(?,?,?,?,?,2)');
            $stmt->execute([$login,coursePasswordHash($password),$fio,$email,$phone]);
            $success='Регистрация успешна! <a href="auth.php">Войдите</a>.';
        } catch(PDOException $ex) {
            $error=$ex->getCode()==='23000'?'Пользователь с таким логином уже существует.':'Не удалось сохранить пользователя. Попробуйте ещё раз.';
        }
    }
}

ob_start();
?>
<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 20px;">Регистрация</h2>

    <?php if ($error): ?><div style="color: red; text-align: center; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div style="color: green; text-align: center; margin-bottom: 15px;"><?= $success ?></div><?php endif; ?>

    <form action="" method="POST"><?= csrf() ?>
        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" name="login" id="login" value="<?= e($_POST['login']??'') ?>" required>
            <span class="error-text">латиница и цифры, не менее 6 символов</span>
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" name="password" id="password" required>
            <span class="error-text">минимум 8 символов</span>
        </div>
        <div class="form-group">
            <label for="fio">ФИО</label>
            <input type="text" name="fio" id="fio" value="<?= e($_POST['fio']??'') ?>" required>
            <span class="error-text">Используйте только символы кириллицы и пробелы</span>
        </div>
        <div class="form-group">
            <label for="email">Адрес эл. почты</label>
            <input type="email" name="email" id="email" value="<?= e($_POST['email']??'') ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" name="phone" id="phone" inputmode="numeric" autocomplete="tel" value="<?= e($_POST['phone']??'') ?>" placeholder="8(XXX)XXX-XX-XX" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            <a href="auth.php" class="btn-link">Уже есть аккаунт? Войти</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
