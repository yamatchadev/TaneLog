<?php
session_start();
$debug = "";
// すでにログイン済みだったらtimeline.phpに遷移
if(isset($_SESSION['user_id'])) {
    if(isset($_GET['to'])){
        if(isset($_GET['username'])){ //セッションにユーザーID定義
            $redirect = htmlspecialchars($_GET['to']);
            $username = htmlspecialchars($_GET['username']);
            header('Location:'.$redirect.'?'.$username);
            exit();
        }else{
            $redirect = htmlspecialchars($_GET['to']);
            header('Location:' . $redirect);
            exit();
        }
    } else {
        header('Location: timeline.php');
        exit();
    }
} elseif(isset($_COOKIE['remember_token'])) {

    require_once 'db.php';
    $token = hash('sha256',$_COOKIE['remember_token']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if($user){
        $_COOKIE['remember_token'] = array();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nickname'] = $user['nickname'];
        $newtoken = bin2hex(random_bytes(32));
        $_COOKIE['remember_token'] = $newtoken;
        $stmt = $pdo->prepare("UPDATE users SET remember_token = $newtoken WHERE user_id = ?");
        $stmt->execute($user['user_id']);
        if($stmt){
            echo "うまくいきました";
            exit();
            header('Location: timeline.php');            
        }else{
            $error = "DBへのトークン登録に失敗しました。";
            $debug = "DBのUPDATE操作に失敗しました。";
        }

    }elseif($_SERVER['REQUEST_METHOD'] === 'POST'){

        $debug = "クッキー上のトークンがデータベースにありません。";
        $email = $_POST['email'];
        $pass = $_POST['pass'];

        // メールアドレスでユーザーを検索
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        // ユーザーが存在して、パスワードが一致するか確認
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nickname'] = $user['nickname'];
            // Cookie設定
            $auto_login_token = bin2hex(random_bytes(32));
            $auto_login_token_hashed = hash('sha256', $auto_login_token);
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$auto_login_token_hashed, $user['id']]);
            setcookie('remember_token', $auto_login_token, [
                'expires'  => time() + (60*60*24*30),
                'path'     => '/',
                'secure'   => true,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);

            if(isset($_GET['to'])){
                $redirect = htmlspecialchars($_GET['to']);
                if(isset($_GET['username'])){
                    $username = htmlspecialchars($_GET['username']);
                    header('Location:'.$redirect.'?username='.$username);
                    exit();
                }else{
                    header('Location:' . $redirect);
                    exit();
                }
            } else {
                header('Location: timeline.php');
                exit();
            }
        } else {
            $error = 'メールアドレスまたはパスワードが違います。';
            $debug = "DBと照合した結果、入力されたメアドとパスワードの組み合わせが見つかりませんでした。";
        }        
    }else{
        $debug = "クッキー上のトークンがデータベースにありません。";
    }
}elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
    $debug = "クッキーがありません。";
        require_once 'db.php';
        $email = $_POST['email'];
        $pass = $_POST['pass'];

        // メールアドレスでユーザーを検索
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        // ユーザーが存在して、パスワードが一致するか確認
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nickname'] = $user['nickname'];
            // Cookie設定
            $auto_login_token = bin2hex(random_bytes(32));
            $auto_login_token_hashed = hash('sha256', $auto_login_token);
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$auto_login_token_hashed, $user['id']]);
            setcookie('remember_token', $auto_login_token, [
                'expires'  => time() + (60*60*24*30),
                'path'     => '/',
                'secure'   => true,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);

            if(isset($_GET['to'])){
                if(isset($_GET['username'])){
                    $redirect = htmlspecialchars($_GET['to']);
                    $username = htmlspecialchars($_GET['username']);
                    header('Location:'.$redirect.'?username='.$username);
                    exit();
                }else{
                    $redirect = htmlspecialchars($_GET['to']);
                    header('Location:' . $redirect);
                    exit();
                }
            } else {
                header('Location: timeline.php');
                exit();
            }
        } else {
            $error = 'メールアドレスまたはパスワードが違います。';
        }        
    } else{
        $debug = "ポストなしクッキーなし";
    }

// ランダム背景を決める
$dirPath = './background/*';
$files = glob($dirPath);
if($files !== false && count($files) > 0) {
    $randomIndex = array_rand($files);
    $randombackground = $files[$randomIndex];
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ログイン - TaneLog</title>
        <meta name="description" content="TaneLogはPHP学習者向けのソーシャルアプリです。ログインして投稿・交流を始めましょう。">
        <style>
            /* テーマ共通定義 */
            body {
                --primary-color: #4F5D95;
                --bg-color: #f4f6f9;
                --card-bg: #ffffff;
                --text-color: #333333;
                --border-color: #e2e8f0;
                --input-bg: #ffffff;
            }


            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: var(--bg-color);
                color: var(--text-color);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;  
                backdrop-filter: blur(5px);      
                background-image: url('<?= $randombackground ?>');
                background-size: cover;
                background-position: center;

            }
            .login-card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0px 0px 30px 0px rgba(0, 0, 0, 0.32);
                width: 100%;
                max-width: 400px;
                box-sizing: border-box;
                border: 1px solid var(--border-color);
                margin: 15px;

            }
            h1.logo {
                text-align: center;
                color: var(--primary-color);
                margin-top: 0;
                margin-bottom: 20px;
                font-size: 28px;
            }
            h2 {
                font-size: 18px;
                margin-bottom: 20px;
                color: var(--text-color);
                opacity: 0.9;
            }
            .form-group {
                margin-bottom: 15px;
            }
            label {
                display: block;
                margin-bottom: 6px;
                font-size: 14px;
                font-weight: bold;
                color: var(--text-color);
                opacity: 0.8;
            }
            input {
                width: 100%;
                padding: 10px 12px;
                background-color: var(--input-bg);
                border: 1px solid var(--border-color);
                color: var(--text-color);
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
                opacity: 0.9;
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
            body.dark-theme .error-msg {
                background-color: #2d2222;
                color: #fc8181;
                border-color: #742a2a;
            }
            .link-p {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
                color: var(--text-color);
                opacity: 0.7;
            }
            .link-p a {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h1 class="logo">TaneLog</h1>
            <h2>ログイン</h2>
            
            <?php if (isset($error)): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="<?php
                if (isset($_GET['to'])) {
                    if (isset($_GET['username'])) {
                        $url = "?to=" . htmlspecialchars($_GET['to']) . "&username=" . htmlspecialchars($_GET['username']);
                    } else {
                        $url = "?to=" . htmlspecialchars($_GET['to']);
                    }
                    echo "login.php" . $url;
                } else {
                    echo "login.php";
                }
            ?>" method="post">
                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="email" placeholder="example@email.com" required value="<?php if(isset($email)) {echo htmlspecialchars($email);} ?>">
                </div>
                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="pass" placeholder="••••••••" required>
                </div>
                <button type="submit">ログイン</button>
            </form>
            <p class="link-p">または <a href="register.php">新規登録</a></p>
            <p><?= $debug; ?></p>
            <p><?php if(isset($_COOKIE['remember_token'])){ $_COOKIE['remember_token'];} ?></p>
        </div>
    </body>
</html>