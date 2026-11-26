<?php
require __DIR__ . '/src/bootstrap.php';
$teacher = requireRole('teacher');
$title = 'Мои заявки';
$page = intValue($_GET['page'] ?? 1);
$pageUrl = static fn(int $number): string => 'dashboard.php?page=' . $number;
if (verifyPost()) {
    $id = intValue(post('id'));
    $comment = trim(post('comment'));
    if ($comment === '' || !mb_check_encoding($comment) || mb_strlen($comment) > 2000) {
        notice('Введите комментарий длиной до 2000 символов.', 'error');
    } elseif (repository()->saveTeacherComment($id, $teacher['id'], $comment)) {
        notice('Комментарий сохранён.');
    } else {
        notice('Комментарий доступен для вашей заявки со статусом «Отклонена».', 'error');
    }
    redirect($pageUrl(max(1, $page)) . '#application-' . $id);
}
[$rows, $page, $pages] = repository()->applications($teacher['id'], 0, '', $page);
$isAdmin = false;
require __DIR__ . '/src/header.php';
?>
<div class="page-heading">
    <h1>Мои заявки</h1>
    <a class="action" href="add_request.php">Подать заявку</a>
</div>
<?php if (!$rows): ?>
    <p class="panel empty">Вы пока не подали ни одной заявки.</p>
<?php endif; ?>
<div class="request-grid">
    <?php foreach ($rows as $row): ?>
        <article class="panel application" id="application-<?= h($row['id']) ?>">
            <?php require __DIR__ . '/src/card.php'; ?>
            <?php if ($row['status'] === 'rejected'): ?>
                <form method="post" class="card-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                    <div class="field">
                        <label for="comment-<?= h($row['id']) ?>">Комментарий после отклонения</label>
                        <textarea id="comment-<?= h($row['id']) ?>" name="comment" required maxlength="2000" rows="3"><?= h($row['teacher_comment']) ?></textarea>
                    </div>
                    <button class="action secondary" type="submit">Сохранить комментарий</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/src/pagination.php'; ?>
<?php require __DIR__ . '/src/footer.php'; ?>
