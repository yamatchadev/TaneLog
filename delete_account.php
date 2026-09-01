<?php
$page = basename(__FILE__);
require_once 'db.php';
require_once 'api/logincheck.php';

$error = '';
$success = false;
if(isset($_SESSION['user_id'])){
    $inputid = htmlspecialchars($_SESSION['user_id']);
    $stmt = $pdo->prepare("SELECT username, nickname FROM users WHERE id = ?");
    $stmt->execute([$inputid]);
    $input = $stmt->fetch();
    $inputusername = $input['username'];
}else{
        header('Location: login.php?to=delete_account.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $pass = $_POST['pass'];
    $id = $_SESSION['user_id'];

    try {
        // 現在のユーザーのハッシュ化された正しいパスワードを取得
        $stmt_user = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_user->execute([$id]);
        $user = $stmt_user->fetch();

        // パスワード確認の検証
        if ($user && password_verify($pass, $user['password'])) {
            // 1. 本人の投稿をすべて削除
            $stmt_posts = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
            $stmt_posts->execute([$id]);

            // 2. ユーザーアカウントの削除
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            // 3. セッションの破棄とクリーンアップ
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            $success = true;
        } else {
            $error = "パスワードが正しくありません。";
        }

    } catch (PDOException $e) {
        $error = "エラーが発生しました: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <script src="js/theme.js"></script>
        <script src="js/sidemenu.js"></script>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>アカウント削除 - TaneLog</title>

        <style>
            /* 固定レイアウトや個別スタイルのみ残す */
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: var(--bg-color);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .card {
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
            h2 {
                font-size: 20px;
                color: var(--danger-color, #e53e3e);
                margin-top: 0;
                margin-bottom: 15px;
            }
            p {
                font-size: 14px;
                line-height: 1.6;
            }
            .form-group {
                margin-bottom: 20px;
            }
            input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                box-sizing: border-box;
                font-size: 15px;
                background-color: var(--card-bg);
                color: inherit;
            }
            button {
                width: 100%;
                background-color: var(--danger-color, #e53e3e);
                color: white;
                border: none;
                padding: 12px;
                border-radius: 6px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
            }
            button:hover {
                background-color: #c53030;
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
            .success-view {
                text-align: center;
            }
            .back-link {
                display: block;
                text-align: center;
                margin-top: 20px;
                color: var(--primary-color, #4F5D95);
                text-decoration: none;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <?php if ($success): ?>
                <div class="success-view">
                    <h2>削除完了</h2>
                    <p>アカウントとすべての投稿データを完全に削除しました。ご利用ありがとうございました。</p>
                    <a href="register.php" class="back-link">新規登録画面へ</a>
                </div>
            <?php else: ?>
                <h2>アカウントの永久削除</h2>
                <p>アカウントを削除すると、これまでの投稿データもすべて削除され、元に戻すことはできません。</p>
                
                <?php if ($error !== ''): ?>
                    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="delete_account.php" method="post">
                    <div class="form-group">
                        <p>ユーザーID</p>
                        <input type="text" value="<?= htmlspecialchars($inputusername); ?>" readonly disabled style="background-color: #bcbaba;">
                        <p>パスワード</p>
                        <input type="password" name="pass" placeholder="パスワード" required>
                    </div>
                    <button type="submit">アカウントを完全に削除</button>
                </form>
                <a href="timeline.php" class="back-link">キャンセルして戻る</a>
            <?php endif; ?>
        </div>
    </body>
</html>