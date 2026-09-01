<?php
require_once '../../api/logincheck.php';
require_once '../../db.php';
require_once '../../api/newscheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>記事タイトル | TaneLog</title>

  <!-- TaneLog 共通テーマ（timeline.php と同じ） -->
  <link rel="stylesheet" id="theme-link" href="../../css/style-light.css">
  <!-- 記事専用スタイル -->
  <link rel="stylesheet" href="../css/article.css">

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

            /*h1テキスト。このテンプレートでは、画面最上部の帯の左側に小文字で入れているテキストです。*/
            header h1 {
	            font-weight: normal;
	            margin: 0;padding: 0;
	            font-size: 0.8rem;		/*文字サイズを80%*/
	            letter-spacing: 0.1em;	/*文字間隔を少しだけ広く*/
                
            }

	        /*画面幅700px以下の追加指定*/
	        @media screen and (max-width:700px) {

	        header h1 {
	            font-size: 0.7em;
            }
           }/*追加指定ここまで*/

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-8px); }
                to   { opacity: 1; transform: translateY(0); }
            }
  </style>
</head>
<body>

  <!-- ════ timeline.php と同一のヘッダー ════ -->
  <header>
    <div id="header-top">
      <h1><?= $recentnewsdate; ?> <?= $recentnews; ?><a href="../../<?= $recentnewsurl ?>">詳細</a></h1>
    </div>
    <div class="header-main-row">
      <div>
        <a href="../../timeline.php"><img src="../../img/tanelog.png" alt="TaneLog" id="headerLogo"></a>
      </div>
      <div class="header-actions">
        <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="テーマ切り替え">🌙</button>
        <button class="menu-btn" id="menuBtn">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <?php require '../../sidemenu.php'; ?>
  <!-- ════════════════════════════════════════ -->

  <main class="article-layout">

    <!-- ───── サムネイル ───── -->
    <div class="article-thumbnail">
      <img src="../articles/thumbnails/1.png" alt="記事サムネイル">
    </div>

    <!-- ───── メタ情報 ───── -->
    <div class="article-meta">
      <span class="article-category">PHP基礎</span>
      <span class="article-tags">
        <span class="tag">PDO</span>
        <span class="tag">MySQL</span>
        <span class="tag">入門</span>
      </span>
    </div>

    <!-- ───── タイトル ───── -->
    <h1 class="article-title">PDOを使ったデータベース接続入門</h1>
    <p class="article-date">2025年6月21日</p>

    <hr class="article-divider">

    <!-- ───── 本文（ここを自分で書く） ───── -->
    <div class="article-body">

      <h2>PDOとは</h2>
      <p>PDO（PHP Data Objects）は、PHPからデータベースに接続するための標準的なインターフェースです。MySQL・SQLiteなど複数のDBを同じ書き方で扱えます。</p>

      <h2>接続の基本</h2>
      <p>まず <code>PDO</code> クラスのインスタンスを作ります。</p>

      <pre><code>$dsn = 'mysql:host=localhost;dbname=learnphp;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);</code></pre>

      <p>接続に失敗すると <code>PDOException</code> が投げられるので、<code>try/catch</code> で囲むのがおすすめです。</p>

      <h2>データの取得</h2>
      <p>プリペアドステートメントを使うとSQLインジェクションを防げます。</p>

      <pre><code>$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);</code></pre>

      <!-- 記事内画像を使う場合 -->
      <figure class="article-figure">
        <img src="../articles/img/pdo-flow.png" alt="PDOの処理フロー">
        <figcaption>PDOを使ったクエリ実行の流れ</figcaption>
      </figure>

      <h2>まとめ</h2>
      <ul>
        <li>PDOはDBを抽象化する標準インターフェース</li>
        <li>プリペアドステートメントでSQLインジェクション対策</li>
        <li><code>PDO::ERRMODE_EXCEPTION</code> でエラーを例外として受け取る</li>
      </ul>

    </div>
    <!-- ───── 本文ここまで ───── -->

    <hr class="article-divider">
    <a class="back-link" href="../">← 記事一覧に戻る</a>

  </main>

  <footer class="site-footer">
    <p>&copy; 2025 TaneLog</p>
  </footer>

  <!-- ════ timeline.php と同一のテーマ切り替えJS ════ -->
  <script>
    const menuBtn       = document.getElementById('menuBtn');
    const closeBtn      = document.getElementById('closeBtn');
    const sideMenu      = document.getElementById('sideMenu');
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const headerLogo    = document.getElementById('headerLogo');
    const themeLink     = document.getElementById('theme-link');

    function updateToggleBtnIcon(theme) {
      themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
    }

    const currentTheme = localStorage.getItem('theme') || 'light';
    updateToggleBtnIcon(currentTheme);
    if (headerLogo) headerLogo.src = currentTheme === 'dark' ? '../../img/logo_dark.png' : '../../img/tanelog.png';
    if (themeLink)  themeLink.href  = currentTheme === 'dark' ? '../../css/style-dark.css' : '../../css/style-light.css';

    themeToggleBtn.addEventListener('click', () => {
      const isDark   = themeLink.href.includes('style-dark.css');
      const newTheme = isDark ? 'light' : 'dark';
      themeLink.href  = newTheme === 'dark' ? '../../css/style-dark.css' : '../../css/style-light.css';
      updateToggleBtnIcon(newTheme);
      if (headerLogo) headerLogo.src = newTheme === 'dark' ? '../img/logo_dark.png' : '../img/tanelog.png';
      localStorage.setItem('theme', newTheme);
    });

    menuBtn.addEventListener('click',  () => sideMenu.classList.add('active'));
    closeBtn.addEventListener('click', () => sideMenu.classList.remove('active'));
  </script>
  <!-- ════════════════════════════════════════════ -->

</body>
</html>