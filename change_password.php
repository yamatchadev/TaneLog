<?php
$page = basename(__FILE__);
require_once 'api/newscheck.php';
require_once 'db.php';
require_once 'api/logincheck.php';
$error = "";
$info = "";
if(isset($_SESSION['user_id'])){

    $user_id = $_SESSION['user_id'];

    if($_SERVER['REQUEST_METHOD'] === "POST"){
        $pass = $_POST['pass'];
        $pass_new = $_POST['pass_new'];
        $pass_new_con = $_POST['pass_new_con'];
        //現在のユーザー情報取得
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])){
            if($pass_new === $pass_new_con){
                $pass_new_hash = password_hash($pass_new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$pass_new_hash, $user_id]);
                $info = "パスワードが変更されました。";                
            }else{
                $error = "新しいパスワードが一致しません。";
            }
        }else{
            $error = "現在のパスワードが違います。";
        }
    } 
}else{
    header('Location: login.php?to='.$page);
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <script src="js/theme.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>パスワード変更 - TaneLog</title>
        
        <style>
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: var(--bg-color);
                color: var(--text-color);
            }
            header {
                display: flex;
                flex-direction: column;
                gap: 8px;
                justify-content: space-between;
                align-items: center;
                padding: 12px 20px;
                background-color: var(--card-bg);
                border-bottom: 1px solid var(--border-color);
                position: sticky;
                top: 0;
                z-index: 50;
                transition: background-color 0.3s, border-color 0.3s;
            }
            .header-main-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
            header img {
                margin-top: 5px;
                height: 45px;
            }
            .header-actions {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .theme-toggle-btn {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                padding: 4px;
                line-height: 1;
                user-select: none;
            }
            .theme-toggle-btn:hover {
                transform: scale(1.1);
            }
            .container {
                max-width: 500px; /* フォーム用に入力しやすいよう少し狭めに設定 */
                margin: 0 auto;
                padding: 4px 15px;
            }
            .card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-top: 30px;
                border: 1px solid var(--border-color);
            }
            .card h2 {
                margin-top: 0;
                margin-bottom: 20px;
                font-size: 18px;
                color: var(--primary-color);
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 10px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 6px;
                color: var(--text-color);
            }
            input[type="password"] {
                width: 100%;
                background-color: var(--card-bg);
                color: var(--text-color);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 10px 12px;
                box-sizing: border-box;
                font-size: 15px;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: var(--primary-color);
            }
            .button_parent {
                text-align: right;
                margin-top: 20px;
            }
            button {
                background-color: var(--button-color);
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 6px;
                font-size: 15px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.2s;
            }
            button:hover {
                background-color: #3b4671;
            }
            /* メッセージスタイル */
            .msg {
                padding: 10px 12px;
                border-radius: 6px;
                font-size: 14px;
                margin-bottom: 15px;
                font-weight: bold;
            }
            .error-msg {
                background-color: #fff5f5;
                color: #e53e3e;
                border: 1px solid #fed7d7;
            }
            .info-msg {
                background-color: #f0fff4;
                color: #38a169;
                border: 1px solid #c6f6d5;
            }
            /* サイドメニュー */
            .menu-btn {
                width: 30px;
                height: 24px;
                background: none;
                border: none;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 0;
            }
            .menu-btn span {
                display: block;
                width: 100%;
                height: 3px;
                background-color: var(--text-color);
                border-radius: 2px;
            }
            .side-menu {
                position: fixed;
                top: 0;
                right: -300px;
                width: 260px;
                height: 100%;
                background-color: #2d3748;
                transition: right 0.3s ease;
                z-index: 99;
                box-shadow: -4px 0 10px rgba(0,0,0,0.1);
            }
            .side-menu.active {
                right: 0;
            }
            .menu-close-wrapper {
                display: flex;
                justify-content: flex-end;
                padding: 15px 20px;
            }
            .close-btn {
                background: none;
                border: none;
                color: #a0aec0;
                font-size: 28px;
                cursor: pointer;
                line-height: 1;
                padding: 0;
            }
            .close-btn:hover {
                color: #fff;
            }
            .side-menu ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .side-menu ul li a {
                display: block;
                padding: 16px 24px;
                color: #e2e8f0;
                text-decoration: none;
                font-size: 16px;
                border-bottom: 1px solid #4a5568;
            }
            .side-menu ul li a:hover {
                background-color: #4a5568;
                color: #fff;
            }
            .side-menu ul li.danger a {
                color: #feb2b2;
            }
            .side-menu ul li.danger a:hover {
                background-color: #9b2c2c;
                color: #fff;
            }
            header h1 {
	            font-weight: normal;
	            margin: 0; padding: 0;
	            font-size: 0.8rem;
	            letter-spacing: 0.1em;
            }
	        @media screen and (max-width:700px) {
                header h1 { font-size: 0.7em; }
            }
        </style>
    </head>
    <body>

        <header>
            <div id="header-top">
                <h1><?= $recentnewsdate; ?> <?= $recentnews; ?><a href="./news.php">詳細</a></h1>
            </div>
            <div class="header-main-row">
                <div>
                    <a href="timeline.php"><img src="img/tanelog.png" alt="TaneLog" id="headerLogo"></a>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="テーマ切り替え">🌙</button>
                    <button class="menu-btn" id="menuBtn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </header>

        <?php require 'sidemenu.php'; ?>

        <main class="container">
            <div class="card">
                <h2>パスワード変更</h2>

                <?php if (!empty($error)): ?>
                    <div class="msg error-msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($info)): ?>
                    <div class="msg info-msg"><?= htmlspecialchars($info) ?></div>
                <?php endif; ?>

                <form action="change_password.php" method="post">
                    <div class="form-group">
                        <label>現在のパスワード</label>
                        <input type="password" name="pass" placeholder="現在のパスワードを入力" required>
                    </div>
                    
                    <div class="form-group">
                        <label>新しいパスワード</label>
                        <input type="password" name="pass_new" placeholder="新しいパスワードを入力" required>
                    </div>

                    <div class="form-group">
                        <label>新しいパスワード（確認）</label>
                        <input type="password" name="pass_new_con" placeholder="新しいパスワードを再度入力" required>
                    </div>
                    
                    <div class="button_parent">
                        <button type="submit">変更する</button>
                    </div>
                </form>
            </div>
        </main>

        <script>
        const menuBtn = document.getElementById('menuBtn');
        const closeBtn = document.getElementById('closeBtn');
        const sideMenu = document.getElementById('sideMenu');
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const headerLogo = document.getElementById('headerLogo');
        const themeLink = document.getElementById('theme-link');

        // ハンバーガーマーク処理
        menuBtn.addEventListener('click', () => {
            sideMenu.classList.add('active');
        });
        closeBtn.addEventListener('click', () => {
            sideMenu.classList.remove('active');
        });

        // テーマ切替
        function updateToggleBtnIcon(theme) {
            themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        const currentTheme = localStorage.getItem('theme') || 'light';
        updateToggleBtnIcon(currentTheme);

        if (headerLogo) {
            headerLogo.src = currentTheme === 'dark' ? 'img/logo_dark.png' : 'img/tanelog.png';
        }
        if (themeLink) {
            themeLink.href = currentTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
        }

        themeToggleBtn.addEventListener('click', () => {
            const isDark = themeLink.href.includes('css/style-dark.css');
            const newTheme = isDark ? 'light' : 'dark';
            
            themeLink.href = newTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
            updateToggleBtnIcon(newTheme);
            if (headerLogo) {
                headerLogo.src = newTheme === 'dark' ? 'img/logo_dark.png' : 'img/tanelog.png';
            }
            localStorage.setItem('theme', newTheme);
        });
        </script>
    </body>
</html>