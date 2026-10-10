<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/api/logincheck.php';
if (isset($_GET['username'])) {
    $username = htmlspecialchars($_GET['username']);
    $stmt = $pdo->prepare("SELECT id, icon_path, created_at, profile_statement, nickname FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);    
    // ユーザーが存在しない場合のフォールバック
    if (!$user) {
        $error = "ユーザー(".$username.")が存在しません。";
        echo $error;
        exit;
    }
    $time = mb_substr($user['created_at'], 0, 10);


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
    $stmt = $pdo->prepare("SELECT posts.*,
       users.nickname, users.icon_path, users.username,
       COUNT(DISTINCT likes.id) AS like_count,
       COUNT(DISTINCT replies.id) AS reply_count,
       SUM(CASE WHEN likes.user_id = ? THEN 1 ELSE 0 END) AS liked_by_me
     FROM posts
     JOIN users ON posts.user_id = users.id
     LEFT JOIN posts AS replies ON replies.parent_id = posts.id
     LEFT JOIN likes ON likes.post_id = posts.id
     WHERE posts.parent_id IS NULL AND posts.deleted = 0 AND posts.user_id = ?
     GROUP BY posts.id
     ORDER BY posts.created_at DESC");
    $stmt->execute([$user['id'],$user['id']]);
    $userPosts = $stmt->fetchAll();

    $currentIcon = $user['icon_path'];
    $iconSrc = ($currentIcon && file_exists(__DIR__ . '/' . $currentIcon))
    ? htmlspecialchars($currentIcon)
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['nickname'] ?? 'U') . '&background=4F5D95&color=fff';
    // ── 添付ファイルを一括取得 ──
    $post_ids = array_column($userPosts, 'id');
    $attachments_map = [];

    if (!empty($post_ids)) {
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        $att_stmt = $pdo->prepare(
            "SELECT * FROM attachments WHERE post_id IN ($placeholders) ORDER BY id ASC"
        );
        $att_stmt->execute($post_ids);
        foreach ($att_stmt->fetchAll() as $att) {
            $attachments_map[$att['post_id']][] = $att;
        }   
    }
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

                    <?php else: ?>
                        <a href="update.php"><button class="follow-btn">プロフィールを編集</button></a>
                        
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
                            <?php
                                $iconSrc = ($p['icon_path'] && file_exists(__DIR__ . '/' . $p['icon_path']))
                                    ? htmlspecialchars($p['icon_path'])
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($p['nickname']) . '&background=4F5D95&color=fff';
                            ?>
                            <a href="profile.php?username=<?= htmlspecialchars($p['username']) ?>" class="front-link"><img src="<?= $iconSrc ?>" alt="アイコン" class="post-icon"></a>
                            <div class="post-meta">
                                <div class="post-name-row">
                                    <div class="post-nickname"><?= htmlspecialchars($p['nickname']) ?></div>
                                    <div class="post-username"><a href="profile.php?username=<?= htmlspecialchars($p['username']); ?>" class="front-link" style="color: #a0aec0">@<?= htmlspecialchars($p['username']) ?></a></div>
                                </div>
                            </div>
                        </div>
                        <p class="post-content"><?= $p['content']; ?></p>

                        <!-- 添付ファイル -->
                        <?php if (!empty($attachments_map[$p['id']])): ?>
                            <div class="attachment-list front-link">
                                <?php foreach ($attachments_map[$p['id']] as $att): ?>
                                    <?php if (str_starts_with($att['mime_type'], 'image/')): ?>
                                        <img src="api/serve_file.php?id=<?= $att['id'] ?>" class="attachment-image front-link" alt="<?= htmlspecialchars($att['original_name']) ?>" loading="lazy">
                                    <?php elseif (str_starts_with($att['mime_type'], 'video/')): ?>
                                        <video controls class="attachment-video front-link" preload="none">
                                        <source src="api/serve_file.php?id=<?= $att['id'] ?>" type="<?= htmlspecialchars($att['mime_type']) ?>">
                                    </video>

                                <?php elseif (str_starts_with($att['mime_type'], 'audio/')): ?>
                                    <audio controls class="attachment-audio front-link">
                                        <source src="api/serve_file.php?id=<?= $att['id'] ?>" type="<?= htmlspecialchars($att['mime_type']) ?>">
                                    </audio>
<?php else: ?>
    <div class="attachment-item front-link" data-att-id="<?= $att['id'] ?>">
        <a href="download_page.php?id=<?= $att['id'] ?>"
           class="attachment-link"
           target="_blank">
            📁 <?= htmlspecialchars($att['original_name']) ?>
        </a>
        <span class="attachment-size">(<?= number_format($att['file_size'] / 1024 / 1024, 1) ?>MB)</span>

        <button type="button" class="attachment-menu-btn" aria-label="その他の操作" aria-haspopup="true" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="5" r="2"/>
                <circle cx="12" cy="12" r="2"/>
                <circle cx="12" cy="19" r="2"/>
            </svg>
        </button>

        <div class="attachment-menu" hidden>
            <a href="download_page.php?id=<?= $att['id'] ?>" class="attachment-menu-item" target="_blank">ダウンロード</a>
            <button type="button" class="attachment-menu-item attachment-report-btn" data-att-id="<?= $att['id'] ?>">通報</button>
        </div>
    </div>
<?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
<div class="post-actions front-link">
    <a href="detail.php?contentid=<?= htmlspecialchars($p['content_id']) ?>&reply=1" class="action-btn reply-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span class="action-count"><?= (int)$p['reply_count'] ?></span>
    </a>

    <button id="like_button" class="action-btn like-btn <?= $p['liked_by_me'] ? 'liked' : '' ?>" data-content-id="<?= htmlspecialchars($p['content_id']) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        <span class="like-count"><?= (int)$p['like_count'] ?></span>
    </button>
</div>
                        <div class="post-time"><?= htmlspecialchars($p['created_at']) ?></div>
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