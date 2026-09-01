<?php
if(isset($_COOKIE['remember_token'])) {
    require_once 'db.php';

    $token = $_COOKIE['remember_token'];
    $hashed = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT remember_token FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $db_token = $stmt->fetch();

    $is_match = ($hashed === $db_token['remember_token']);
?>
<div class="debug-card">
    <h2>トークン検証デバッグ</h2>
    <div class="debug-row">
        <span class="debug-label">GETのID</span>
        <span class="debug-value"><?= htmlspecialchars($_GET['id']) ?></span>
    </div>
    <div class="debug-row">
        <span class="debug-label">クッキーのトークン</span>
        <span class="debug-value"><?= htmlspecialchars($token) ?></span>
    </div>
    <div class="debug-row">
        <span class="debug-label">ハッシュ化したトークン</span>
        <span class="debug-value"><?= htmlspecialchars($hashed) ?></span>
    </div>
    <div class="debug-row">
        <span class="debug-label">DBのトークン</span>
        <span class="debug-value"><?= htmlspecialchars($db_token['remember_token'] ?? 'なし') ?></span>
    </div>
    <div class="debug-result <?= $is_match ? 'match' : 'mismatch' ?>">
        <?= $is_match ? 'トークンが一致しました。ログインできます。' : 'トークンが一致しません。あれれ？' ?>
    </div>
</div>

<div class="info-card">
    <?php if ($is_match): ?>
        <p>トークンが一致した場合、ログイン画面にアクセスすると自動でログインが完了します。</p>
    <?php else: ?>
        <p>トークンが一致しない場合、手動ログインが必要です。</p>
    <?php endif; ?>
    <a class="login-btn" href="login.php">ログイン画面に行く</a>
</div>

<?php
} else {
?>
<div class="debug-card">
    <p class="empty-msg">GETが空です</p>
</div>
<?php
}
?>

<style>
.debug-card,
.info-card {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    font-family: -apple-system, "Helvetica Neue", Arial, sans-serif;
}

.debug-card h2 {
    margin: 0 0 16px;
    font-size: 1.1rem;
    color: #4a3f7a;
}

.debug-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.debug-label {
    font-size: 0.8rem;
    color: #888;
}

.debug-value {
    font-size: 0.95rem;
    color: #333;
    word-break: break-all;
}

.debug-result {
    margin-top: 16px;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
    text-align: center;
}

.debug-result.match {
    background: #e6f7ec;
    color: #1a7f4b;
}

.debug-result.mismatch {
    background: #fdeaea;
    color: #b3261e;
}

.info-card p {
    margin: 0 0 16px;
    font-size: 0.95rem;
    color: #444;
    line-height: 1.6;
    text-align: center;
}

.login-btn {
    display: block;
    text-align: center;
    padding: 12px;
    border-radius: 8px;
    background: #4a3f7a;
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    font-size: 1rem;
    transition: background 0.2s;
}

.login-btn:hover {
    background: #3a3162;
}

.empty-msg {
    color: #b3261e;
    text-align: center;
    margin: 0;
}

@media (max-width: 480px) {
    .debug-card,
    .info-card {
        margin: 10px;
        padding: 16px;
    }
    .debug-value {
        font-size: 0.85rem;
    }
}
</style>