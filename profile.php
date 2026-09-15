<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/api/logincheck.php';
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
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? AND deleted = 0 ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $userPosts = $stmt->fetchAll();

    $currentIcon = $user['icon_path'];
    $iconSrc = ($currentIcon && file_exists(__DIR__ . '/' . $currentIcon))
    ? htmlspecialchars($currentIcon)
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['nickname'] ?? 'U') . '&background=4F5D95&color=fff';
}
function linkifyContent($rawText) {
    // 1. HTMLエスケープ
    $escaped = htmlspecialchars($rawText, ENT_QUOTES, 'UTF-8');

    // 2. URLを検出してリンク化
    $pattern = '/(https?:\/\/[^\s<]+)/i';
    $escaped = preg_replace_callback($pattern, function ($matches) {
        $url = $matches[1];

        // 末尾の句読点・記号をリンクの外に出す
        $trailing = '';
        if (preg_match('/[).,!?、。」』]+$/u', $url, $tMatch)) {
            $trailing = $tMatch[0];
            $url = substr($url, 0, -mb_strlen($trailing));
        }

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="front-link">' . $url . '</a>' . $trailing;
    }, $escaped);

    return $escaped;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($user['nickname']) ?> (@<?= $username ?>) さんのプロフィール - TaneLog</title>
        
        <!-- timeline.php と共通のスクリプト群 -->
        <script src="js/theme.js"></script>
        <script src="js/sidemenu.js"></script>
        <link rel="icon" href="/favicon.ico" sizes="any">        
        <link rel="manifest" href="/manifest.json">
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

            /* ── ヘッダー (timeline.php 準拠) ── */
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

            /* ── 全体レイアウト ── */
            .layout-container {
                display: flex;
                max-width: 900px;
                margin: 20px auto;
                padding: 0 15px;
                gap: 20px;
                align-items: flex-start;
            }

            /* ── 左側サイドバー（プロフィール） ── */
            .sidebar {
                width: 300px;
                background: var(--card-bg);
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                border: 1px solid var(--border-color);
                overflow: hidden;
                flex-shrink: 0;
            }
            .header-photo {
                height: 100px;
                background-color: #cbd5e0;
                border-bottom: 1px solid var(--border-color);
            }
            .profile-info-container {
                padding: 0 20px 20px 20px;
                position: relative;
            }
            .profile-icon-large {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                object-fit: cover;
                border: 4px solid var(--card-bg);
                margin-top: -40px;
                background-color: white;
            }
            .nickname-large {
                font-size: 18px;
                font-weight: bold;
                margin: 10px 0 2px 0;
                color: var(--primary-color);
            }
            .username-large {
                font-size: 14px;
                color: var(--muted-color);
                margin-bottom: 15px;
            }
            .follow-stats {
                display: flex;
                gap: 20px;
                font-size: 14px;
                margin-bottom: 20px;
            }
            .stat-count {
                font-weight: bold;
                color: var(--text-color);
            }
            .profile-statement {
                font-size: 14px;
                line-height: 1.6;
                white-space: pre-wrap;
                margin-bottom: 20px;
                color: var(--text-color);
            }
            .follow-btn {
                background-color: var(--button-color);
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: bold;
                cursor: pointer;
                width: 100%;
                transition: background 0.2s;
                margin-bottom: 10px;
            }
            .follow-btn:hover {
                background-color: #3f533b;
            }
            .follow-btn.following {
                background-color: transparent;
                border: 1px solid var(--border-color);
                color: var(--text-color);
            }
            .join-date {
                font-size: 12px;
                color: var(--muted-color);
                text-align: center;
                border-top: 1px solid var(--border-color);
                padding-top: 15px;
            }

            /* ── 右側メインコンテンツ（投稿一覧 - timeline.php 準拠） ── */
            .main-content {
                flex: 1;
                min-width: 0;
                width: 100%;
                box-sizing: border-box; /* paddingやborderを含めて100%に収めるためのおまじない */
            }
            .post-card {
                position: relative;
                background: var(--card-bg);
                border-radius: 12px;
                padding: 15px 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                margin-bottom: 12px;
                border: 1px solid var(--border-color);
            }
            .post-card-link {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1; /* カード内の通常テキスト（詳細リンク）のレイヤー */
            }
            .front-link {
                position: relative;
                z-index: 2; /* 詳細リンク(z-index:1)より手前に出すことで個別にクリック可能に */
            }
            .post-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 12px;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 10px;
            }
            .post-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border-color);
                flex-shrink: 0;
            }
            .post-meta {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .post-name-row {
                display: flex;
                flex-direction: column;
            }
            .post-nickname {
                font-weight: bold;
                color: var(--primary-color);
                font-size: 15px;
            }
            .post-username {
                font-size: 12px;
                color: #a0aec0;
                margin-top: 2px;
            }
            .post-content {
                font-size: 15px;
                line-height: 1.6;
                white-space: pre-wrap;
                margin: 0 0 12px 0;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
            .post-actions {
                margin-bottom: 8px;
            }
            /*添付ファイルエリア*/
            .attachment-list {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 12px;
            }
            .attachment-item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 10px;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                font-size: 13px;
                color: var(--primary-color);
                text-decoration: none;
                background: var(--bg-color);
                word-break: break-all;
            }
            .attachment-item:hover {
                background: var(--border-color);
            }
            .attachment-size {
                color: #a0aec0;
                font-size: 12px;
            }
            .post-actions {
                display: flex;
                align-items: center;
                gap: 24px;
                margin-bottom: 10px;
            }
            .action-btn {
                display: flex;
                align-items: center;
                gap: 6px;
                background: none;
                border: none;
                cursor: pointer;
                color: #a0aec0;
                font-size: 14px;
                padding: 4px;
                border-radius: 20px;
                text-decoration: none;
                transition: color 0.2s;
            }
            .action-btn:hover {
                color: var(--primary-color);
            }
            .action-count {
                font-size: 13px;
            }
            .like-btn.liked {
                color: #e53e3e;
            }
            .like-btn.liked svg {
                fill: #e53e3e;
                stroke: #e53e3e;
            }
            .like-btn:hover {
                color: #e53e3e;
            }
            .post-time {
                font-size: 12px;
                color: #a0aec0;
                border-top: 1px solid var(--border-color);
                padding-top: 8px;
            }
            .no-posts {
                text-align: center;
                color: var(--muted-color);
                margin-top: 40px;
            }



        </style>
    </head>
    <body>
    <div id="lightbox" class="lightbox" style="display:none;">
    <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
    <div class="lightbox-content">
        <button class="lightbox-close" id="lightboxClose">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <img id="lightboxImg" src="" alt="" style="display:none;">
        <video id="lightboxVideo" controls style="display:none;">
            <source id="lightboxVideoSrc" src="" type="">
        </video>
    </div>
</div>
<?php require_once 'header.php';?>

        <div class="layout-container">
            <!-- 左側: サイドバー (プロフィール情報) -->
            <aside class="sidebar">
                <div class="header-photo"></div>
                
                <div class="profile-info-container">
                    <img class="profile-icon-large" src="<?= $iconSrc ?>" alt="ユーザーアイコン">
                    
                    <div class="nickname-large"><?= htmlspecialchars($user['nickname'] ?? 'ユーザー') ?></div>
                    <div class="username-large">@<?= htmlspecialchars($username) ?></div>
                    
                    <div class="follow-stats">
                        <div class="stat-item" id="following-stat">
                            <span class="stat-count"><?= $followingCount ?></span> フォロー
                        </div>
                        <div class="stat-item" id="followers-stat">
                            <span class="stat-count"><?= $followerCount ?></span> フォロワー
                        </div>
                    </div>

                    <?php if (!$isOwnProfile): ?>
                    <button id="follow-btn" class="follow-btn <?= $isFollowing ? 'following' : '' ?>" data-target-id="<?= $user['id'] ?>" data-following="<?= $isFollowing ? '1' : '0' ?>">
                        <?= $isFollowing ? 'フォロー解除' : 'フォローする' ?>
                    </button>

                    <?php endif; ?>

                    <div class="profile-statement"><?php if (isset($user['profile_statement'])){echo nl2br(htmlspecialchars($user['profile_statement']));}else{echo "設定されていません。";}?></div>
                    
                    <div class="join-date">参加した日: <?= htmlspecialchars($time) ?></div>
                </div>
            </aside>

            <!-- 右側: メインコンテンツ (投稿リスト) -->
            <main class="main-content">
                <?php if (!empty($userPosts)): ?>
                    <?php foreach ($userPosts as $p): ?>
                        <div class="post-card">
                            <a href="detail.php?contentid=<?= htmlspecialchars($p['content_id']) ?>" class="post-card-link" aria-label="投稿の詳細を見る"></a>
                            <div class="post-header">
                                <img src="<?= $iconSrc ?>" alt="アイコン" class="post-icon">
                                <div class="post-meta">
                                    <div class="post-name-row">
                                        <div class="post-nickname"><?= htmlspecialchars($user['nickname']) ?></div>
                                        <div class="post-username">@<?= htmlspecialchars($username) ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 投稿内容 (カラム名は適宜変更してください) -->
                            <p class="post-content"><?= linkifyContent($p['content'] ?? '（投稿内容）') ?></p>
                            
                            <div class="post-time"><?= htmlspecialchars(mb_substr($p['created_at'], 0, 16)) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-posts">まだ投稿がありません。</div>
                <?php endif; ?>
            </main>
        </div>
        <script>
document.addEventListener('DOMContentLoaded', () => {
    const followBtn = document.getElementById('follow-btn');
    
    if (followBtn) {
        followBtn.addEventListener('click', async (e) => {
            e.preventDefault(); // デフォルト動作のキャンセル
            
            const targetId = followBtn.dataset.targetId;
            const isFollowing = followBtn.dataset.following === '1'; // 現在フォロー中かどうか
            
            // フォロー状態に応じて送信先URLを変更
            // ※ follow.php / unfollow.php が api フォルダ内にある場合は 'api/follow.php' などパスを調整してください
            const url = isFollowing ? 'api/unfollow.php' : 'api/follow.php';
            
            const formData = new FormData();
            formData.append('target_user_id', targetId);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    if (isFollowing) {
                        // フォロー解除に成功した場合
                        followBtn.textContent = 'フォローする';
                        followBtn.classList.remove('following');
                        followBtn.dataset.following = '0';
                    } else {
                        // フォロー登録に成功した場合
                        followBtn.textContent = 'フォロー解除';
                        followBtn.classList.add('following');
                        followBtn.dataset.following = '1';
                    }
                } else {
                    alert(data.error || '処理に失敗しました');
                }
            } catch (error) {
                console.error('通信エラー:', error);
                alert('通信に失敗しました');
            }
        });
    }
});
</script>
    </body>
</html>