<?php
if($_SERVER['REQUEST_METHOD'] === "POST"){
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if($data === null){
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    $articleId = $data['article_id'] ?? null;
    $blocks = $data['blocks'] ?? [];
    
}
?>