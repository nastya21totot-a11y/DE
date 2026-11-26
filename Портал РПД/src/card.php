<header class="card-heading">
    <div>
        <h2><?= h($row['discipline']) ?></h2>
        <p>Версия <?= h($row['version_number']) ?> · <time datetime="<?= h(str_replace(' ', 'T', $row['submitted_at'])) ?>"><?= h(date('d.m.Y H:i', strtotime($row['submitted_at']))) ?></time></p>
    </div>
    <span class="status status-<?= h($row['status']) ?>"><?= h(STATUSES[$row['status']]) ?></span>
</header>
<dl class="card-details">
    <?php if ($isAdmin): ?>
        <div><dt>Преподаватель</dt><dd><?= h($row['teacher']) ?> · <?= h($row['email']) ?></dd></div>
    <?php endif; ?>
    <div><dt>Файл РПД</dt><dd><a class="text-link" href="download.php?id=<?= h($row['id']) ?>"><?= h($row['original_file']) ?></a></dd></div>
    <div><dt>Комментарий к версии</dt><dd class="preserve-lines"><?= h($row['version_comment']) ?></dd></div>
    <div><dt>Комментарий председателя МЦК</dt><dd class="preserve-lines"><?= h($row['chair_comment'] ?? '—') ?></dd></div>
    <?php if ($row['teacher_comment'] !== null): ?>
        <div><dt>Комментарий преподавателя после отклонения</dt><dd class="preserve-lines"><?= h($row['teacher_comment']) ?></dd></div>
    <?php endif; ?>
</dl>
