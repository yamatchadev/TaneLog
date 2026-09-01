<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/notify_function_core.php';

$logFile = __DIR__ . '/notify_cli.log';

function writeLog(string $logFile, string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

if ($argc < 2) {
    writeLog($logFile, 'エラー: 引数が渡されていません');
    exit(1);
}

$tmpFile = $argv[1];

if (!file_exists($tmpFile)) {
    writeLog($logFile, 'エラー: 一時ファイルが見つかりません: ' . $tmpFile);
    exit(1);
}

$rawJson = file_get_contents($tmpFile);
$payload = json_decode($rawJson, true);

// 読み込み後、一時ファイルを削除
unlink($tmpFile);

if (!$payload) {
    writeLog($logFile, 'エラー: JSONのデコードに失敗しました。内容=' . $rawJson);
    exit(1);
}

writeLog($logFile, '通知送信処理を開始: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

require_once __DIR__ . '/../db.php';

try {
    sendPushNotificationCore(
        $pdo,
        $payload['target_user_ids'],
        $payload['title'],
        $payload['body'],
        $payload['url'] ?? '/'
    );
    writeLog($logFile, '通知送信処理が正常に完了しました');
} catch (\Throwable $e) {
    writeLog($logFile, 'エラー発生: ' . $e->getMessage());
    writeLog($logFile, 'スタックトレース: ' . $e->getTraceAsString());
}