<?php
require_once 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: timeline.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    header('Location: timeline.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <script src="js/theme.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダウンロード - <?= htmlspecialchars($file['original_name']) ?></title>
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
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            width: 90%;
            max-width: 480px;
            text-align: center;
        }
        .filename {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 8px;
            word-break: break-all;
        }
        .filesize {
            font-size: 13px;
            color: #a0aec0;
            margin-bottom: 24px;
        }
        .progress-wrap {
            background: var(--border-color);
            border-radius: 999px;
            height: 12px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-bar {
            height: 100%;
            width: 0%;
            background: var(--primary-color);
            border-radius: 999px;
            transition: width 0.2s ease;
        }
        .progress-label {
            font-size: 13px;
            color: #a0aec0;
            margin-bottom: 20px;
        }
        .status {
            font-size: 15px;
            color: var(--text-color);
        }
        .back-link {
            display: inline-block;
            margin-top: 24px;
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="filename"><?= htmlspecialchars($file['original_name']) ?></div>
    <div class="filesize"><?= number_format($file['file_size'] / 1024 / 1024, 1) ?> MB</div>
    <div class="status" id="status">ダウンロードを開始しています</div>

    <a href="timeline.php" class="back-link">← タイムラインに戻る</a>
</div>

<script>
const FILE_ID  = <?= (int)$file['id'] ?>;
const status   = document.getElementById('status');

// ネイティブダウンロードを発火
window.location.href = `download.php?id=${FILE_ID}`;
status.textContent = 'ダウンロードを開始しました。このままお待ちください。';
</script>
</body>
</html>