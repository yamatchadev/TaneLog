<?php
$page = basename(__FILE__);
require_once 'api/logincheck.php';
require_once 'db.php';
require_once 'api/newscheck.php';

// 投稿フォーム処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'gmail.php';
    $content = trim($_POST['content']);
    if ($content !== '') {
        
        $content_clean = htmlspecialchars($content);
        $user_id = $_SESSION['user_id'];
        $content_id = '';
        for ($i = 0; $i < 15; $i++) {
            $content_id .= random_int(0, 9);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $contacttype = $_POST['contacttype'];
        $subject = "【TaneLog】お客様からのお問い合わせ";

        $body = <<<EOT
        お客様からのお問い合わせを受信しました。
        お問い合わせ内容：{$contacttype}
        お客様のメールアドレス：{$user['email']}
        お客様のユーザーネーム：{$user['username']}

        お問い合わせ内容：
        
        {$content_clean}

        EOT;

        sendGmail(
            "noteusforschool@gmail.com", $subject, $body
        );
    }
    header('Location: contact.php');
    exit;
}

$user_stmt = $pdo->prepare("SELECT nickname, icon_path, username, email FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$current_user = $user_stmt->fetch();


?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <script src="js/theme.js"></script>
        <script src="js/sidemenu.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>お問い合わせ - TaneLog</title>
                
        

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
                background: none;
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
                padding: 20px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-bottom: 20px;
                border: 1px solid var(--border-color);

            }
            select{
                width: 50%;
                background-color: var(--card-bg);
                color: var(--text-color);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 12px;
                box-sizing: border-box;

                resize: none;
                margin-bottom: 10px;
            }
            textarea {
                width: 100%;
                background-color: var(--card-bg);
                color: var(--text-color);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 12px;
                box-sizing: border-box;
                font-size: 15px;
                resize: none;
                margin-bottom: 10px;

            }
            textarea:focus {
                outline: none;
                border-color: var(--primary-color);
            }
            
            /* ───【追加・変更】投稿フォーム下部のレイアウト調整 ─── */
            .form-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                
            }
            .form-user-icon {
                width: 35px;
                height: 35px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border-color);
            }
            /* ────────────────────────────────────────────────── */

            button {
                background-color: var(--button-color);
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 15px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.2s;
            }
            button:hover {
                background-color: #3b4671;
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
            .email{
                font-size: 12px;
            }
           }/*追加指定ここまで*/

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-8px); }
                to   { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>

        <?php require 'header.php'; ?>

        <main class="container">
            <div class="card">
                <form action="contact.php" method="post">
                    <p>お問い合わせの種類</p>
                    <select name="contacttype" required> 
                        <option value="">＜選択してください＞</option>
                        <option value="account">本人様のアカウントに関して</option>
                        <option value="request">ご要望・ご意見</option>
                        <option value="other">その他</option>
                    </select>
                    <p>お問い合わせの内容</p>
                    <textarea name="content" rows="6"  required placeholder="できるだけ詳細にお書きください。登録されているメールアドレスへご返答させていただきます。"></textarea>
                    <div class="form-footer">
                        <p class="email">以下のメールアドレスに返信いたします:<br><?= $current_user['email'] ?></p>
                        <button type="submit">送信する</button>
                    </div>
                </form>
                </div>
        </main>
    </body>
    <script>

        const textarea = document.querySelector('textarea[name="content"]');
        const submitBtn = document.querySelector('button[type="submit"]');

        // 初期状態は無効
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';

        textarea.addEventListener('input', () => {
            const isEmpty = textarea.value.trim() === '';
            submitBtn.disabled = isEmpty;
            submitBtn.style.opacity = isEmpty ? '0.5' : '1';
        });
    </script>
</html>