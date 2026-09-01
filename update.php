<?php
$page = basename(__FILE__);
require_once 'api/logincheck.php';
require_once 'db.php';

$message = '';
$messageType = 'info'; // 'info' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_SESSION['user_id'];
    
    // ── username（ユーザーID）更新 ──────────────────────────
    $newusername = trim($_POST['username'] ?? '');
    if($newusername !== '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$newusername]);
        $count = $stmt->fetchColumn();
        if($count > 0) {
            $message = "ユーザー名「".$newusername."」は既に使われています。";
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$newusername, $id]);
            $message = "ユーザーIDを更新しました。";              
        }
    }
    
    // ── nickname更新 ──────────────────────────
    $newnickname = trim($_POST['nickname'] ?? '');
    if ($newnickname !== '') {
        $stmt = $pdo->prepare("UPDATE users SET nickname = ? WHERE id = ?");
        $stmt->execute([$newnickname, $id]);
        $_SESSION['user_nickname'] = htmlspecialchars($newnickname);
        $message = ($message !== '') ? $message . " / ニックネームを更新しました。" : "ニックネームを更新しました。";
    }

    // ── profile_statement（プロフィール文）更新 ──────────────────────────
    $newprofile = isset($_POST['profile_statement']) ? trim($_POST['profile_statement']) : '';
    if ($newprofile !== '') {
        $stmt = $pdo->prepare("UPDATE users SET profile_statement = ? WHERE id = ?");
        $stmt->execute([$newprofile, $id]);
        $message = ($message !== '') ? $message . " / プロフィール文を更新しました。" : "プロフィール文を更新しました。";
    }

    // ── icon_path更新 ──────────────────────────────
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['icon'];
        $maxSize  = 2 * 1024 * 1024; // 2MB
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $extMap   = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowed)) {
            $message = "画像ファイル（JPG / PNG / GIF / WebP）のみ使用できます。";
            $messageType = 'error';
        } elseif ($file['size'] > $maxSize) {
            $message = "ファイルサイズは2MB以内にしてください。";
            $messageType = 'error';
        } else {
            $ext      = $extMap[$mimeType];
            $filename = 'icon_' . $id . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/icons/';
            $savePath  = $uploadDir . $filename;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach (['jpg','png','gif','webp'] as $e) {
                $old = $uploadDir . 'icon_' . $id . '.' . $e;
                if (file_exists($old)) unlink($old);
            }

            if (move_uploaded_file($file['tmp_name'], $savePath)) {
                $iconPath = 'uploads/icons/' . $filename;
                $stmt = $pdo->prepare("UPDATE users SET icon_path = ? WHERE id = ?");
                $stmt->execute([$iconPath, $id]);
                $_SESSION['icon_path'] = $iconPath;
                $message = ($message !== '') ? $message . " アイコンも更新しました。" : "アイコンを更新しました。";
            } else {
                $message = "アイコンの保存に失敗しました。";
                $messageType = 'error';
            }
        }
    }
}

