<?php
require_once '../api/logincheck.php';
require_once '../db.php';
if(isset($_POST['content']) && isset($_POST['parent_id'])){
    $content_clean = htmlspecialchars($_POST['content']);
    $content_id = '';
    for ($i = 0; $i < 15; $i++) {
        $content_id .= random_int(0, 9);
    }
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, content_id, parent_id) VALUES (?,?,?,?)");
    $stmt->execute([$_SESSION['user_id'],$content_clean,$content_id,$_POST['parent_id']]);

    echo("投稿に対して、リプライ「".$content_clean."」をcontent_id:".$content_id."で記録しました。");
}
$referer = $_SERVER['HTTP_REFERER'] ?? '';
header("Location: ".$referer."&post=1");
?>
