<?php
if(!isset($_GET['username'])){
    $page = basename(__FILE__);    
    header('Location: timeline.php');
    exit;
}else{
    $username = htmlspecialchars($_GET['username']);
    $page = basename(__FILE__)."&username=".$username;
}
require_once 'db.php';
require_once 'api/logincheck.php';

if (isset($_GET['username'])) {
    $username = htmlspecialchars($_GET['username']);
    $stmt = $pdo->prepare("SELECT id, icon_path, created_at, profile_statement, nickname FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    $time = mb_substr($user['created_at'], 0, 10);

    // ユーザーが存在しない場合のフォールバック
    if (!$user) {
        header('Location: timeline.php');
        exit;
    }
    // フォローフォロワー取得
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
    $stmt->execute([$user['id']]);
    $followerCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$user['id']]);
    $followingCount = $stmt->fetchColumn();
    
    $isOwnProfile = ($_SESSION['user_id'] == $user['id']);
    $isFollowing = false;

    if (!$isOwnProfile) {
        $stmt = $pdo->prepare("SELECT EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?) AS is_following");
        $stmt->execute([$_SESSION['user_id'], $user['id']]);
        $isFollowing = (bool)$stmt->fetchColumn();
    }

    //投稿表示
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $userPosts = $stmt->fetchAll();

    $currentIcon = $user['icon_path'];
    $iconSrc = ($currentIcon && file_exists(__DIR__ . '/' . $currentIcon))
    ? htmlspecialchars($currentIcon)
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['nickname'] ?? 'U') . '&background=4F5D95&color=fff';
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($user['nickname']) ?> (@<?= $username ?>) さんのプロフィール - TaneLog</title>
        <link rel="stylesheet" id="theme-link" href="css/style-light.css">
        <script>
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.getElementById('theme-link').href = savedTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
        </script>
        <style>
            body {
                margin: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background-color: var(--bg-color);
                color: var(--text-color);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .profile-card {
                background: var(--card-bg);
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
                width: 100%;
                max-width: 450px;
                box-sizing: border-box;
                border: 1px solid var(--border-color);
                margin: 15px;
                text-align: center;
            }
            
            /* アイコンの円形スタイリング */
            .profile-icon {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid var(--card-bg);
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
                margin-bottom: 15px;
            }

            .nickname {
                font-size: 22px;
                font-weight: bold;
                margin: 0 0 4px 0;
                color: var(--text-color);
            }

            .username {
                font-size: 15px;
                color: var(--muted-color);
                margin: 0 0 20px 0;
            }

            .divider {
                border: 0;
                border-top: 1px solid var(--border-color);
                margin: 20px 0;
            }

            /* 自己紹介セクション */
            .section-title {
                text-align: left;
                font-size: 13px;
                font-weight: bold;
                color: var(--muted-color);
                text-transform: uppercase;
                margin-bottom: 8px;
                letter-spacing: 0.5px;
            }

            .profile-statement {
                text-align: left;
                font-size: 15px;
                line-height: 1.6;
                background-color: var(--bg-color); /* 背景色に追従 */
                padding: 15px;
                border-radius: 8px;
                border: 1px solid var(--border-color);
                margin: 0 0 20px 0;
                white-space: pre-wrap; /* 改行を反映させる */
                min-height: 50px;
            }

            .meta-info {
                text-align: left;
                font-size: 13px;
                color: var(--muted-color);
                display: flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 25px;
            }

            /* ボタン */
            .back-link {
                display: block;
                background-color: var(--button-color);
                color: white;
                text-decoration: none;
                padding: 12px;
                border-radius: 6px;
                font-size: 15px;
                font-weight: bold;
                transition: background 0.2s;
            }
            .back-link:hover {
                opacity: 0.9;
            }
        </style>
    </head>
    <body>
        <div class="profile-card">
            <img class="profile-icon" src="<?= $iconSrc ?>" alt="ユーザーアイコン">
            
            <div class="nickname"><?= htmlspecialchars($user['nickname'] ?? 'ユーザー') ?></div>
            <div class="username">@<?= htmlspecialchars($username) ?></div>
            <div class="follow-stats">
    <div class="stat-item" id="followers-stat">
        <span class="stat-count"><?= $followerCount ?></span>
        <span class="stat-label">フォロワー</span>
    </div>
    <div class="stat-item" id="following-stat">
        <span class="stat-count"><?= $followingCount ?></span>
        <span class="stat-label">フォロー中</span>
    </div>
</div>

<?php if (!$isOwnProfile): ?>
<button id="follow-btn" class="follow-btn <?= $isFollowing ? 'following' : '' ?>" data-target-id="<?= $user['id'] ?>" data-following="<?= $isFollowing ? '1' : '0' ?>">
    <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
</button>
<?php endif; ?>
            <hr class="divider">

            <div class="section-title">自己紹介</div>
            <div class="profile-statement"><?php if (isset($user['profile_statement'])){echo(htmlspecialchars($user['profile_statement']));}else{echo "設定されていません。";}?></div>
            
            <div class="meta-info">
                <span></span>
                <span>参加した日: <?= htmlspecialchars($time) ?></span>
            </div>

            <a href="timeline.php" class="back-link">タイムラインに戻る</a>
        </div>
    </body>
</html>