// 現在のアイコンパスをDBから取得
$stmt = $pdo->prepare("SELECT icon_path, profile_statement FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRow = $stmt->fetch();
$currentIcon = $userRow['icon_path'] ?? '';
$currentProfile = $userRow['profile_statement'] ?? '';

$iconSrc = ($currentIcon && file_exists(__DIR__ . '/' . $currentIcon))
    ? htmlspecialchars($currentIcon)
    : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_nickname'] ?? 'U') . '&background=4F5D95&color=fff';

// 現在のユーザーIDを取得
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$username = $stmt->fetchColumn();

if ($username === false) {
    $username = ''; 
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール変更 - TaneLog</title>
    <link rel="stylesheet" id="theme-link" href="css/style-light.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.getElementById('theme-link').href = savedTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
    </script>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 800px;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            margin: 15px;
        }
        h2 {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* 左右分割（2カラム）を実現するFlexコンテナ */
        .form-container {
            display: flex;
            gap: 40px;
            margin-bottom: 24px;
        }
        
        /* 左半分と右半分のボックス幅を均等に */
        .form-left, .form-right {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: var(--muted-color, #718096);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            background-color: var(--card-bg);
            color: inherit;
        }
        textarea {
            resize: vertical;
            min-height: 120px; /* PC・スマホ問わず入力しやすい高さに少し広げました */
            font-family: inherit;
        }
        
        /* アイコンプレビューエリア */
        .icon-preview-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        #icon-preview {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
            background: #eee;
        }
        .icon-file-label {
            display: inline-block;
            padding: 8px 16px;
            background: #eef0f8;
            color: #4F5D95;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border: 1px solid #c9cde8;
        }
        .icon-file-label:hover {
            background: #dde0f5;
        }
        #icon-filename {
            font-size: 13px;
            color: var(--muted-color, #718096);
            margin-top: 6px;
        }
        input[type="file"] {
            display: none;
        }

        /* ボックス下端の配置用Flexコンテナ */
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        .submit-btn {
            width: auto;
            min-width: 150px;
            background-color: var(--button-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .submit-btn:hover { 
            opacity: 0.9; 
        }

        .msg {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .msg.info  { background:#ebf8ff; color:#2b6cb0; border:1px solid #bee3f8; }
        .msg.error { background:#fff5f5; color:#c53030; border:1px solid #fed7d7; }

        /* ── スマホ・タブレット（画面幅650px以下）用のレスポンシブ調整 ── */
        @media (max-width: 650px) {
            .card {
                padding: 20px; /* カード内の余白を少し詰めて画面を広く */
            }
            .form-container {
                flex-direction: column; /* 左右並びから縦並びに変更 */
                gap: 20px; /* 縦並びの隙間を最適化 */
            }
            textarea {
                min-height: 140px; /* スマホで長文が見やすくなるよう高さをゆったり確保 */
            }
            .form-footer {
                flex-direction: column-reverse; /* スマホではボタンを上に、戻るリンクを下にして押しやすく */
                gap: 16px;
                align-items: stretch; /* ボタンを横幅いっぱいに広げる */
                text-align: center;
            }
            .submit-btn {
                width: 100%; /* 適用ボタンをタップしやすいよう全幅化 */
                padding: 14px;
            }
        }
    </style>
</head>
<body>
<div class="card">
    <h2>プロフィール変更</h2>

    <?php if ($message !== ''): ?>
        <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="update.php" method="post" enctype="multipart/form-data">
        
        <div class="form-container">
            
            <div class="form-left">
                <div class="section-title">アイコン</div>
                <div class="icon-preview-wrap">
                    <img id="icon-preview" src="<?= $iconSrc ?>" alt="アイコンプレビュー">
                    <div>
                        <label class="icon-file-label" for="icon">画像を選択</label>
                        <div id="icon-filename">未選択</div>
                    </div>
                </div>
                <input type="file" id="icon" name="icon" accept="image/*">

                <div style="margin-top: 20px;"></div>

                <div class="section-title">ユーザーID</div>
                <div class="form-group">
                    <label>現在のユーザーID</label>
                    <input type="text" value="<?= htmlspecialchars($username); ?>" readonly disabled>
                </div>
                <div class="form-group">
                    <label>新しいユーザーID</label>
                    <input type="text" name="username" placeholder="新しいユーザーIDを入力">
                </div>
            </div>
            
            <div class="form-right">
                <div class="section-title">ニックネーム</div>
                <div class="form-group">
                    <label>現在のニックネーム</label>
                    <input type="text" value="<?= htmlspecialchars($_SESSION['nickname'] ?? '') ?>" readonly disabled>
                </div>
                <div class="form-group">
                    <label>新しいニックネーム</label>
                    <input type="text" name="nickname" placeholder="新しい名前を入力">
                </div>

                <div class="section-title">プロフィール文</div>
                <div class="form-group">
                    <textarea name="profile_statement" placeholder="自己紹介文を入力してください"><?= htmlspecialchars($currentProfile) ?></textarea>
                </div>
            </div>

        </div>

        <div class="form-footer">
            <a href="timeline.php" class="back-link">← タイムラインに戻る</a>
            <button type="submit" class="submit-btn">変更を適用</button>
        </div>
    </form>
</div>

<script>
    const fileInput   = document.getElementById('icon');
    const preview     = document.getElementById('icon-preview');
    const filenameTag = document.getElementById('icon-filename');

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        filenameTag.textContent = file.name;

        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
    });
</script>
</body>
</html>