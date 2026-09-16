<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../api/newscheck.php';

// ── 検索パラメータ取得 ──
$keyword = trim($_GET['q']   ?? '');
$tag     = trim($_GET['tag'] ?? '');

// ── 記事一覧取得（動的WHERE）──
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
$article = $stmt->fetchAll();

// ── サイドバー用タグ一覧（全記事から重複排除）──
$all_tags_stmt = $pdo->query("SELECT tags FROM articles WHERE tags IS NOT NULL AND tags != ''");
$tag_list = [];
foreach ($all_tags_stmt->fetchAll() as $row) {
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
  <title>記事 | LearnPHP</title>

  <link rel="stylesheet" id="theme-link" href="/../css/style-light.css">
  <link rel="stylesheet" href="css/article.css">

  <style>
    /* ── timeline.php から持ってきたヘッダー・サイドメニュー用スタイル ── */
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


  <?php require '../header.php'; ?>
  <!-- ════════════════════════════════════════ -->

  <!-- ページ見出し -->
  <h2 class="index-heading">記事</h2>

  <!-- キーワード検索バー -->
  <form class="index-search" action="" method="get">
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

  <!-- メインレイアウト（サイドバー＋グリッド） -->
  <div class="index-layout">

    <!-- ── 左サイドバー ── -->
    <aside class="index-sidebar">
      <p class="sidebar-label">タグ</p>
      <ul class="sidebar-tag-list">
        <li>
          <a href="?"
            class="<?= ($tag === '' && $keyword === '') ? 'active' : '' ?>">
            全て
          </a>
        </li>
        <?php foreach ($tag_list as $t): ?>
          <li>
            <a href="?tag=<?= urlencode($t) ?>"
              class="<?= ($tag === $t) ? 'active' : '' ?>">
              <?= htmlspecialchars($t) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <!-- ── 右：記事グリッド ── -->
    <main class="index-main">
      <div class="article-grid">
        <?php if (empty($article)): ?>
          <p class="no-results">記事が見つかりませんでした。</p>
        <?php else: ?>
          <?php foreach ($article as $a): ?>
            <?php
              $thumb_path = __DIR__ . '/thumbnail/' . $a['id'] . '.png';
              $thumb_src  = file_exists($thumb_path)
                ? 'thumbnail/' . $a['id'] . '.png'
                : null;
              $tags = array_filter(array_map('trim', explode(',', $a['tags'] ?? '')));
              $date = date('Y年m月d日', strtotime($a['created_at']));
            ?>
            <a class="article-card" href="content/<?= $a['id'] ?>.php">
              <?php if ($thumb_src): ?>
                <img class="card-thumb" src="<?= htmlspecialchars($thumb_src) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
              <?php else: ?>
                <div class="card-thumb-placeholder">📄</div>
              <?php endif; ?>

              <div class="card-body">
                <p class="card-title"><?= htmlspecialchars($a['title']) ?></p>
                <?php if (!empty($tags)): ?>
                  <div class="card-tags">
                    <?php foreach ($tags as $t): ?>
                      <span class="tag"><?= htmlspecialchars($t) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <p class="card-date"><?= $date ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>

  </div><!-- /.index-layout -->

  <footer class="site-footer">
    <p>&copy; 2025 LearnPHP</p>
  </footer>

  <!-- ════ timeline.php と同一のテーマ切り替えJS ════ -->
  <script>
    const menuBtn        = document.getElementById('menuBtn');
    const closeBtn       = document.getElementById('closeBtn');
    const sideMenu       = document.getElementById('sideMenu');
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const headerLogo     = document.getElementById('headerLogo');
    const themeLink      = document.getElementById('theme-link');

    function updateToggleBtnIcon(theme) {
      themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
    }

    const currentTheme = localStorage.getItem('theme') || 'light';
    updateToggleBtnIcon(currentTheme);
    if (headerLogo) headerLogo.src = currentTheme === 'dark' ? '../img/logo_dark.png' : '../img/logo_light.png';
    if (themeLink)  themeLink.href  = currentTheme === 'dark' ? '../css/style-dark.css' : '../css/style-light.css';

    themeToggleBtn.addEventListener('click', () => {
      const isDark   = themeLink.href.includes('style-dark.css');
      const newTheme = isDark ? 'light' : 'dark';
      themeLink.href  = newTheme === 'dark' ? '../css/style-dark.css' : '../css/style-light.css';
      updateToggleBtnIcon(newTheme);
      if (headerLogo) headerLogo.src = newTheme === 'dark' ? '../img/logo_dark.png' : '../img/logo_light.png';
      localStorage.setItem('theme', newTheme);
    });

    menuBtn.addEventListener('click',  () => sideMenu.classList.add('active'));
    closeBtn.addEventListener('click', () => sideMenu.classList.remove('active'));
  </script>

</body>
</html>