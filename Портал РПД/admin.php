<?php
require __DIR__ . '/src/bootstrap.php';
requireRole('admin');
$title = 'Панель председателя';
$discipline = intValue($_GET['discipline'] ?? 0);
$status = $_GET['status'] ?? '';
$status = is_string($status) && isset(STATUSES[$status]) ? $status : '';
$page = intValue($_GET['page'] ?? 1);
$pageUrl = static fn(int $number): string => 'admin.php?' . http_build_query(array_filter([
    'discipline' => $discipline, 'status' => $status, 'page' => $number,
]));
if (verifyPost()) {
    $id = intValue(post('id'));
    $decision = post('decision');
    $comment = trim(post('chair_comment'));
    if (!in_array($decision, ['approved', 'rejected'], true) || !mb_check_encoding($comment) || mb_strlen($comment) > 2000) {
        notice('Выберите решение и проверьте длину комментария (до 2000 символов).', 'error');
    } elseif (repository()->review($id, $decision, $comment)) {
        notice('Решение сохранено.');
    } else {
        notice('Заявка не найдена.', 'error');
    }
    redirect($pageUrl(max(1, $page)));
}
$disciplines = repository()->disciplines();
[$rows, $page, $pages, $total] = repository()->applications(null, $discipline, $status, $page);
$isAdmin = true;
require __DIR__ . '/src/header.php';
?>
<div class="page-heading"><h1>Панель председателя МЦК</h1></div>
<form class="panel filter-bar" method="get">
    <div class="field">
        <label for="discipline">Дисциплина</label>
        <select id="discipline" name="discipline">
            <option value="">Все дисциплины</option>
            <?php foreach ($disciplines as $item): ?>
                <option value="<?= h($item['id']) ?>"<?= (int)$item['id'] === $discipline ? ' selected' : '' ?>><?= h($item['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="status">Статус</label>
        <select id="status" name="status">
            <option value="">Все статусы</option>
            <?php foreach (STATUSES as $value => $label): ?>
                <option value="<?= h($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="action secondary" type="submit">Применить</button>
    <?php if ($discipline || $status): ?><a class="text-link" href="admin.php">Сбросить</a><?php endif; ?>
</form>
<p class="result-count">Заявок найдено: <?= h($total) ?></p>
<?php if (!$rows): ?><p class="panel empty">Заявок по выбранным условиям нет.</p><?php endif; ?>
<div class="request-grid">
    <?php foreach ($rows as $row): ?>
        <article class="panel application">
            <?php require __DIR__ . '/src/card.php'; ?>
            <form method="post" class="card-form">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                <div class="field">
                    <label for="decision-<?= h($row['id']) ?>">Решение</label>
                    <select id="decision-<?= h($row['id']) ?>" name="decision" required>
                        <option value="">Выберите статус</option>
                        <?php foreach (['approved', 'rejected'] as $decision): ?>
                            <option value="<?= h($decision) ?>"<?= $row['status'] === $decision ? ' selected' : '' ?>><?= h(STATUSES[$decision]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="chair-<?= h($row['id']) ?>">Комментарий к решению</label>
                    <textarea id="chair-<?= h($row['id']) ?>" name="chair_comment" maxlength="2000" rows="3"><?= h($row['chair_comment']) ?></textarea>
                </div>
                <button class="action secondary" type="submit">Сохранить решение</button>
            </form>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/src/pagination.php'; ?>
<?php require __DIR__ . '/src/footer.php'; ?>
