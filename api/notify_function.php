<?php
function sendPushNotification(PDO $pdo, array $targetUserIds, string $title, string $body, string $url = '/'): void
{
    if (empty($targetUserIds)) {
        return;
    }

    $payload = json_encode([
        'target_user_ids' => $targetUserIds,
        'title' => $title,
        'body' => $body,
        'url' => $url,
    ], JSON_UNESCAPED_UNICODE);

    // JSONを一時ファイルに書き出す(クォートのエスケープ問題を回避するため)
    $tmpFile = __DIR__ . '/tmp_notify_' . uniqid() . '.json';
    file_put_contents($tmpFile, $payload);

    $phpPath = 'C:\\xampp\\php\\php.exe';
    $scriptPath = __DIR__ . '\\send_notification_cli.php';

    $cmd = 'start /B "" ' . escapeshellarg($phpPath) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($tmpFile);

    pclose(popen($cmd, 'r'));
}