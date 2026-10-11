<?php
require_once 'db.php';
require_once 'api/logincheck.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<link rel="stylesheet" href="css/style.css">
<script src="js/theme.js"></script>
<style>
    th{
        font-size: 12px;
    }
    td{
        font-size: 15px;
    }
</style>
<body>
<?php require_once 'header.php'; ?>
    <div class="container">
        <div class="post-card">
            <h1>ログイン中のユーザー情報</h1>
            <table border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ユーザー名</th>
                        <th>ニックネーム</th>
                        <th>メールアドレス</th>
                        <th>作成日時</th>
                        <th>最終ログイン</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['nickname'],ENT_QUOTES,'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES,'UTF-8')?></td>
                        <td><?= htmlspecialchars($user['last_login'], ENT_QUOTES,'UTF-8')?></td>
                    </tr>        
                </tbody>
            </table>
            
            <h1>その他の情報</h1>
            <p>アクセス時刻: <?= date('Y-m-d H:i:s'); ?> (UTC)</p>
            <p>ドメイン: <?= $_SERVER['HTTP_HOST']; ?></p>
            <p>あなたのアドレス: <?= $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']; ?></p>
            <p>サイトバージョン: V26.10.11.1</p>
            <a href="<?php if(isset($_SERVER['HTTP_REFERER'])){if($_SERVER['HTTP_REFERER'] !== "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']){echo $_SERVER['HTTP_REFERER'] ?? 'timeline.php';}else{echo 'timeline.php';}}?>" class="back-link">← 戻る</a>      
        </div>            
    </div>
</body>
