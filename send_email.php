<?php
require_once __DIR__.'/db.php';
if(isset($_POST['email'])){

    $code = sprintf('%06d', random_int(0, 999999));
    $token = bin2hex(random_bytes(16));
    $email = $_POST['email'];
    
    $exist=$pdo->prepare("SELECT EXISTS (SELECT * FROM users WHERE email = ?)");
    $exist->execute([$email]);
    $exist=$exist->fetchColumn();
    if($exist){
        $error = "そのメールアドレスは既に登録されています。";
    }else{
        $stmt=$pdo->prepare("INSERT INTO pre_users (email,verify_code,token) VALUE (?,?,?)");
        $stmt->execute([$email,$code,$token]);

        require_once 'gmail.php';

        $subject = "【TaneLog】メールアドレス認証";
        $body = <<<EOT
            コード認証ページに以下のコードを入力し、メールアドレスを認証してください。
            認証コード：{$code}
            ※認証コードの有効期限は２４時間です。
            もしこのメールに心当たりがない場合は、このメールを無視してください。
            認証コードは他人に絶対に教えないでください。あなたのメールアドレスを使って第三者がこのサービス（TaneLog）に登録できてしまいます。
            EOT;

        sendGmail($email, $subject, $body);
        header("Location:".$_SERVER['REQUEST_URI']. "/../verify_email.php?token=".$token);   
        }


}
?>
<html>
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
            .info-msg {
                background-color: #f6fff4;
                color: #1bac2a;
                border: 1px solid #d7fedd;
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
            <div class="register-card-header"><img src="img/tanelog_login.png" alt="TaneLog Logo" class="logo"></div>
            <h2>新規登録：メールアドレス認証</h2>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($info)):?>
                <div class="info-msg"><?= $info ?></div>
            <?php endif;?>
            <form action="send_email.php" method="post">
                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="email" placeholder="example@email.com" required value="<?php if(isset($email)){echo $email;}?>">
                </div>
                <button type="submit">送信</button>
            </form>
            <p class="link-p">すでにアカウントをお持ちですか？ <a href="login.php">ログイン</a></p>
        </div>
    </body>

</html>
