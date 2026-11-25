<?php
require __DIR__ . '/src/bootstrap.php';
require __DIR__ . '/src/uploads.php';
$teacher = requireRole('teacher');
$title = 'Новая заявка';
$disciplines = repository()->disciplines($teacher['id']);
$errors = [];
$discipline = 0;
$version = $comment = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_UPLOAD) {
    $errors['file'] = 'Максимальный размер файла — 20 МБ.';
} elseif (verifyPost()) {
    $discipline = intValue(post('discipline'));
    $version = trim(post('version'));
    $comment = trim(post('version_comment'));
    if (!in_array($discipline, array_map('intval', array_column($disciplines, 'id')), true)) {
        $errors['discipline'] = 'Выберите одну из назначенных вам дисциплин.';
    }
    if (!preg_match('/^[0-9]+(?:\.[0-9]+)*$/D', $version) || strlen($version) > 24) {
        $errors['version'] = 'Укажите номер версии, например 1 или 2.1.';
    }
    if ($comment === '' || !mb_check_encoding($comment) || mb_strlen($comment) > 2000) {
        $errors['version_comment'] = 'Введите комментарий к версии длиной до 2000 символов.';
    }
    if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
        $errors['file'] = 'Выберите файл РПД.';
    }
    if (!$errors) {
        try {
            [$stored, $name] = storeProgram($_FILES['file'], $config['storage']);
            try {
                repository()->submit($teacher['id'], $discipline, $version, $stored, $name, $comment);
            } catch (Throwable $error) {
                unlink($config['storage'] . '/' . $stored);
                throw $error;
            }
            notice('Заявка отправлена председателю МЦК.');
            redirect('my.php');
        } catch (DomainException $error) {
            $errors['file'] = $error->getMessage();
        } catch (RuntimeException $error) {
            error_log($error->getMessage());
            $errors['file'] = 'Не удалось сохранить заявку. Повторите попытку позже.';
        }
    }
}
require __DIR__ . '/src/header.php';
?>
<section class="panel form-panel">
    <h1>Новая заявка</h1>
    <?php if (!$disciplines): ?>
        <p>Вам ещё не назначены дисциплины. Обратитесь к председателю МЦК.</p>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <div class="field">
                <label for="discipline">Дисциплина</label>
                <select id="discipline" name="discipline" required<?= invalid('discipline') ?>>
                    <option value="">Выберите дисциплину</option>
                    <?php foreach ($disciplines as $item): ?>
                        <option value="<?= h($item['id']) ?>"<?= (int)$item['id'] === $discipline ? ' selected' : '' ?>><?= h($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php fieldError('discipline'); ?>
            </div>
            <div class="field">
                <label for="version">Номер версии</label>
                <input id="version" name="version" required maxlength="24" placeholder="Например, 1.2" value="<?= h($version) ?>"<?= invalid('version') ?>>
                <?php fieldError('version'); ?>
            </div>
            <div class="field">
                <label for="file">Файл РПД</label>
                <input id="file" type="file" name="file" accept=".pdf,.docx" required<?= invalid('file') ?>>
                <p class="hint">PDF или DOCX, до 20 МБ</p>
                <?php fieldError('file'); ?>
            </div>
            <div class="field">
                <label for="version_comment">Комментарий к версии</label>
                <textarea id="version_comment" name="version_comment" maxlength="2000" rows="4" required<?= invalid('version_comment') ?>><?= h($comment) ?></textarea>
                <?php fieldError('version_comment'); ?>
            </div>
            <button class="action" type="submit">Отправить</button>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/src/footer.php'; ?>
