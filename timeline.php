<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$page = basename(__FILE__);

require_once 'api/logincheck.php';
require_once 'db.php';
require_once 'api/newscheck.php';
//投稿フォーム処理はpost.phpとupload_chunk.php

// ───【追加】ログインユーザーの情報を取得 ───
$user_stmt = $pdo->prepare("SELECT nickname, icon_path, username FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if(!$current_user){
    $_SESSION['error-msg'] = "ユーザー情報が存在しません。";
    header('Location: login.php');
}
// ログインユーザー用のアイコンパスを確定
$current_icon_src = ($current_user['icon_path'] && file_exists(__DIR__ . '/' . $current_user['icon_path']))
    ? htmlspecialchars($current_user['icon_path'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['nickname'] ?? 'User') . '&background=4F5D95&color=fff';
// ──────────────────────────────────────────

// 投稿一覧取得（投稿者名も一緒に）
$stmt = $pdo->prepare(
    "SELECT posts.*,
       users.nickname, users.icon_path, users.username,
       COUNT(DISTINCT likes.id) AS like_count,
       COUNT(DISTINCT replies.id) AS reply_count,
       SUM(CASE WHEN likes.user_id = ? THEN 1 ELSE 0 END) AS liked_by_me
     FROM posts
     JOIN users ON posts.user_id = users.id
     LEFT JOIN posts AS replies ON replies.parent_id = posts.id
     LEFT JOIN likes ON likes.post_id = posts.id
     WHERE posts.parent_id IS NULL AND posts.deleted = 0
     GROUP BY posts.id
     ORDER BY posts.created_at DESC
    "
);
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchALL();

// ── 添付ファイルを一括取得 ──
$post_ids = array_column($posts, 'id');
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

$latest_id = !empty($posts) ? $posts[0]['id'] : 0;
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <script src="js/theme.js"></script>
        <script src="js/sidemenu.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>タイムライン - TaneLog</title>
        <link rel="icon" href="/favicon.ico" sizes="any">        
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#fef8e5">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
        
        <style>

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
            .textarea-wrap {
                position: relative;
                margin-bottom: 10px;
            }
            .textarea-wrap textarea {
                margin-bottom: 0;
                padding-bottom: 36px; /* ツールバーの高さ分だけ下に余白 */
            }
            .textarea-toolbar {
                position: absolute;
                bottom: 16px;
                left: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .attach-label {
                font-size: 18px;
                cursor: pointer;
                line-height: 1;
                user-select: none;
            }
            .attach-label:hover {
                opacity: 0.7;
            }
            #attach-count {
                font-size: 12px;
                color: #a0aec0;
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
            button:hover:not(#like_button,#menuBtn,#themeToggleBtn) {
                background-color: var(--button-hover-color);
            }

            /* 投稿リスト */
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
            
            /* 上部: ユーザー情報エリア */
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
                flex-direction: column; /* 横並びから縦並びにし、detail.phpと統一 */
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
                text-decoration: underline;
                text-decoration-color: #a0aec0;
                text-decoration-thickness: 1px; 
            }
            
            /* 中央: 本文エリア */
            .post-content {
                font-size: 15px;
                line-height: 1.6;
                white-space: pre-wrap;
                margin: 0 0 12px 0; /* 下部に余白を確保 */
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
            /* 下部: 日時エリア */
            .post-time {
                font-size: 12px;
                color: #a0aec0;
                border-top: 1px solid var(--border-color);
                padding-top: 8px;
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

            .attachment-preview {
                border-radius: 10px;
                border: 1px solid var(--border-color);
            }
.attachment-image {
    width: calc(50% - 3px);
    max-height: 180px;
    object-fit: cover;
    cursor: zoom-in;
    border-radius: 10px;
}
            .attachment-video {
                max-width: calc(50% - 3px);
                background: #000;
                border-radius: 10px;
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
<?php require_once 'header.php';?>

        <main class="container">
            <div class="card">
                <form action="timeline.php" method="post" enctype="multipart/form-data">
                <div class="textarea-wrap">
                    <textarea name="content" rows="3" placeholder="何かつぶやいてみよう！"></textarea>
                    <div class="textarea-toolbar">
                        <label for="attachments" class="attach-label">📁</label>
                        <span id="attach-count"></span>
                    </div>
                </div>
                <input type="file" id="attachments" name="attachments[]" multiple style="display:none;">
                <div id="progress-area"></div>
                <div class="form-footer">
                    <a href="<?= "profile.php?username=".$current_user['username']; ?>">
                        <img src="<?= $current_icon_src ?>" alt="マイアイコン" class="form-user-icon">
                    </a>
                    <button type="submit">投稿する</button>
                </div>
                </form>
            </div>
<script>
document.getElementById('attachments').addEventListener('change', function () {
    const count = this.files.length;
    document.getElementById('attach-count').textContent = count > 0 ? count + '件選択中' : '';
});

const CHUNK_SIZE = 50 * 1024 * 1024; // 50MB

document.querySelector('form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const content = document.querySelector('textarea[name="content"]').value.trim();
    const files = document.getElementById('attachments').files;

    if (content === '' && files.length === 0) return;

    // 投稿テキストを先にPOSTしてpost_idを取得
    const formData = new FormData();
    formData.append('content', content);

const postRes = await fetch('api/post.php', { method: 'POST', body: formData });
const postJson = await postRes.json();

console.log('post.phpのレスポンス:', postJson); // ← 追加

if (!postJson.ok) {
    alert('投稿に失敗しました');
    return;
}

const post_id = postJson.post_id;
console.log('取得したpost_id:', post_id); // ← 追加
    // ファイルがあればチャンクアップロード
    if (files.length > 0) {
        const progressArea = document.getElementById('progress-area');
        progressArea.innerHTML = '';

        for (const file of files) {
            const upload_id = crypto.randomUUID().replace(/-/g, '');
            const total_chunks = Math.ceil(file.size / CHUNK_SIZE);

            // 進捗バーを追加
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
                <span>${file.name}</span>
                <progress value="0" max="${total_chunks}"></progress>
                <span class="progress-label">0 / ${total_chunks}</span>
            `;
            progressArea.appendChild(wrapper);
            const bar   = wrapper.querySelector('progress');
            const label = wrapper.querySelector('.progress-label');

            for (let i = 0; i < total_chunks; i++) {
                const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);

                const cd = new FormData();
                cd.append('upload_id',     upload_id);
                cd.append('chunk_index',   i);
                cd.append('total_chunks',  total_chunks);
                cd.append('original_name', file.name);
                cd.append('post_id',       post_id);
                cd.append('chunk',         chunk);

                const res  = await fetch('upload_chunk.php', { method: 'POST', body: cd });
                const json = await res.json();

                if (!json.ok) {
                    alert(`${file.name} のアップロードに失敗しました`);
                    break;
                }

                bar.value = i + 1;
                label.textContent = `${i + 1} / ${total_chunks}`;
            }
        }
    }

    window.location.href = 'timeline.php';
});
</script>

            <div class="post-list" id="postList">
                <?php foreach ($posts as $p): ?>
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
                                    <a href="download_page.php?id=<?= $att['id'] ?>"
                                    class="attachment-item front-link"
                                    target="_blank">
                                        📁 <?= htmlspecialchars($att['original_name']) ?>
                                        <span class="attachment-size">(<?= number_format($att['file_size'] / 1024 / 1024, 1) ?>MB)</span>
                                    </a>
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


// ── リアルタイム更新 ──────────────────────────
let latestId = <?= (int)$latest_id ?>;
const postList = document.getElementById('postList');

function buildPostCard(p) {
    const icon = (p.icon_path)
        ? p.icon_path
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.nickname)}&background=4F5D95&color=fff`;

    return `
    <div class="post-card" style="animation: fadeIn 0.4s ease">
        <a href="detail.php?contentid=${p.content_id}" class="post-card-link" aria-label="投稿の詳細を見る"></a>
        <div class="post-header">
            <a href="profile.php?username=${p.username}" class="front-link">
                <img src="${icon}" alt="アイコン" class="post-icon">
            </a>
            <div class="post-meta">
                <div class="post-name-row">
                    <div class="post-nickname">${p.nickname}</div>
                    <div class="post-username">
                        <a href="profile.php?username=${p.username}" class="front-link" style="color:#a0aec0">@${p.username}</a>
                    </div>
                </div>
            </div>
        </div>
        <p class="post-content">${p.content}</p>
        <div class="post-actions front-link">
            <a href="detail.php?contentid=${p.content_id}&reply=1" class="action-btn reply-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="action-count">${p.reply_count ?? 0}</span>
            </a>
            <button class="action-btn like-btn" data-content-id="${p.content_id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span class="like-count">${p.like_count ?? 0}</span>
            </button>
        </div>
        <div class="post-time">${p.created_at}</div>
    </div>`;
}

async function checkNewPosts() {
    try {
        const res = await fetch(`api/new_posts.php?since_id=${latestId}`);
        const posts = await res.json();
        if (posts.length > 0) {
            posts.forEach(p => postList.insertAdjacentHTML('afterbegin', buildPostCard(p)));
            latestId = posts[0].id;
        }
    } catch (e) {
        console.error('更新エラー:', e);
    }
}

// タブが見えているときだけポーリング（負荷対策）
let timer = setInterval(checkNewPosts, 4000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(timer);
    } else {
        checkNewPosts(); // タブに戻った瞬間に即チェック
        timer = setInterval(checkNewPosts, 4000);
    }
});

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