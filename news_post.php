<?php
require_once 'api/logincheck.php';
require_once 'db.php';
require_once 'api/newscheck.php';

define('ADMIN_USER_ID', 24);

if ($_SESSION['user_id'] !== ADMIN_USER_ID) {
    header('Location: timeline.php');
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = 'タイトルと内容を両方入力してください。';
    } else {
        $title_clean   = htmlspecialchars($title);
        $content_clean = htmlspecialchars($content);

        $stmt = $pdo->prepare("INSERT INTO news (title, content) VALUES (?, ?)");
        $stmt->execute([$title_clean, $content_clean]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ニュース投稿 - TaneLog</title>
    <link rel="stylesheet" id="theme-link" href="css/style-light.css">
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
                background: none;
                transform: scale(1.1);
            }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 15px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        h2 {
            margin: 0 0 24px 0;
            font-size: 18px;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 6px;
        }
        input[type="text"],
        textarea {
            width: 100%;
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 12px;
            box-sizing: border-box;
            font-size: 15px;
            font-family: inherit;
            margin-bottom: 18px;
        }
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        textarea {
            resize: vertical;
            min-height: 200px;
        }
        button[type="submit"] {
            background-color: var(--button-color);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
        }
        button[type="submit"]:hover {
            background-color: #3b4671;
        }
        button[type="submit"]:disabled {
            opacity: 0.5;
            cursor: default;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .alert-error {
            background-color: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }
        .back-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        
            header h1 {
	            font-weight: normal;
	            margin: 0; padding: 0;
	            font-size: 0.8rem;
	            letter-spacing: 0.1em;
            }

	        @media screen and (max-width:700px) {
                header h1 {
                    font-size: 0.7em;
                }
            }
    </style>
</head>
<body>

<header>
    <div id="header-top">
                <h1><?= $recentnewsdate; ?> <?= $recentnews; ?><a href="./news.php">詳細</a></h1>
            </div>
    <a href="timeline.php"><img src="img/tanelog.png" alt="TaneLog" id="headerLogo"></a>
</header>

<main class="container">
    <div class="card">
        <h2>ニュース投稿</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">投稿しました！</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="news_post.php" method="post">
            <label for="title">タイトル</label>
            <input
                type="text"
                id="title"
                name="title"
                placeholder="ニュースのタイトル"
                value="<?= isset($_POST['title']) && !$success ? htmlspecialchars($_POST['title']) : '' ?>"
            >

            <label for="content">内容</label>
            <textarea
                id="content"
                name="content"
                placeholder="ニュースの内容を入力してください..."
            ><?= isset($_POST['content']) && !$success ? htmlspecialchars($_POST['content']) : '' ?></textarea>

            <button type="submit" id="submitBtn">投稿する</button>
        </form>

        <a href="admin.php" class="back-link">← 管理者ページに戻る</a>
    </div>
</main>

<script>
    // テーマ復元
    const themeLink = document.getElementById('theme-link');
    const headerLogo = document.getElementById('headerLogo');
    const savedTheme = localStorage.getItem('theme') || 'light';
    themeLink.href = savedTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
    headerLogo.src = savedTheme === 'dark' ? 'img/logo_dark.png' : 'img/tanelog.png';

    // 送信ボタンの有効・無効制御
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const submitBtn = document.getElementById('submitBtn');

    function checkInputs() {
        const isEmpty = titleInput.value.trim() === '' || contentInput.value.trim() === '';
        submitBtn.disabled = isEmpty;
        submitBtn.style.opacity = isEmpty ? '0.5' : '1';
    }

    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.5';

    titleInput.addEventListener('input', checkInputs);
    contentInput.addEventListener('input', checkInputs);
</script>

</body>
</html>