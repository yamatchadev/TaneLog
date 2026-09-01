<?php
require_once 'api/logincheck.php';
require_once 'db.php';

header('Content-Type: application/json');

// パラメータ受け取り
$upload_id    = $_POST['upload_id']    ?? '';
$chunk_index  = $_POST['chunk_index']  ?? null;
$total_chunks = $_POST['total_chunks'] ?? null;
$original_name = $_POST['original_name'] ?? '';
$post_id      = $_POST['post_id']      ?? null;

// バリデーション
if (!$upload_id || $chunk_index === null || !$total_chunks || !$original_name || !$post_id) {
    echo json_encode(['ok' => false, 'error' => 'パラメータ不足']);
    exit;
}

$upload_id    = preg_replace('/[^a-f0-9]/', '', $upload_id); // 英数字のみ許可
$chunk_index  = (int)$chunk_index;
$total_chunks = (int)$total_chunks;
$post_id      = (int)$post_id;

// チャンク一時保存先
$tmp_dir = 'E:/learnphp_uploads/tmp/' . $upload_id . '/';
if (!is_dir($tmp_dir)) {
    mkdir($tmp_dir, 0755, true);
}

// チャンクを保存
$chunk_path = $tmp_dir . $chunk_index . '.part';
move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_path);

// 全チャンク揃ったか確認
$parts = glob($tmp_dir . '*.part');
if (count($parts) < $total_chunks) {
    echo json_encode(['ok' => true, 'done' => false]);
    exit;
}

// 全チャンク結合
$safe_original = basename($original_name);
$stored_name   = uniqid() . '_' . $safe_original;
$final_path    = 'E:/learnphp_uploads/attachments/' . $stored_name;

$out = fopen($final_path, 'wb');
for ($i = 0; $i < $total_chunks; $i++) {
    $part = $tmp_dir . $i . '.part';
    fwrite($out, file_get_contents($tmp_dir . $i . '.part'));
    unlink($part);
}
fclose($out);
rmdir($tmp_dir);

// MIMEを実ファイルから取得
$finfo = new finfo(FILEINFO_MIME_TYPE);
$real_mime = $finfo->file($final_path);

// DBに登録
$stmt = $pdo->prepare(
    "INSERT INTO attachments (post_id, original_name, stored_name, mime_type, file_size)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$post_id, $safe_original, $stored_name, $real_mime, filesize($final_path)]);

echo json_encode(['ok' => true, 'done' => true]);