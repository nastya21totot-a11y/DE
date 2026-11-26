<?php
require __DIR__ . '/src/bootstrap.php';
$title = 'Главная';
require __DIR__ . '/src/header.php';
$slides = [
    'Преподаватель готовит рабочую программу', 'Компьютерный класс техникума',
    'Преподаватели обсуждают программу', 'Проект программы рядом с ноутбуком',
    'Учебная аудитория с проектором',
];
?>
<section class="home-hero">
    <div class="hero-copy">
        <h1>Согласование рабочих программ дисциплин</h1>
        <p>Загрузите РПД и следите за рассмотрением заявки председателем методической цикловой комиссии.</p>
        <?php if (($current['role'] ?? '') === 'admin'): ?>
            <a class="action" href="admin.php">Открыть панель</a>
        <?php elseif ($current): ?>
            <a class="action" href="add_request.php">Подать заявку</a>
        <?php else: ?>
            <a class="action" href="login.php">Войти в портал</a>
            <a class="text-link" href="register.php">Зарегистрироваться</a>
        <?php endif; ?>
    </div>
    <div class="gallery" data-carousel role="region" aria-label="Фотографии учебного процесса">
        <div class="gallery-window">
            <?php foreach ($slides as $index => $description): ?>
                <img src="assets/slides/slide-<?= $index + 1 ?>.webp" alt="<?= h($description) ?>" width="800" height="540"<?= $index ? ' hidden' : '' ?>>
            <?php endforeach; ?>
        </div>
        <div class="gallery-controls" hidden>
            <button type="button" data-prev aria-label="Предыдущая фотография">←</button>
            <span data-position>1 / 5</span>
            <button type="button" data-next aria-label="Следующая фотография">→</button>
            <button type="button" data-pause>Пауза</button>
        </div>
    </div>
</section>
<?php require __DIR__ . '/src/footer.php'; ?>
