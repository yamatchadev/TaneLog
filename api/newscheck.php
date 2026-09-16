<?php
require_once __DIR__. '/../db.php';
$stmt = $pdo->prepare("SELECT created_at, title, id FROM articles ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$news = $stmt->fetch();
$recentnewsdate = mb_substr($news['created_at'], 0, 10);
$recentnews = $news['title'];
$recentnewsurl = "article/contents/".$news['id'].".php";

?>

