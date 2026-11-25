<?php
require __DIR__ . '/src/bootstrap.php';
$current = user();
if (!$current) {
    redirect('login.php');
}
$file = repository()->applicationFile(intValue($_GET['id'] ?? 0), $current);
if (!$file || !preg_match('/^[a-f0-9]{40}\.(?:pdf|zip)$/D', $file['stored_file'])) {
    http_response_code(404);
    exit('Файл не найден.');
}
$path = $config['storage'] . '/' . $file['stored_file'];
$handle = is_file($path) ? fopen($path, 'rb') : false;
if (!$handle) {
    http_response_code(404);
    exit('Файл не найден.');
}
session_write_close();
$pdf = str_ends_with($file['stored_file'], '.pdf');
header('Content-Type: ' . ($pdf ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
header('Content-Length: ' . fstat($handle)['size']);
header('Content-Disposition: attachment; filename="rpd.' . ($pdf ? 'pdf' : 'docx') . '"; filename*=UTF-8\'\'' . rawurlencode($file['original_file']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
fpassthru($handle);
fclose($handle);
