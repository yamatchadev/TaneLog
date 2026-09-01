<?php
require_once '../api/logincheck.php';
require_once '../db.php';

header('Content-Type: application/json');

$since_id = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

$stmt = $pdo->prepare(
    "SELECT posts.*, users.nickname, users.icon_path, users.username 
     FROM posts 
     JOIN users ON posts.user_id = users.id 
     WHERE posts.id > ? AND posts.parent_id IS NULL AND posts.deleted = 0
     ORDER BY posts.created_at DESC"
);
$stmt->execute([$since_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($posts);
?>