<?php
header('Content-Type: application/json');

$rawData = file_get_contents('php://input');

// JSON文字列を連想配列にデコード
$data = json_decode($rawData, true);

// デコード失敗チェック
if (!isset($data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'])){
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$endpoint = $data['endpoint'];
$p256dh = $data['keys']['p256dh'];
$auth = $data['keys']['auth'];

// データベース登録
require_once '../db.php';
// require_once 'logincheck.php' は使わない。代わりにシンプルでAPIを邪魔しないapi_logincheck.phpを使う。
require_once '../api/api_logincheck.php';


try {

    $stmt = $pdo->prepare("SELECT EXISTS ( SELECT 1 FROM push_subscriptions WHERE user_id = ? AND endpoint = ?) AS already_exists");
    $stmt->execute([$_SESSION['user_id'],$endpoint]);
    $already = $stmt->fetchColumn();
    
    if ($already) {
        // 登録済み。更新する
        $stmt_update = $pdo->prepare("UPDATE push_subscriptions SET p256dh_key = ?, auth_key = ? WHERE user_id = ? AND endpoint = ?");
        $stmt_update->execute([$p256dh, $auth, $_SESSION['user_id'], $endpoint]);
    }else{
        // 未登録。登録する
        $stmt_insert = $pdo->prepare("INSERT INTO push_subscriptions (user_id,endpoint,p256dh_key,auth_key) VALUES (?,?,?,?)");
        $stmt_insert->execute([$_SESSION['user_id'],$endpoint,$p256dh,$auth]);
    }
    http_response_code(200);
    echo json_encode(['success' => true]);   
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

?>