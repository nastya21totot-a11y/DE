<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$columns = [
    'disciplines' => ['id', 'name'],
    'users' => ['id', 'login', 'password_hash', 'full_name', 'phone', 'email', 'role'],
    'teacher_disciplines' => ['teacher_id', 'discipline_id'],
    'applications' => ['id', 'teacher_id', 'discipline_id', 'version_number', 'stored_file', 'original_file',
        'version_comment', 'teacher_comment', 'chair_comment', 'status', 'submitted_at'],
];

function csvRecords(string $table, array $columns): array
{
    $handle = fopen(__DIR__ . '/' . $table . '.csv', 'rb');
    if (!$handle) {
        throw new RuntimeException("Не удалось открыть $table.csv.");
    }
    try {
        if (fgetcsv($handle, 0, ',', '"', '') !== $columns) {
            throw new RuntimeException("Столбцы $table.csv не совпадают со схемой.");
        }
        $rows = [];
        while (($record = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if (count($record) !== count($columns)) {
                throw new RuntimeException("Неверное число полей в $table.csv.");
            }
            $rows[] = array_combine($columns, $record);
        }
        return $rows;
    } finally {
        fclose($handle);
    }
}

$created = [];
$db = null;
try {
    $host = getenv('RPD_DB_HOST') ?: '127.0.0.1';
    $port = getenv('RPD_DB_PORT') ?: '3306';
    $storage = getenv('RPD_STORAGE') ?: '/opt/lampp/rpd-storage';
    $db = new PDO("mysql:host=$host;port=$port;dbname=rpd_portal;charset=utf8mb4",
        getenv('RPD_DB_USER') ?: 'root', getenv('RPD_DB_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    if (!is_dir($storage) || !is_writable($storage)) {
        throw new RuntimeException('Каталог RPD_STORAGE должен существовать и быть доступен для записи.');
    }
    $data = [];
    foreach ($columns as $table => $fields) {
        $data[$table] = csvRecords($table, $fields);
    }
    $db->beginTransaction();
    $existingDisciplines = $db->query('SELECT id, name FROM disciplines ORDER BY id FOR UPDATE')->fetchAll();
    $existingUsers = $db->query('SELECT id, login, role FROM users ORDER BY id FOR UPDATE')->fetchAll();
    foreach ($existingDisciplines as &$discipline) {
        $discipline['id'] = (string)$discipline['id'];
    }
    unset($discipline);
    $fresh = count($existingUsers) === 1 && (int)$existingUsers[0]['id'] === 1
        && $existingUsers[0]['login'] === 'Admin' && $existingUsers[0]['role'] === 'admin'
        && $existingDisciplines === $data['disciplines']
        && (int)$db->query('SELECT COUNT(*) FROM teacher_disciplines')->fetchColumn() === 0
        && (int)$db->query('SELECT COUNT(*) FROM applications')->fetchColumn() === 0;
    if (!$fresh) {
        throw new RuntimeException('Нужна свежая демонстрационная база, созданная из rpd.sql.');
    }

    foreach (['users', 'teacher_disciplines', 'applications'] as $table) {
        $fields = $columns[$table];
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $statement = $db->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . $placeholders . ')');
        foreach ($data[$table] as $row) {
            if ($table === 'users' && $row['login'] === 'Admin') {
                continue;
            }
            if ($table === 'applications') {
                $row['teacher_comment'] = $row['teacher_comment'] === '' ? null : $row['teacher_comment'];
                $row['chair_comment'] = $row['chair_comment'] === '' ? null : $row['chair_comment'];
            }
            $statement->execute(array_values($row));
        }
    }
    foreach ($data['applications'] as $row) {
        $name = $row['stored_file'];
        if (!preg_match('/^[a-f0-9]{40}\.(?:pdf|zip)$/D', $name)) {
            throw new RuntimeException('Некорректное имя файла РПД в CSV.');
        }
        $sample = file_get_contents(__DIR__ . '/rpd-storage/' . $name);
        if ($sample === false || $sample === '') {
            throw new RuntimeException('Файл РПД для демонстрационной заявки отсутствует.');
        }
        $path = rtrim($storage, '/\\') . '/' . $name;
        $output = fopen($path, 'xb');
        if (!$output) {
            throw new RuntimeException('Не удалось создать файл РПД без перезаписи.');
        }
        $created[] = $path;
        try {
            if (fwrite($output, $sample) !== strlen($sample)) {
                throw new RuntimeException('Файл РПД записан не полностью.');
            }
        } finally {
            fclose($output);
        }
    }
    $db->commit();
    echo "Импорт завершён. Добавлены преподаватель, его дисциплины и две заявки.\n";
} catch (Throwable $error) {
    if ($db?->inTransaction()) {
        $db->rollBack();
    }
    foreach ($created as $path) {
        unlink($path);
    }
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
