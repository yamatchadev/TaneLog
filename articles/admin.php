<?php
require_once '../api/logincheck.php';
require_once '../db.php';

$page = basename(__FILE__);
define('ADMIN_USER_ID', 24);

if ($_SESSION['user_id'] !== ADMIN_USER_ID) {
    header('Location: ../timeline.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $title = trim($_POST['title']);
    if(!$_POST['tags'] == ""){
    $tags = htmlspecialchars($_POST['tags']);
        $stmt = $pdo->prepare("INSERT INTO articles (title, tags) VALUES (?, ?)");
        $stmt->execute([$title, $tags]);
        $id = $pdo->lastInsertId();

        $filename = __DIR__ . '/contents/' . $id . '.php';

        $template = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
</head>
<body>
    <h1 class="article-title">{$title}</h1>
</body>
</html>
HTML;
        
        file_put_contents($filename, $template);
        $info = "記事「" . htmlspecialchars($title) . "」、タグ「". $tags ."」を登録し、ファイルを生成しました。(ID: {$id})";
    } else{
        $stmt = $pdo->prepare("INSERT INTO articles (title) VALUES (?)");
        $stmt->execute([$title]);
        $id = $pdo->lastInsertId();

        $filename = __DIR__ . '/contents/' . $id . '.php';

        $template = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
</head>
<body>
    <h1 class="article-title">{$title}</h1>
</body>
</html>
HTML;
        file_put_contents($filename, $template);
        $info = "記事「" . htmlspecialchars($title) . "」を登録し、ファイルを生成しました。(ID: {$id})";
        }        
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>記事登録＆作成（管理画面） - TaneLog</title>
                
        <link rel="stylesheet" id="theme-link" href="../css/style-light.css">
        
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
                max-width: 600px;
                margin: 0 auto;
                padding: 20px 15px;
            }
            .card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-bottom: 20px;
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
            input[type="text"] {
                width: 100%;
                background-color: var(--card-bg);
                color: var(--text-color);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 12px;
                box-sizing: border-box;
                font-size: 15px;
            }
            input[type="text"]:focus {
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
            /* メッセージ通知 */
            .msg {
                padding: 12px;
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
                <h1>【管理者用】記事生成システム管理画面</h1>
            </div>
            <div class="header-main-row">
                <div>
                    <a href="../timeline.php"><img src="../img/tanelog.png" alt="TaneLog" id="headerLogo"></a>
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

        <nav class="side-menu" id="sideMenu">
            <div class="menu-close-wrapper">
                <button class="close-btn" id="closeBtn">&times;</button>
            </div>
            <ul>
                <li><a href="../timeline.php">タイムラインに戻る</a></li>
                <li><a href="../logout.php">ログアウト</a></li>
            </ul>
        </nav>

        <main class="container">
            <div class="card">
                <h2>新規記事の登録と生成</h2>

                <?php if (!empty($error)): ?>
                    <div class="msg error-msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($info)): ?>
                    <div class="msg info-msg"><?= $info ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="form-group">
                        <label for="title">記事タイトル</label>
                        <input type="text" id="title" name="title" placeholder="作成する記事のタイトルを入力" required>
                    </div>
                    <div class="form-group">
                        <label for="tags">記事につけるタグ</label>
                        <input type="text" id="tags" name="tags" placeholder="記事につけるタグを,区切りで登録" >
                    </div>
                    
                    <div class="button_parent">
                        <button type="submit">登録＆テンプレート生成</button>
                    </div>
                </form>            
            </div>
            <a href="../admin.php" class="back-link">← 管理者ページに戻る</a>
        </main>

        <script>
        const menuBtn = document.getElementById('menuBtn');
        const closeBtn = document.getElementById('closeBtn');
        const sideMenu = document.getElementById('sideMenu');
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const headerLogo = document.getElementById('headerLogo');
        const themeLink = document.getElementById('theme-link');

        // サイドメニュー開閉
        menuBtn.addEventListener('click', () => { sideMenu.classList.add('active'); });
        closeBtn.addEventListener('click', () => { sideMenu.classList.remove('active'); });

        // ダークモード同期
        function updateToggleBtnIcon(theme) {
            themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        const currentTheme = localStorage.getItem('theme') || 'light';
        updateToggleBtnIcon(currentTheme);

        if (headerLogo) {
            headerLogo.src = currentTheme === 'dark' ? '../img/logo_dark.png' : '../img/tanelog.png';
        }
        if (themeLink) {
            themeLink.href = currentTheme === 'dark' ? '../css/style-dark.css' : '../css/style-light.css';
        }

        themeToggleBtn.addEventListener('click', () => {
            const isDark = themeLink.href.includes('css/style-dark.css');
            const newTheme = isDark ? 'light' : 'dark';
            
            themeLink.href = newTheme === 'dark' ? '../css/style-dark.css' : '../css/style-light.css';
            updateToggleBtnIcon(newTheme);
            if (headerLogo) {
                headerLogo.src = newTheme === 'dark' ? '../img/logo_dark.png' : '../img/tanelog.png';
            }
            localStorage.setItem('theme', newTheme);
        });
        </script>
    </body>
</html>