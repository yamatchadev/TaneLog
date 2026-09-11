<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    if(!isset($_COOKIE['remember_token'])){ //remember_tokenがないとき
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }else{ // remember_tokenがあるとき
        // echo "remember_tokenがクッキー上にあります。";
        require_once __DIR__ . '/../db.php';
        $token = hash('sha256',$_COOKIE['remember_token']);
        try{
            $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);            
        }catch(PDOException $e){
            header('Location:'.__DIR__.'/../dberror.php');
        }

        if(!$user){ // DBにない
            //echo "クッキーのトークンがDBと一致しません。";
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /login.php');
            exit;
        }else{
            // DBにある
            // echo "トークン照会ができました！";
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nickname'] = $user['nickname'];
            exit;
        }
    }   
}


?>