<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/secret.php';
$error = "";

if(isset($_GET['token'])){
    $token = htmlspecialchars($_GET['token']);
    $stmt = $pdo->prepare("SELECT * FROM pre_users WHERE token = ?");
    $stmt->execute([$token]);
    $valid_token = $stmt->fetch(PDO::FETCH_ASSOC);
    if($valid_token){
        if(isset($_POST['verify_code'])){
            $verify_code = htmlspecialchars($_POST['verify_code']);
            $stmt = $pdo->prepare("SELECT * FROM pre_users WHERE token = ? AND created_at > now - interval 24 hour");
            $stmt->execute([$token]);
            $verified_user = $stmt->fetch(PDO::FETCH_ASSOC);
            if($verified_user == true && $_POST['verify_code'] == $verified_user['verify_code']){
                $_SESSION['register_token'] = $verified_user['token'];
                header('Location: register.php');
            }else{
                $error = "認証コードが違うか、有効期限が切れています。";
            }        
        }else{
        }        
    }else{
        $error = "トークンが無効です。";
    }
}else{
    $_SESSION['error'] = "無効なリクエストです。";
    header('Location: register.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>新規登録 - TaneLog</title>
        <style>

            :root {
                --primary-color: #6A8B65;
                --primary-dark: #546d50;
                --bg-color: #f4f6f9;
                --card-bg: #ffffff;
                --text-color: #333333;
                --text-muted: #6b7280;
                --border-color: #e2e8f0;
                --input-bg: #ffffff;
                --shadow-soft: 0 10px 30px rgba(79, 93, 149, 0.12);
            }
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: var(--bg-color);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .register-card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
                width: 100%;
                max-width: 400px;
                box-sizing: border-box;
                border: 1px solid var(--border-color);
                margin: 15px;
            }
            .register-card-header {
                display: flex;
                justify-content: center; /* 水平方向の中央揃え */
                align-items: center;     /* 垂直方向の中央揃え */
                height: 50px;           /* 必要に応じて高さを指定 */

            }
            .logo {
                width: 200px;
                color: var(--primary-color);
                margin: auto;
                font-size: 28px;
            }
            h2 {
                font-size: 18px;
                margin-top: 28px;
                margin-bottom: 20px;
                color: #4a5568;
            }
            .form-group {
                margin-bottom: 15px;
            }
            label {
                display: block;
                margin-bottom: 6px;
                font-size: 14px;
                font-weight: bold;
                color: #4a5568;
            }
            input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                box-sizing: border-box;
                font-size: 15px;
            }
            input:focus {
                outline: none;
                border-color: var(--primary-color);
            }
            button {
                width: 100%;
                background-color: var(--primary-color);
                color: white;
                border: none;
                padding: 12px;
                border-radius: 6px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                margin-top: 10px;
            }
            button:hover {
                background-color: var(--primary-dark);
            }
            .error-msg {
                background-color: #fff5f5;
                color: #c53030;
                border: 1px solid #fed7d7;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 15px;
                font-size: 14px;
            }
            .link-p {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
                color: #718096;
            }
            .link-p a {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="register-card">
            <div class="register-card-header"><img src="img/tanelog_login.png" alt="TaneLog Logo" class="logo">
            </div>
            <h2>新規アカウント登録</h2>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="verify_email.php" method="post">
                <div class="form-group">
                    <label>認証コード</label>
                    <input type="text" name="verify_code" required>
                </div>
                <button type="submit">認証</button>
            </form>
            <p class="link-p">すでにアカウントをお持ちですか？ <a href="login.php">ログイン</a></p>
        </div>
    </body>
</html>