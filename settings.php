<?php
require_once 'api/logincheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <script src="js/theme.js"></script>
        <script src="js/sidemenu.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>設定 - TaneLog</title>
        <link rel="icon" href="/favicon.ico" sizes="any">  
        <meta name="theme-color" content="#fef8e5">
        <link rel="apple-touch-icon" href="/icon/icon-192.png">
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
        <style>
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: var(--bg-color);
                color: var(--text-color);
                transition: background-color 0.3s, border-color 0.3s;
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
            
            /* ヘッダー右側のボタン配置用エリア */
            .header-actions {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            /* ダークモード切替ボタンのスタイル */
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
            .settings-container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .settings-tabs {
                display: flex;
                gap: 8px;
                border-bottom: 1px solid var(--border-color);
                flex-wrap: wrap;
            }

            .settings-tab-btn {
                background: none;
                border: none;
                padding: 10px 18px;
                font-size: 0.95rem;
                color: var(--text-color);
                cursor: pointer;
                border-bottom: 3px solid transparent;
                transition: color 0.2s ease, border-color 0.2s ease;
            }
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
                transition: background-color 0.3s;
            }

            /* サイドメニュー */
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

            /* メニュー内の閉じるボタンエリア */
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
                transition: background 0.2s;
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
.settings-tab-btn.active {
    color: var(--button-color);
    border-bottom-color: var(--button-color);
    font-weight: bold;
}

.settings-tab-btn:hover {
    color: var(--primary-color);
}

.settings-panel {
    display: none;
    flex-direction: column;
    gap: 16px;
}

.settings-panel.active {
    display: flex;
}

.settings-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.settings-card-title {
    font-size: 1rem;
    font-weight: bold;
    color: var(--text-color);
    margin: 0;
}

.settings-card-desc {
    font-size: 0.88rem;
    color: var(--text-color);
    opacity: 0.75;
    margin: 0;
}

.settings-btn {
    background: var(--button-color);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-size: 0.92rem;
    cursor: pointer;
    transition: background 0.2s ease;
    align-self: flex-start;
}

.settings-btn:hover {
    background: #3f533b;
}

.settings-btn:disabled {
    background: #ccc;
    cursor: default;
}

.settings-placeholder {
    color: var(--text-color);
    opacity: 0.6;
    font-size: 0.9rem;
    font-style: italic;
}
            /*h1テキスト。このテンプレートでは、画面最上部の帯の左側に小文字で入れているテキストです。*/
            header h1 {
	            font-weight: normal;
	            margin: 0;padding: 0;
	            font-size: 0.8rem;		/*文字サイズを80%*/
	            letter-spacing: 0.1em;	/*文字間隔を少しだけ広く*/
                
            }

	        /*画面幅700px以下の追加指定*/
	        @media screen and (max-width:700px) {

	        header h1 {
	            font-size: 0.7em;
            }
           }/*追加指定ここまで*/

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-8px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            /* 危険アクション用カード（赤色ベース） */
.settings-card.danger {
    background: rgba(254, 226, 226, 0.3); /* 薄い赤色の背景 */
    border-color: #fca5a5;
}

/* 危険アクション用ボタン */
.settings-btn.danger-btn {
    background: #e53e3e;
    color: #fff;
}

.settings-btn.danger-btn:hover {
    background: #c53030;
}
</style>
</head>
<body>
<?php require_once 'header.php';?>

<div class="settings-container">
    <h1>設定</h1>

    <div class="settings-tabs">
        <button class="settings-tab-btn active" data-tab="general">一般</button>
        <button class="settings-tab-btn" data-tab="notifications">通知</button>
        <button class="settings-tab-btn" data-tab="security">セキュリティ</button>
        <button class="settings-tab-btn" data-tab="help">ヘルプ</button>
    </div>

    <!-- 一般タブ -->
    <div class="settings-panel active" id="tab-general">
        <div class="settings-card">
            <p class="settings-card-title">プロフィールを編集</p>
            <p class="settings-card-desc">ユーザーID、ニックネーム、アイコン、プロフィール文を変更できます。</p>
            <a href="update.php"><button class="settings-btn">プロフィールを編集する</button></a>
        </div>
    </div>

    <!-- 通知タブ -->
    <div class="settings-panel" id="tab-notifications">
        <div class="settings-card">
            <p class="settings-card-title">プッシュ通知</p>
            <p class="settings-card-desc">通知を有効にすると、新着投稿やお知らせをすぐに受け取れます。</p>
            <button id="enable-push-btn" class="settings-btn">通知を有効にする</button>
        </div>
    </div>

    <!-- セキュリティタブ -->
    <div class="settings-panel" id="tab-security">
        <div class="settings-card">
            <p class="settings-card-title">パスワードを変更</p>
            <p class="settings-card-desc">アカウントのパスワードを変更します。</p>
            <a href="change_password.php"><button class="settings-btn">パスワードを変更する</button></a>
        </div>
        <div class="settings-card">
            <p class="settings-card-title" style="color: #e53e3e;">アカウントを削除</p>
            <p class="settings-card-desc">アカウントを永久に削除します。投稿もすべて消去されます。</p>
            <a href="delete_account.php"><button class="settings-btn danger-btn">アカウント削除ページ</button></a>
        </div>
    </div> 
    <!-- お問い合わせ・ヘルプタブ -->
    <div class="settings-panel" id="tab-help">
        <div class="settings-card">
            <p class="settings-card-title">お問い合わせフォーム</p>
            <p class="settings-card-desc">本サービスに関してのご意見、ご要望がありましたらお問い合わせください。</p>
            <a href="contact.php"><button class="settings-btn">お問い合わせフォーム</button></a>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.settings-tab-btn');
    const panels = document.querySelectorAll('.settings-panel');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });
});

</script>

<script src="/js/push-notifications.js"></script>

</body>
</html>