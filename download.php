<?php
require_once 'db.php';

$id          = filter_input(INPUT_GET, 'id',    FILTER_VALIDATE_INT);

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

$finfo     = new finfo(FILEINFO_MIME_TYPE);
$real_mime = $finfo->file($path);

while (ob_get_level()) { ob_end_clean(); };
ini_set('zlib.output_compression', 'Off');
set_time_limit(0);
session_write_close();

header('Content-Type: ' . $real_mime);
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['original_name']));
header('Content-Length:' . $file_size);
header('X-Content-Type-Options: nosniff');

$fp = fopen($path, 'rb');
while (!feof($fp)){
    if(connection_aborted()){
        break;
    }
    $part = fread($fp, 1024*1024);
    echo $part;
    flush();
}
fclose($fp);
exit;