<?php
session_start();
$debug = "";
// すでにログイン済みだったらtimeline.phpに遷移
if(isset($_SESSION['user_id'])) {
    if(isset($_SESSION['redirect_after_login'])){
        header('Location:'.$_SESSION['redirect_after_login']);
        exit;
    }else{
        header('Location: timeline.php');
        exit;
    }
} elseif(isset($_COOKIE['remember_token'])) {

    require_once __DIR__.'/db.php';
    $token = hash('sha256',$_COOKIE['remember_token']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user){
        $_COOKIE['remember_token'] = array();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nickname'] = $user['nickname'];
        $newtoken = bin2hex(random_bytes(32));
        $_COOKIE['remember_token'] = $newtoken;
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$newtoken,$user['id']]);
        if($stmt){
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
            $stmt->fetch(PDO::FETCH_ASSOC);
            if($stmt){
                setcookie('remember_token', $auto_login_token, [
                    'expires'  => time() + (60*60*24*30),
                    'path'     => '/',
                    'secure'   => true,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);                
            }else{
                header('dberror.php');
            }

            if(isset($_SESSION['redirect_after_login'])){
                header('Location:'.$_SESSION['redirect_after_login']);
                exit;
            }else{
                header('Location: timeline.php');
                exit;
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

            if(isset($_SESSION['redirect_after_login'])){
                header('Location:'.$_SESSION['redirect_after_login']);
                exit;
            }else{
                header('Location: timeline.php');
                exit;
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
        <title>TaneLog - 学ぶ人のための投稿・交流アプリ</title>
        <meta name="description" content="TaneLogはPHPを学ぶ人のための投稿・交流アプリです。学習ログをタイムラインに投稿したり、記事を書いて知識をストックしたり、いいね・リプライで他の学習者とつながれます。">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#fef8e5">
        <link rel="apple-touch-icon" href="/icon/icon-192.png">
        <script type="application/ld+json">
        {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "タネログ",
        "alternateName": ["たねろぐ", "TaneLog", "Tanelog"],
        "url": "https://tanelog.yamatcha.net"
        }
        </script>
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js');
            });
        }
        </script>
        
        <style>
            /* ==== テーマ共通定義 ==== */
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

            * { box-sizing: border-box; }

            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background-color: var(--bg-color);
                color: var(--text-color);
            }

            a { color: var(--primary-color); }

            /* ==== ヒーローセクション(背景写真 + ログインカード) ==== */
            .hero {
                position: relative;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 40px 20px;
                background-image: url('<?= $randombackground ?>');
                background-size: cover;
                background-position: center;
            }
            .hero::before {
                content: "";
                position: absolute;
                inset: 0;
                background: rgba(20, 24, 40, 0.45);
                backdrop-filter: blur(3px);
            }
            .hero-inner {
                position: relative;
                z-index: 1;
                display: flex;
                flex-wrap: wrap;
                gap: 40px;
                align-items: center;
                justify-content: center;
                max-width: 900px;
                width: 100%;
            }

            /* 紹介文パネル */
            .intro {
                flex: 1 1 340px;
                color: #ffffff;
                padding: 10px;
            }
            .intro .eyebrow {
                display: inline-block;
                font-size: 13px;
                font-weight: bold;
                letter-spacing: 0.05em;
                background: rgba(255, 255, 255, 0.15);
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 999px;
                padding: 4px 12px;
                margin-bottom: 16px;
            }
            .intro h1 {
                font-size: 40px;
                line-height: 1.2;
                margin: 0 0 16px;
            }
            .intro p {
                font-size: 16px;
                line-height: 1.7;
                color: rgba(255, 255, 255, 0.9);
                margin: 0 0 24px;
                max-width: 440px;
            }
            .intro-links a {
                color: #ffffff;
                text-decoration: underline;
                font-size: 14px;
            }

            /* ログインカード(既存デザインを踏襲) */
            .login-card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0px 0px 30px 0px rgba(0, 0, 0, 0.32);
                width: 100%;
                max-width: 400px;
                flex: 0 1 400px;
                border: 1px solid var(--border-color);
            }
            .login-card-header {
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
            .login-card h2 {
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
                color: var(--text-color);
                opacity: 0.7;
            }
            .link-p a {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: bold;
            }

            /* ==== 機能紹介セクション ==== */
            .features {
                max-width: 1000px;
                margin: 0 auto;
                padding: 70px 20px;
            }
            .features h2.section-title {
                text-align: center;
                font-size: 26px;
                color: var(--text-color);
                margin-bottom: 8px;
            }
            .features p.section-sub {
                text-align: center;
                color: var(--text-muted);
                margin-bottom: 40px;
                font-size: 15px;
            }
            .feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 24px;
            }
            .feature-card {
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 24px;
                box-shadow: var(--shadow-soft);
            }
            .feature-card .icon {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                background: rgba(79, 93, 149, 0.1);
                color: var(--primary-color);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 16px;
            }
            .feature-card h3 {
                font-size: 17px;
                margin: 0 0 8px;
                color: var(--text-color);
            }
            .feature-card p {
                font-size: 14px;
                line-height: 1.6;
                color: var(--text-muted);
                margin: 0;
            }

            /* ==== フッター ==== */
            footer {
                text-align: center;
                padding: 30px 20px;
                font-size: 13px;
                color: var(--text-muted);
                border-top: 1px solid var(--border-color);
            }
            footer a {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: bold;
            }

            @media (max-width: 640px) {
                .intro h1 { font-size: 30px; }
                .hero-inner { gap: 30px; }
            }
        </style>
    </head>
    <body>

        <section class="hero">
            <div class="hero-inner">
                <div class="intro">
                    <span class="eyebrow">プログラミング学習者向けSNS</span>
                    <h1>TaneLog</h1>
                    <p>
                        学んだことをタイムラインに投稿し、記事にまとめてストックし、
                        いいね・リプライで他の学習者とつながる。
                        学ぶ人のための、小さなコミュニティです。
                    </p>
                    <p class="intro-links">
                        <a href="#features">できることを見る ↓</a>
                    </p>
                </div>

                <div class="login-card">
                    <div class="login-card-header"><img src="img/tanelog_login.png" alt="TaneLog Logo" class="logo">
                </div>
                    
                    <h2>ログイン</h2>

                    <?php if (isset($error)): ?>
                        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['redirect_after_login'])):?>
                        <div class="error-msg">このページにアクセスするにはログインしてください。</div>
                    <?php endif; ?>

                    <form action="index.php" method="post">
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
                    <p class="link-p">または <a href="send_email.php">新規登録</a></p>
                </div>
            </div>
        </section>

        <section class="features" id="features">
            <h2 class="section-title">TaneLogでできること</h2>
            <p class="section-sub">学習・発信・交流をひとつの場所で</p>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="icon">投</div>
                    <h3>学習ログを投稿</h3>
                    <p>今日学んだ知識や気づきを、タイムラインに気軽に投稿できます。</p>
                </div>
                <div class="feature-card">
                    <div class="icon">記</div>
                    <h3>記事を書いて残す</h3>
                    <p>まとまった知識は記事として執筆し、あとから読み返せる形でストックできます。</p>
                </div>
                <div class="feature-card">
                    <div class="icon">交</div>
                    <h3>いいね・リプライで交流</h3>
                    <p>他の学習者の投稿にいいねやリプライを送り、励まし合いながら学べます。</p>
                </div>
            </div>
        </section>

        <footer>
            &copy; <?= date('Y') ?> TaneLog &middot; <a href="https://yamatcha.net/">プロジェクト一覧</a>
        </footer>

    </body>
</html>