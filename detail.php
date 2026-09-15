<?php
require_once 'api/logincheck.php';
require_once 'db.php';

$post = null;
$deleteurl = "";
$deleteable = "false";
$deleteclass = "hidden"; 
$replies = [];
$show_reply = "off";
if (isset($_GET['contentid'])) {
    //timeline.phpからリプライ遷移
    if (!isset($_GET['post']) || $_GET['post'] === '0'){
        if (isset($_GET['reply']) && $_GET['reply'] === '1'){
            $show_reply = "on";            
        }else{
            $show_reply = "off";
        }
    }else{
        $show_reply = "off";
    }

    $contentid = htmlspecialchars($_GET['contentid']);
    // JOINを使って投稿と投稿者情報を一括取得(AI)
    $stmt = $pdo->prepare(
        "SELECT posts.*, users.nickname, users.icon_path, users.username,
        COUNT(DISTINCT likes.id) AS like_count,
        COUNT(DISTINCT replies.id) AS reply_count,
        SUM(CASE WHEN likes.user_id = ? THEN 1 ELSE 0 END) AS liked_by_me
        FROM posts
        JOIN users ON posts.user_id = users.id 
        LEFT JOIN likes ON likes.post_id = posts.id
        LEFT JOIN posts AS replies ON replies.parent_id = posts.id
        WHERE posts.content_id = ?
        GROUP BY posts.id"
    );
    $stmt->execute([$_SESSION['user_id'],$contentid]);
    $post = $stmt->fetch();


    // 添付ファイル取得
    $attachments = [];
    if ($post) {
        if($post['deleted'] == 1){
            $post_error = "削除された投稿です。";
            
        }else{
            $att_stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ? ORDER BY id ASC");
            $att_stmt->execute([$post['id']]);
            $attachments = $att_stmt->fetchAll();
            if(empty($attachments)){
                        $file_error = "添付ファイルが見つかりません。";
            }
            $post['user_id'] = (int)$post['user_id']; 
            $deleteable = isset($_SESSION['user_id']) && $_SESSION['user_id'] === $post['user_id'];
            if($deleteable){
                $deleteurl = "api/delete.php?contentid=" . $contentid;
                $deleteclass = "back-link";
            }else{
                $deleteurl = "";
                $deleteclass = "hidden";
            }
            $replies = $pdo->prepare(
                "SELECT posts.*, users.nickname, users.icon_path, users.username,
                COUNT(DISTINCT likes.id) AS like_count,
                COUNT(DISTINCT replies.id) AS reply_count,
                SUM(CASE WHEN likes.user_id = ? THEN 1 ELSE 0 END) AS liked_by_me
                FROM posts
                JOIN users ON posts.user_id = users.id
                LEFT JOIN likes ON likes.post_id = posts.id
                LEFT JOIN posts AS replies ON replies.parent_id = posts.id
                WHERE posts.parent_id = ?
                GROUP BY posts.id
                ORDER BY posts.created_at ASC"
            );
            $replies->execute([$_SESSION["user_id"],$post['id']]);
            $replies=$replies->fetchAll();
        }
    }else{
        $post_error = "投稿が存在しません。コンテンツIDを確認してください。";    
        $post_not_exist = "yes";
    }
}else{
    $post_error = "不正なリクエストです。";
}
if(!isset($post_not_exist)){
    $iconSrc = ($post['icon_path'] && file_exists(__DIR__ . '/' . $post['icon_path']))
            ? htmlspecialchars($post['icon_path'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($post['nickname']) . '&background=4F5D95&color=fff';
}        

// ───【追加】ログインユーザーの情報を取得 ───
$user_stmt = $pdo->prepare("SELECT nickname, icon_path, username FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$current_user = $user_stmt->fetch();

// ログインユーザー用のアイコンパスを確定
$current_icon_src = ($current_user['icon_path'] && file_exists(__DIR__ . '/' . $current_user['icon_path']))
    ? htmlspecialchars($current_user['icon_path'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['nickname'] ?? 'User') . '&background=4F5D95&color=fff';

// 投稿内のURLリンク化
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
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>投稿詳細 - TaneLog</title>
                
        <script src="js/sidemenu.js"></script>
        <script src="js/theme.js"></script>

        <style>
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px 15px;
            }
            .error-msg {
                background-color: #fff5f5;
                color: #c53030;
                border-radius: 6px;
                font-size: 14px;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-bottom: 12px;
                border: 1px solid var(--border-color);
            }
            /* 詳細表示用のカードスタイル */
            .post-card {
                position: relative;
                background: var(--card-bg);
                border-radius: 12px;
                padding: 15px 20px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                margin-bottom: 12px;
                border: 1px solid var(--border-color);
            }
            .front-link {
                position: relative;
                z-index: 2;
            }
            .post-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 15px;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 12px;
            }
            .post-icon {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid var(--border-color);
                flex-shrink: 0;
            }
            .post-nickname {
                font-weight: bold;
                font-size: 16px;
                color: var(--primary-color);
            }
            .post-username {
                font-size: 13px;
                color: #a0aec0;
                margin-top: 2px;
                text-decoration: underline;
                text-decoration-color: #a0aec0;
                text-decoration-thickness: 1px; 
            }
            .post-meta {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .post-name-column {
                display: flex;
                flex-direction: column;
            }
            .post-time {
                font-size: 12px;
                color: #a0aec0;
                margin-top: 15px;
                border-top: 1px solid var(--border-color);
                padding-top: 8px;
            }
            .post-content {
                font-size: 16px;
                line-height: 1.6;
                white-space: pre-wrap;
                margin: 0 0 12px 0;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
                /* 添付ファイルエリア */
        .attachment-list {
            display: flex;
            flex-direction: column;
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
            /* タイムラインに戻るボタン */
            .back-nav {
                margin-bottom: 15px;
            }
            .back-link {
                color: var(--primary-color);
                text-decoration: none;
                font-size: 15px;
                font-weight: bold;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .hidden{
                display: none;
            }
            .back-link:hover {
                text-decoration: underline;
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
/*リプライ画面 */
.reply-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
    animation: overlayIn 0.2s ease;
}
@keyframes overlayIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
.reply-modal {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    padding: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: modalIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes modalIn {
    from { opacity: 0; transform: translateY(12px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.reply-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}
.reply-to-label {
    font-size: 14px;
    color: #a0aec0;
}
.reply-to-label strong {
    color: var(--primary-color);
}
.reply-close-btn {
    color: #a0aec0;
    display: flex;
    align-items: center;
    padding: 4px;
    border-radius: 50%;
    transition: background 0.15s, color 0.15s;
}
.reply-close-btn:hover {
    background: var(--border-color);
    color: var(--text-color);
}
.reply-origin {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    background: var(--bg-color);
    border-radius: 8px;
    margin-bottom: 16px;
    border: 1px solid var(--border-color);
}
.reply-origin-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.reply-origin-content {
    margin: 0;
    font-size: 13px;
    color: #a0aec0;
    line-height: 1.5;
}
.reply-input-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 12px;
}
.reply-my-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    margin-top: 4px;
}
.reply-input-row textarea {
    flex: 1;
    background: var(--bg-color);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 15px;
    font-family: inherit;
    resize: none;
    box-sizing: border-box;
}
.reply-input-row textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}
.reply-modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
}
.reply-char-count {
    font-size: 13px;
    color: #a0aec0;
}
.reply-modal-footer button {
    padding: 8px 20px;
    font-size: 14px;
}
.reply-modal-footer button:disabled {
    opacity: 0.4;
    cursor: default;
}
/* 返信一覧（post-list）内のカードサイズを縮小 */
.post-list .post-card {
    padding: 12px 16px;       /* 内側の余白を小さく */
    margin-bottom: 8px;       /* 下のマージンを詰める */
    border-radius: 8px;       /* 角丸を少し控えめに */
}

/* ヘッダー周りの縮小 */
.post-list .post-header {
    gap: 8px;
    margin-bottom: 8px;
    padding-bottom: 8px;
}

/* アイコンサイズを小さく (48px -> 36px) */
.post-list .post-icon {
    width: 36px;
    height: 36px;
}

/* ユーザー名・ニックネームの文字サイズを調整 */
.post-list .post-nickname {
    font-size: 14px;
}

.post-list .post-username {
    font-size: 12px;
}

/* 本文の文字サイズを小ぶりに (16px -> 14px) */
.post-list .post-content {
    font-size: 14px;
    line-height: 1.5;
}

/* アクションボタン・時間のフォントや間隔を小さく */
.post-list .post-actions {
    margin-bottom: 6px;
    gap: 16px;
}

.post-list .action-btn svg {
    width: 16px;
    height: 16px;
}

.post-list .post-time {
    font-size: 11px;
    margin-top: 8px;
    padding-top: 6px;
}/* リプライカード内にリンクを広げるための設定 */
.post-list .post-card {
    position: relative; /* カード基準で絶対配置 */
}

/* カード全体に透明なリンクを覆いかぶせる */
.post-card-link {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1; /* カード全体をクリック可能に */
}
.attachment-preview {
    display: block;
    width: 100%;
    border-radius: 10px;
    margin-bottom: 8px;
    border: 1px solid var(--border-color);
}
.attachment-image {
    max-height: 400px;
    object-fit: cover;
    cursor: pointer; /* 後で拡大表示も追加できる */
}
.attachment-video {
    max-height: 400px;
    background: #000;
}
.attachment-audio {
    width: 100%;
}
.lightbox {
    position: fixed;
    inset: 0;
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: overlayIn 0.2s ease;
}
.lightbox-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.lightbox-content {
    position: relative;
    z-index: 1;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lightbox-content img,
.lightbox-content video {
    max-width: 90vw;
    max-height: 90vh;
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.lightbox-close {
    position: fixed;
    top: 16px;
    right: 16px;
    background: rgba(0,0,0,0.5);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #fff;
    padding: 0;
    transition: background 0.2s;
}
.lightbox-close:hover {
    background: rgba(0,0,0,0.8);
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

<?php if ($show_reply === "on"): ?>
<div class="reply-overlay" id="replyOverlay">
    <div class="reply-modal">
        <div class="reply-modal-header">
            <span class="reply-to-label">返信先：<strong><?= htmlspecialchars($post['nickname']) ?></strong></span>
            <a href="detail.php?contentid=<?= htmlspecialchars($post['content_id']) ?>" class="reply-close-btn" aria-label="閉じる">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </a>
        </div>

        <div class="reply-origin">
            <img src="<?= $iconSrc ?>" alt="アイコン" class="reply-origin-icon">
            <p class="reply-origin-content"><?= htmlspecialchars(mb_strimwidth($post['content'], 0, 80, '...')) ?></p>
        </div>

        <form action="api/reply_post.php" method="post" id="replyForm">
            <input type="hidden" name="parent_id" value="<?= (int)$post['id'] ?>">
            <div class="reply-input-row">
                <img src="<?= $current_icon_src ?>" alt="自分のアイコン" class="reply-my-icon">
                <textarea name="content" id="replyContent" placeholder="返信を入力..." rows="3"></textarea>
            </div>
            <div class="reply-modal-footer">
                <span class="reply-char-count"><span id="charCount">0</span> / 140</span>
                <button type="submit" id="replySubmitBtn" disabled>返信する</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require_once 'header.php';?>
        <main class="container">
            <div class="back-nav">
                <a href="<?php if($post['deleted'] == 0){echo $_SERVER['HTTP_REFERER'] ?? 'timeline.php';}else{echo "timeline.php";}?>" class="back-link">← 戻る</a>
            </div>
            <?php if(isset($post_error)):?>
            <div class="error-msg"><?php echo $post_error;?></div>
            <?php endif;?>
            <div class="post-card" style="display:<?php if(isset($post_error)){echo "none;";}?>">
                <div class="post-header">

                    <a href="profile.php?username=<?= htmlspecialchars($post['username']) ?>" class="front-link">
                        <img src="<?= $iconSrc ?>" alt="アイコン" class="post-icon">
                    </a>
                    <div class="post-meta">
                        <div class="post-name-column">
                            <div class="post-nickname"><?= htmlspecialchars($post['nickname']) ?></div>
                            <div class="post-username">
                                <a href="profile.php?username=<?= htmlspecialchars($post['username']); ?>" class="front-link" style="color: #a0aec0">@<?= htmlspecialchars($post['username']) ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="post-content"><?= linkifyContent($post['content']) ?></p>
                <!-- 添付ファイル -->
                <?php if (!empty($attachments)): ?>
                    <div class="attachment-list">
                        <?php foreach ($attachments as $att): ?>
    <?php if (str_starts_with($att['mime_type'], 'image/')): ?>
        <img src="api/serve_file.php?id=<?= $att['id'] ?>" class="attachment-preview attachment-image" alt="<?= htmlspecialchars($att['original_name']) ?>" loading="lazy">

    <?php elseif (str_starts_with($att['mime_type'], 'video/')): ?>
        <video controls class="attachment-preview attachment-video" preload="none">
            <source src="api/serve_file.php?id=<?= $att['id'] ?>" type="<?= htmlspecialchars($att['mime_type']) ?>">
        </video>

    <?php elseif (str_starts_with($att['mime_type'], 'audio/')): ?>
        <audio controls class="attachment-preview attachment-audio">
            <source src="api/serve_file.php?id=<?= $att['id'] ?>" type="<?= htmlspecialchars($att['mime_type']) ?>">
        </audio>

    <?php else: ?>
        <a href="download_page.php?id=<?= $att['id'] ?>" class="attachment-item" target="_blank">
            📁 <?= htmlspecialchars($att['original_name']) ?>
            <span class="attachment-size">(<?= number_format($att['file_size'] / 1024 / 1024, 1) ?>MB)</span>
        </a>
    <?php endif; ?>
<?php endforeach; ?>
                    </div>
                <?php endif; ?>
<div class="post-actions front-link">
    <a href="detail.php?contentid=<?= htmlspecialchars($post['content_id']) ?>&reply=1" class="action-btn reply-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span class="action-count"><?= (int)$post['reply_count'] ?></span>
    </a>

    <button class="action-btn like-btn <?= $post['liked_by_me'] ? 'liked' : '' ?>" data-content-id="<?= htmlspecialchars($post['content_id']) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        <span class="like-count"><?= (int)$post['like_count'] ?></span>
    </button>
</div>
                <div class="post-time"><?= htmlspecialchars($post['created_at']) ?></div>
            </div>
        <a href="<?= $deleteurl; ?>" class="<?= $deleteclass; ?>" style="color: #e53e3e;">この投稿を削除</a>
        <?php if($show_reply === "on"){echo "リプライON";}; ?>
            


        <div class="post-list" id="postList">
            <?php foreach ($replies as $p): ?>
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
                        <p class="post-content"><?= htmlspecialchars($p['content']) ?></p>

                        <!-- 添付ファイル -->
                        <?php if (!empty($attachments_map[$p['id']])): ?>
                            <div class="attachment-list front-link">
                                <?php foreach ($attachments_map[$p['id']] as $att): ?>
                                    <a href="download_page.php?id=<?= $att['id'] ?>" 
                                        class="attachment-item front-link" 
                                        target="_blank">
                                            📁 <?= htmlspecialchars($att['original_name']) ?>
                                            <span class="attachment-size">(<?= number_format($att['file_size'] / 1024 / 1024, 1) ?>MB)</span>
                                        </a>
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
            </div>
        </main>

        <script>

    //いいねボタン
    document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.like-btn');
    if (!btn) return;

    e.stopPropagation(); // カードリンクへの伝播を防ぐ

    const contentId = btn.dataset.contentId;
    const countEl = btn.querySelector('.like-count');

    const res = await fetch('api/like_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `content_id=${contentId}`
    });
    const data = await res.json();

    countEl.textContent = data.count;
    btn.classList.toggle('liked');
});

// リプライフォームの文字数カウントと送信ボタン制御
const replyContent = document.getElementById('replyContent');
const replySubmitBtn = document.getElementById('replySubmitBtn');
const charCount = document.getElementById('charCount');

if (replyContent) {
    replyContent.addEventListener('input', () => {
        const len = replyContent.value.length;
        charCount.textContent = len;
        replySubmitBtn.disabled = len === 0 || len > 140;
        charCount.style.color = len > 140 ? '#e53e3e' : '#a0aec0';
    });
}

// ライトボックス
const lightbox      = document.getElementById('lightbox');
const lightboxImg   = document.getElementById('lightboxImg');
const lightboxVideo = document.getElementById('lightboxVideo');
const lightboxVideoSrc = document.getElementById('lightboxVideoSrc');
const lightboxClose = document.getElementById('lightboxClose');
const lightboxBackdrop = document.getElementById('lightboxBackdrop');

function openLightbox(type, src, mimeType = '') {
    lightboxImg.style.display   = 'none';
    lightboxVideo.style.display = 'none';

    if (type === 'image') {
        lightboxImg.src = src;
        lightboxImg.style.display = 'block';
    } else if (type === 'video') {
        lightboxVideoSrc.src  = src;
        lightboxVideoSrc.type = mimeType;
        lightboxVideo.load();
        lightboxVideo.style.display = 'block';
    }
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lightbox.style.display = 'none';
    lightboxVideo.pause();
    lightboxImg.src = '';
    lightboxVideoSrc.src = '';
    document.body.style.overflow = '';
}

// 画像クリック
document.addEventListener('click', (e) => {
    const img = e.target.closest('.attachment-image');
    if (img) {
        e.stopPropagation();
        openLightbox('image', img.src);
        return;
    }
});

// 閉じる
lightboxClose.addEventListener('click', closeLightbox);
lightboxBackdrop.addEventListener('click', closeLightbox);
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});
        </script>
    </body>
</html>