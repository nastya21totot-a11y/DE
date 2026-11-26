<?php
declare(strict_types=1);

const MAX_UPLOAD = 20 * 1024 * 1024;

function storeProgram(array $file, string $storage): array
{
    $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new DomainException('Максимальный размер файла — 20 МБ.');
    }
    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new DomainException($uploadError === UPLOAD_ERR_NO_FILE ? 'Выберите файл РПД.' : 'Не удалось принять файл. Загрузите его ещё раз.');
    }
    if ($file['size'] < 1 || $file['size'] > MAX_UPLOAD) {
        throw new DomainException('Загрузите непустой файл размером до 20 МБ.');
    }
    $name = basename(str_replace('\\', '/', $file['name']));
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'docx'], true) || !mb_check_encoding($name) || mb_strlen($name) > 255) {
        throw new DomainException('Выберите файл PDF или DOCX с именем до 255 символов.');
    }
    if (!is_dir($storage) || !is_writable($storage)) {
        throw new RuntimeException('Каталог хранения РПД недоступен для записи.');
    }
    $stored = bin2hex(random_bytes(20)) . ($extension === 'docx' ? '.zip' : '.pdf');
    $path = $storage . '/' . $stored;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Не удалось сохранить файл РПД.');
    }
    try {
        $valid = $extension === 'pdf'
            ? (new finfo(FILEINFO_MIME_TYPE))->file($path) === 'application/pdf'
            : isWordDocument($path);
        if (!$valid) {
            throw new DomainException('Содержимое файла не соответствует формату PDF или DOCX.');
        }
    } catch (Throwable $error) {
        unlink($path);
        throw $error;
    }
    return [$stored, $name];
}

function isWordDocument(string $path): bool
{
    try {
        $archive = new PharData($path, FilesystemIterator::SKIP_DOTS, null, Phar::ZIP);
        return isset($archive['[Content_Types].xml'], $archive['word/document.xml'])
            && $archive['[Content_Types].xml']->isFile() && $archive['word/document.xml']->isFile();
    } catch (Exception) {
        return false;
    }
}
