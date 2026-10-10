<?php

if(!isset($_SESSION['form_url'])){
    exit;
}
require_once 'db.php';
require_once 'api/logincheck.php';

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


?>
<link rel="stylesheet" href="css/postcard.css">
<script src="js/tinymce/tinymce.min.js"></script>
<script>
const form = document.getElementById('postForm');
const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

function isEmpty(editor) {
    return editor.getContent({ format: 'text' }).trim() === '';
}

function updateSubmitState(editor) {
    if (!submitBtn) return;
    const empty = isEmpty(editor);
    submitBtn.disabled = empty;
    submitBtn.style.opacity = empty ? '0.5' : '1';
}

// 初期状態は無効
if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.5';
}

tinymce.init({
    selector: '#foo',
    language: 'ja',
    license_key: 'gpl',
    height: 300,
    menubar: false,
    statusbar: false,
    plugins: 'lists',
    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | forecolor',
    setup: (editor) => {
        editor.on('init input keyup change SetContent Undo Redo', () => {
            editor.save(); // textareaへ常に同期
            updateSubmitState(editor);
        });

        if (form) {
            // captureをtrueにして、他の送信処理より先に同期する
            form.addEventListener('submit', (e) => {
                editor.save();
                if (isEmpty(editor)) {
                    e.preventDefault();
                }
            }, true);
        }
    }
});

</script>
<style>
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
<div class="card">
    <form action="<?= $_SESSION['form_url'] ?>" method="post" enctype="multipart/form-data">
        <?php if(isset($_SESSION['parent_id'])):?>
            <input type="hidden" name="parent_id" value="<?= (int)$_SESSION['parent_id'] ?>">
        <?php endif;?>
            <div class="textarea-wrap">
            <textarea name="content" rows="3" placeholder="何かつぶやいてみよう！" id="foo"></textarea>
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
            <p style="color: #ffffff">フォーム送信先: <?= $_SESSION['form_url']; ?></p>
        <button type="submit">投稿する</button>
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

    const contentid = postJson.contentid;
    window.location.href = 'detail.php?contentid='+contentid;
});
        </script>
        </div>
    </form>
</div>


