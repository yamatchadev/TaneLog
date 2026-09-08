<?php
require_once __DIR__.'/../api/logincheck.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../api/newscheck.php';

// ── 検索パラメータ取得 ──
$keyword = trim($_GET['q']   ?? '');
$tag     = trim($_GET['tag'] ?? '');

// ── 記事一覧取得（動的WHERE） ──
$sql    = "SELECT * FROM articles WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $sql     .= " AND title LIKE ?";
    $params[] = "%{$keyword}%";
}

if ($tag !== '') {
    $sql     .= " AND tags LIKE ?";
    $params[] = "%{$tag}%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// ── タグ一覧生成（全記事のtagsから重複排除） ──
$all = $pdo->query("SELECT tags FROM articles WHERE tags IS NOT NULL AND tags != ''")->fetchAll();
$tag_list = [];
foreach ($all as $row) {
    foreach (explode(',', $row['tags']) as $t) {
        $t = trim($t);
        if ($t !== '') $tag_list[] = $t;
    }
}
$tag_list = array_values(array_unique($tag_list));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>記事一覧 | LearnPHP</title>

  <link rel="stylesheet" id="theme-link" href="../css/style-light.css">
  <link rel="stylesheet" href="css/article.css">
  <script src="../js/sidemenu.js"></script>
  <script src="../js/theme.js"></script>
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
    }
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
      transition: background-color 0.3s, border-color 0.3s;
    }
    .header-main-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }
    header img { margin-top: 5px; height: 45px; }
    .header-actions { display: flex; align-items: center; gap: 15px; }
    .theme-toggle-btn {
      background: none; border: none; font-size: 20px;
      cursor: pointer; padding: 4px; line-height: 1; user-select: none;
    }
    .theme-toggle-btn:hover { background: none; transform: scale(1.1); }
    .menu-btn {
      width: 30px; height: 24px; background: none; border: none;
      cursor: pointer; display: flex; flex-direction: column;
      justify-content: space-between; padding: 0;
    }
    .menu-btn span {
      display: block; width: 100%; height: 3px;
      background-color: var(--text-color); border-radius: 2px;
    }
    header h1 {
      font-weight: normal; margin: 0; padding: 0;
      font-size: 0.8rem; letter-spacing: 0.1em;
    }
    @media screen and (max-width: 700px) {
      header h1 { font-size: 0.7em; }
    }
  </style>
</head>
<body>

  <header>
    <div id="header-top">
      <h1><?= $recentnewsdate ?> <?= $recentnews ?><a href="../news.php">詳細</a></h1>
    </div>
    <div class="header-main-row">
      <div>
        <a href="../timeline.php"><img src="../img/logo_light.png" alt="LearnPHP" id="headerLogo"></a>
      </div>
      <div class="header-actions">
        <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="テーマ切り替え">🌙</button>
        <button class="menu-btn" id="menuBtn">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <?php require '../sidemenu.php'; ?>

  <!-- ── ページ見出し ── -->
  <h2 class="index-heading">記事</h2>

  <!-- ── キーワード検索 ── -->
  <form class="index-search" action="index.php" method="get">
    <?php if ($tag !== ''): ?>
      <input type="hidden" name="tag" value="<?= htmlspecialchars($tag) ?>">
    <?php endif; ?>
    <input
      type="text"
      name="q"
      placeholder="キーワードで検索..."
      value="<?= htmlspecialchars($keyword) ?>"
    >
    <button type="submit">検索</button>
  </form>

  <div class="index-layout">

    <!-- ── 左サイドバー ── -->
    <nav class="index-sidebar">
      <div class="sidebar-label">タグ</div>
      <ul class="sidebar-tag-list">
        <li>
          <a href="index.php<?= $keyword !== '' ? '?q='.urlencode($keyword) : '' ?>"
             class="<?= $tag === '' ? 'active' : '' ?>">
            全て
          </a>
        </li>
        <?php foreach ($tag_list as $t): ?>
        <li>
          <a href="index.php?tag=<?= urlencode($t) ?><?= $keyword !== '' ? '&q='.urlencode($keyword) : '' ?>"
             class="<?= $tag === $t ? 'active' : '' ?>">
            <?= htmlspecialchars($t) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <!-- ── 記事グリッド ── -->
    <main class="index-main">
      <div class="article-grid">

        <?php if (empty($articles)): ?>
          <div class="no-results">記事が見つかりませんでした。</div>

        <?php else: ?>
          <?php foreach ($articles as $a):
            $thumb_path = __DIR__ . '/thumbnails/' . $a['id'] . '.png';
            $has_thumb  = file_exists($thumb_path);
            $tags_arr   = $a['tags'] ? array_map('trim', explode(',', $a['tags'])) : [];
            $date_str   = date('Y年n月j日', strtotime($a['created_at']));
          ?>
          <a class="article-card" href="contents/<?= (int)$a['id'] ?>.php">

            <?php if ($has_thumb): ?>
              <img
                class="card-thumb"
                src="thumbnails/<?= (int)$a['id'] ?>.png"
                alt="<?= htmlspecialchars($a['title']) ?>"
              >
            <?php else: ?>
              <div class="card-thumb-placeholder">📄</div>
            <?php endif; ?>

            <div class="card-body">
              <div class="card-title"><?= htmlspecialchars($a['title']) ?></div>

              <?php if (!empty($tags_arr)): ?>
              <div class="card-tags">
                <?php foreach ($tags_arr as $t): ?>
                  <span class="tag"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <div class="card-date"><?= $date_str ?></div>
            </div>

          </a>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </main>

  </div>

  <footer class="site-footer">
    <p>&copy; 2026 TaneLog</p>
  </footer>

</body>
</html>
