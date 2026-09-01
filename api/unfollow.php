<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログイン確認
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '未ログイン状態です']);
    exit();
}

try {
    // DB接続ファイルの読み込み
    require_once '../db.php';

    // POSTデータの存在チェック
    if (!isset($_POST['target_user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'target_user_id が送られていません']);
        exit();
    }

    $follower_id = (int)$_SESSION['user_id'];
    $followed_id = (int)$_POST['target_user_id'];

    if ($follower_id === $followed_id) {
        http_response_code(400);
        echo json_encode(['error' => '自分自身はフォローできません']);
        exit();
    }

    // フォロー解除処理
    $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->execute([$follower_id, $followed_id]);

    echo json_encode(['status' => 'success', 'message' => 'フォロー解除しました']);

} catch (Throwable $e) {
    // db.phpの読み込み失敗やSQLエラー等の詳細メッセージを出力
    http_response_code(500);
    echo json_encode(['error' => '内部エラー: ' . $e->getMessage()]);
}
?>