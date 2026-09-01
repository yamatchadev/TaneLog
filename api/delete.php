<?php
require_once __DIR__.'/logincheck.php';
require_once __DIR__.'/../db.php';
if($_GET['contentid']){
    if(isset($_SESSION['user_id'])){
        $contentid = htmlspecialchars($_GET['contentid']);
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE content_id = ?");
        $stmt->execute([$contentid]);
        $content_user_id = $stmt->fetch();
        $content_user_id = (int)$content_user_id['user_id'];
        
        $user_id = htmlspecialchars($_SESSION['user_id']);
        $user_id = (int)$user_id;
        if($content_user_id === $user_id){
            $stmt=$pdo->prepare("UPDATE posts SET deleted = 1 WHERE content_id = ?");
            $stmt->execute([$contentid]);
            header('Location: ../detail.php?contentid='.$contentid);
            exit();
        }else{
            $contentid = htmlspecialchars($_GET['contentid']);
            header('Location: ../detail.php?contentid='.$contentid);
        }
    }else{
        $error = "投稿を削除するには、ログインしてください。";
    }
}else{
    header('Location: timeline.php');
}

?>