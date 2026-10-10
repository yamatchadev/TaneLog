<?php

if(!isset($_GET['contentid'])){
    header('Location: timeline.php');
    exit;
}
require_once 'api/logincheck.php';
require_once 'db.php';
$post = null;
$deleteurl = "";
$deleteable = "false";
$deleteclass = "hidden"; 
$replies = [];
$show_reply = false;

$_SESSION['view_contentid'] = htmlspecialchars($_GET['contentid']);

if (isset($_SESSION['deleted']) && $_SESSION['deleted'] === true){
    $url = "https://".$_SERVER['HTTP_HOST']."/timeline.php";
    header("Refresh: 3; URL={$url}");
    $_SESSION['deleted'] = false;
    $info = "投稿を削除しました。3秒後にタイムラインに戻ります。";
}
if(isset($_GET['reply']) && $_GET['reply'] === '1'){
    $url = 'detail.php?contentid='.$_GET['contentid'];
    $_SESSION['send_reply'] = true;
    header("Location: $url");
    exit;
}
if (isset($_SESSION['send_reply']) && $_SESSION['send_reply']){
    $show_reply = true;     
    unset($_SESSION['send_reply']);
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
                WHERE posts.parent_id = ? AND posts.deleted = 0
                GROUP BY posts.id
                ORDER BY posts.created_at ASC"
            );
            $replies->execute([$_SESSION["user_id"],$post['id']]);
            $replies=$replies->fetchAll();
        }
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
        <script src="js/tinymce/tinymce.min.js"></script>
        <script>
        </script>
        <style>
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px 15px;
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

<?php if ($show_reply): ?>

<?php endif; ?>
<?php require_once 'header.php';?>
        <main class="container">
            <div class="back-nav">
                <a href=
                "<?php if(isset($_SERVER['HTTP_REFERER'])){
                    if($_SERVER['HTTP_REFERER'] !== "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']){
                        echo $_SERVER['HTTP_REFERER'] ?? 'timeline.php';
                    }else{
                        echo 'timeline.php';
                        }
                    }
                ?>" class="back-link">← 戻る</a>
            </div> 
            <?php if(isset($info)):?>
                <div class="info-msg"><?= $info; ?></div>
            <?php endif;?>
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
                
                <p class="post-content"><?= $post['content'];?></p>
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
            

<?php if ($show_reply):
    $_SESSION['parent_id'] = (int)$post['id'];
    $_SESSION['form_url'] = "api/post.php";?>
<main class="container">
    <?php require_once "postform.php";?>    
</main>

<?php endif;?>
        <div class="post-list" id="postList">
            <?php foreach ($replies as $p): ?>
            <?php
                // 添付ファイル取得
                $replies_attachments = [];
                $att_stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ? ORDER BY id ASC");
                $att_stmt->execute([$p['id']]);
                $replies_attachments = $att_stmt->fetchAll();
                
            ?>
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
                        <?php if (!empty($replies_attachments)): ?>
                            <div class="attachment-list front-link">
                                <?php foreach ($replies_attachments as $ratt): ?>
                                    <?php if (str_starts_with($ratt['mime_type'], 'image/')): ?>
                                        <img src="api/serve_file.php?id=<?= $ratt['id'] ?>" class="attachment-image front-link" alt="<?= htmlspecialchars($ratt['original_name']) ?>" loading="lazy">
                                    <?php elseif (str_starts_with($ratt['mime_type'], 'video/')): ?>
                                        <video controls class="attachment-video front-link" preload="none">
                                        <source src="api/serve_file.php?id=<?= $ratt['id'] ?>" type="<?= htmlspecialchars($ratt['mime_type']) ?>">
                                    </video>

                                <?php elseif (str_starts_with($ratt['mime_type'], 'audio/')): ?>
                                    <audio controls class="attachment-audio front-link">
                                        <source src="api/serve_file.php?id=<?= $ratt['id'] ?>" type="<?= htmlspecialchars($ratt['mime_type']) ?>">
                                    </audio>
                                <?php else: ?>
                                    <div class="attachment-item front-link" data-att-id="<?= $ratt['id'] ?>">
                                        <a href="download_page.php?id=<?= $ratt['id'] ?>" class="attachment-link" target="_blank">📁 <?= htmlspecialchars($ratt['original_name']) ?></a>
                                        <span class="attachment-size">(<?= number_format($ratt['file_size'] / 1024 / 1024, 1) ?>MB)</span>

                                        <button type="button" class="attachment-menu-btn" aria-label="その他の操作" aria-haspopup="true" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="2"/>
                                            <circle cx="12" cy="12" r="2"/>
                                            <circle cx="12" cy="19" r="2"/>
                                        </svg>
                                        </button>

                                        <div class="attachment-menu" hidden>
                                            <a href="download_page.php?id=<?= $ratt['id'] ?>" class="attachment-menu-item" target="_blank">ダウンロード</a>
                                            <button type="button" class="attachment-menu-item attachment-report-btn" data-att-id="<?= $ratt['id'] ?>">通報</button>
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


// ── 添付ファイルの「⋮」メニュー（修正版） ──────────────────────
document.addEventListener('click', (e) => {
    const menuBtn = e.target.closest('.attachment-menu-btn');

    // クリックされたボタン以外の開いているメニューは閉じる
    document.querySelectorAll('.attachment-menu:not([hidden])').forEach(menu => {
        const ownerBtn = menu.previousElementSibling;
        if (ownerBtn !== menuBtn) {
            menu.hidden = true;
            ownerBtn?.setAttribute('aria-expanded', 'false');
            ownerBtn?.closest('.attachment-item')?.classList.remove('menu-open');
        }
    });

    if (menuBtn) {
        e.preventDefault();
        e.stopPropagation();
        const item = menuBtn.closest('.attachment-item');
        const menu = menuBtn.nextElementSibling;
        const willOpen = menu.hidden;

        menu.hidden = !willOpen;
        menuBtn.setAttribute('aria-expanded', String(willOpen));
        item.classList.toggle('menu-open', willOpen);
        return;
    }

    // 通報ボタン
    const reportBtn = e.target.closest('.attachment-report-btn');
    if (reportBtn) {
        e.preventDefault();
        e.stopPropagation();
        // TODO: 通報APIが用意できたら、ここでfetch()して送信してください
        alert('通報を受け付けました（送信処理は未実装です）');

        const item = reportBtn.closest('.attachment-item');
        item.querySelector('.attachment-menu').hidden = true;
        item.querySelector('.attachment-menu-btn')?.setAttribute('aria-expanded', 'false');
        item.classList.remove('menu-open');
        return;
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.attachment-menu:not([hidden])').forEach(menu => {
            const btn = menu.previousElementSibling;
            menu.hidden = true;
            btn?.setAttribute('aria-expanded', 'false');
            btn?.closest('.attachment-item')?.classList.remove('menu-open');
        });
    }
});
        </script>
    </body>
</html>