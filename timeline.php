<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
if(isset($_SESSION['view_contentid'])){
    header('Location: #'.$_SESSION['view_contentid']);
    unset($_SESSION['view_contentid']);
    exit;
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
    header('Location: logout.php');
    exit;
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

// 投稿内のＵＲＬをリンク化
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
        <script src="js/theme.js"></script>
        <script src="js/sidemenu.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>タイムライン - TaneLog</title>
        <link rel="stylesheet" href="css/postcard.css">
        <link rel="icon" href="/favicon.ico" sizes="any">        
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#fef8e5">
        <link rel="apple-touch-icon" href="/icon/icon-192.png">
        <script src="js/tinymce/tinymce.min.js"></script>
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
    <?php $_SESSION['form_url'] = "timeline.php";?>
    <?php require_once 'postform.php';?>
<script>

</script>

            <div class="post-list" id="postList">
                <?php foreach ($posts as $p): ?>
                    <div class="post-card" id="<?= $p['content_id'] ?>">

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
            </div>
        </main>
<script>

const textarea = document.querySelector('textarea[name="content"]');



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
        <p class="post-content">${linkifyText(p.content)}</p>
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
let timer = setInterval(checkNewPosts, 10000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(timer);
    } else {
        checkNewPosts(); // タブに戻った瞬間に即チェック
        timer = setInterval(checkNewPosts, 10000);
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