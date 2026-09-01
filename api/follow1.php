<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'logincheck.php';
require_once 'db.php';

// レスポンスをJSON形式に統一


// POSTデータが存在し、かつ自分自身でないか確認（(int)で数値型にキャストして比較）
if (isset($_POST['target_user_id']) && (int)$_SESSION['user_id'] !== (int)$_POST['target_user_id']) {
    try {
        $follower_id = (int)$_SESSION['user_id'];
        $followed_id = (int)$_POST['target_user_id'];

        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $result = $stmt->execute([$follower_id, $followed_id]);

        if ($result) {
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit();
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Execute failed']);
            exit();
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
} else {
    // リクエストパラメータが不正、または自分自身をフォローしようとした場合
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters or same user']);
    exit();
}
?>