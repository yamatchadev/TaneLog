<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../secret.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function sendPushNotificationCore(PDO $pdo, array $targetUserIds, string $title, string $body, string $url = '/'): void
{
    $logFile = __DIR__ . '/notify_cli.log';
    $log = fn($msg) => file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);

    if (empty($targetUserIds)) {
        $log('対象ユーザーIDが空です');
        return;
    }

    $webPush = new WebPush([
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ]);

    $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
    $sql = "SELECT id, endpoint, p256dh_key, auth_key FROM push_subscriptions WHERE user_id IN ($placeholders)";
    $log('実行するSQL: ' . $sql);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($targetUserIds);
    $subscriptions = $stmt->fetchAll();

    $log('取得した購読数: ' . count($subscriptions));

    foreach ($subscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh_key'],
            'authToken' => $sub['auth_key'],
        ]);

        $webPush->queueNotification(
            $subscription,
            json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
            ])
        );
    }

    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getEndpoint();
        if ($report->isSuccess()) {
            $log('送信成功: ' . $endpoint);
        } else {
            $log('送信失敗: ' . $endpoint . ' 理由: ' . $report->getReason());
            $deleteStmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
            $deleteStmt->execute([$endpoint]);
        }
    }
}