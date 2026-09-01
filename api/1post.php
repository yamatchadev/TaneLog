<?php
require_once '../api/logincheck.php';
require_once '../db.php';
require_once 'notify_function.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit;
}

$content = trim($_POST['content'] ?? '');
if ($content === '') {
    echo json_encode(['ok' => false, 'error' => 'content is empty']);
    exit;
}

$content_clean = htmlspecialchars($content);
$content_id = '';
for ($i = 0; $i < 15; $i++) {
    $content_id .= random_int(0, 9);
}
try{
    $stmt = $pdo->prepare(
        "INSERT INTO posts (user_id, content, content_id) VALUES (?, ?, ?)"
    );
    $stmt->execute([$_SESSION['user_id'], $content_clean, $content_id]);

}catch(PDOException $e){
    echo json_encode(['ok' => false, 'error' => 'Database error']);
    exit();
}
try{
    // フォロワーのuser_idを取得
    $stmt = $pdo->prepare("SELECT follower_id FROM follows WHERE followed_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $followerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // 通知送信（投稿者自身のニックネームを本文に含める）
    sendPushNotification(
        $pdo,
        $followerIds,
        'TaneLog',
        $_SESSION['nickname'] . 'さんが新しい投稿をしました',
        '/detail.php?contentid='.$content_id
    );    
}catch(PDOException $e){
    error_log('Push notification failed:'.$e->getMessage());
}


echo json_encode(['ok' => true, 'post_id' => (int)$pdo->lastInsertId()]);