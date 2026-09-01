<?php
require_once '../api/logincheck.php';
require_once '../db.php';
if(isset($_POST['content_id'])){
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE content_id = ?");
    $stmt->execute([$_POST['content_id']]);
    $post_id = $stmt->fetchColumn();
    
    if(isset($post_id)){
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        $existlikes = $stmt->fetchColumn();

        if($existlikes == 0){            
            
            //いいねしてなかったら登録
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'],$post_id]);
            $status = "liked";
        }else{
            //いいね済みだったら消す
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$_SESSION['user_id'], $post_id]);
            $status = "not";
        }

        //いいね数数えます
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $likes_count = $stmt->fetchColumn();
        header("Content-Type: application/json");
        echo json_encode(["status" => $status, "count" => $likes_count]);
    }
}

?>