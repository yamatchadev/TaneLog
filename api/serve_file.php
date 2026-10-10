<?php
require_once '../db.php';
require_once '../api/logincheck.php';
if(isset($_GET['id'])){
    $stmt=$pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $att=$stmt->fetch();
    if($att){
        header('Content-Type: ' . $att['mime_type']);
        if(file_exists('E:/learnphp_uploads/attachments/'.$att['stored_name'])){
            readfile('E:/learnphp_uploads/attachments/'.$att['stored_name']);
        }else{
            header('Content-Type: image/jpeg');
            readfile('E:/learnphp_uploads/imgs/notfound.jpg'); 
        }
    }else{
        header('Content-Type: image/jpeg');
        readfile('E:/learnphp_uploads/imgs/notfound.jpg');
    }

}else{
    echo("不正なリクエストです。");
}

?>