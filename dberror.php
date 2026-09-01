<?php
session_start();
if(isset($_SESSION['error'])){
    $error = $_SESSION['error'];
    require_once 'gmail.php';
    $devemail = "noteusforschool@gmail.com";
    $subject = "【TaneLog】データベースエラーが発生しました";
    $body = <<<EOT
        開発担当者様
        お客様がサイトにアクセスしたとき、データベース接続に問題が生じました。
        直ちに対応してください。
        問題が発生したファイル: {$_SESSION['caller']}
        EOT;

    sendGmail($devemail, $subject, $body);
}else{
    header('Location: timeline.php');
    exit; // リダイレクト後の処理中断のために追加を推奨
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <script src="js/theme.js"></script>
    <script src="js/sidemenu.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー - TaneLog</title>
            

    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        header {
            display: flex;
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
            margin: 0 auto;
            padding: 40px 15px;
        }

        /* エラー表示用のカードスタイル */
        .error-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px 25px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            border: 1px solid var(--border-color);
            text-align: center;
        }

        /* 警告・注意を促すアイコン風デザイン */
        .error-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #e53e3e; /* 警告色の赤 */
        }

        .error-card h1 {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-color);
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .error-card p {
            font-size: 15px;
            line-height: 1.8;
            color: var(--text-color);
            text-align: left; /* 文章は左寄りの方が読みやすいため */
            margin: 0 0 25px 0;
        }

        /* トップへ戻るボタン */
        .btn-home {
            display: inline-block;
            background-color: var(--button-color);
            color: white;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-home:hover {
            background-color: #3b4671;
        }
    </style>
</head>
<body>

    <header>
        <div>
            <a href="timeline.php"><img src="img/tanelog.png" alt="TaneLog" id="headerLogo"></a>
        </div>
        <div class="header-actions">
            <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="テーマ切り替え">🌙</button>
        </div>
    </header>

    <main class="container">
        <div class="error-card">
            <div class="error-icon">⚠️</div>
            <h1>サーバーでエラーが発生しました</h1>
            <p>
                ただいまお客様がアクセスされたページの処理中に、サーバー内でのエラーが発生いたしました。<br>
                ただいま原因特定と復旧作業を行っております。時間を空けてもう一度アクセスをお願いします。ご迷惑をおかけして申し訳ございません。
            </p>
        </div>
    </main>


</body>
</html>