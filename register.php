<?php

require_once 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];
    $pass_con = $_POST['pass_con'];

    if ($nickname === '' || $username === '' || $email === '' || $pass === '') {
        $error = 'すべての項目を入力してください。';
    } elseif ($pass === $pass_con) {
        $stmt_email = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM users WHERE email = ?)");
        $stmt_email->execute([$email]);
        $emailexist = $stmt_email->fetchColumn();
        if((int)$emailexist === 0){
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (nickname, username, email, password) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$nickname, $username, $email, $hash]);
            header('Location: login.php');
            exit;            
        }else{
            $error = "既に登録されているメールアドレスです。";
        }
    } else {
        $error = "パスワードが一致していません。";
    }
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

            <form action="register.php" method="post">
                <div class="form-group">
                    <label>ニックネーム</label>
                    <input type="text" name="nickname" placeholder="めいじろうLOVE" required value="<?php if(isset($nickname)){echo $nickname;}?>">
                </div>
                <div class="form-group">
                    <label>ユーザー名（@に続く固有のIDです。半角英数字と記号）</label>
                    <input type="text" name="username" placeholder="Meijiro_fun" required value="<?php if(isset($username)){echo $username;}?>">
                </div>
                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="email" placeholder="example@email.com" required value="<?php if(isset($email)){echo $email;}?>">
                </div>
                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="pass" placeholder="6文字以上" minlength="6" required>
                </div>
                <div class="form-group">
                    <label>パスワードの確認</label>
                    <input type="password" name="pass_con" placeholder="6文字以上"  minlength="6" required>
                </div>
                <button type="submit">アカウントを作成</button>
            </form>
            <p class="link-p">すでにアカウントをお持ちですか？ <a href="login.php">ログイン</a></p>
        </div>
    </body>
</html>