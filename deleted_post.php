<?php
//自分の削除済みの投稿一覧ページ
require_once __DIR__.'/api/logincheck.php';
require_once __DIR__.'/db.php/';

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$post
?>