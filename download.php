<?php
require_once 'db.php';

$id          = filter_input(INPUT_GET, 'id',    FILTER_VALIDATE_INT);
$chunk_index = filter_input(INPUT_GET, 'chunk', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('不正なリクエストです');
}

$stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('ファイルが見つかりません');
}

$path = 'E:/learnphp_uploads/attachments/' . $file['stored_name'];

if (!file_exists($path)) {
    http_response_code(404);
    exit('ファイルが見つかりません');
}

$file_size  = filesize($path);
$chunk_size = 50 * 1024 * 1024; // 50MB

// chunkパラメータがなければファイル情報だけ返す
if ($chunk_index === null || $chunk_index === false) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok'           => true,
        'file_size'    => $file_size,
        'total_chunks' => (int)ceil($file_size / $chunk_size),
        'original_name' => $file['original_name'],
    ]);
    exit;
}

$offset = $chunk_index * $chunk_size;
if ($offset >= $file_size) {
    http_response_code(416);
    exit('範囲外です');
}

$finfo     = new finfo(FILEINFO_MIME_TYPE);
$real_mime = $finfo->file($path);

header('Content-Type: ' . $real_mime);
header('X-Content-Type-Options: nosniff');

$fp = fopen($path, 'rb');
fseek($fp, $offset);
$data = fread($fp, $chunk_size);
fclose($fp);

echo $data;
exit;