<?php
require_once '../../db.php';
require_once '../../api/newscheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>投稿本文のURLを自動でリンク化する | TaneLog</title>

  <link rel="stylesheet" id="theme-link" href="../../css/style-light.css">
  <link rel="stylesheet" href="../css/article.css">

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
      transition: background-color 0.3s;
    }
    .side-menu {
      position: fixed; top: 0; right: -300px; width: 260px; height: 100%;
      background-color: #2d3748; transition: right 0.3s ease;
      z-index: 99; box-shadow: -4px 0 10px rgba(0,0,0,0.1);
    }
    .side-menu.active { right: 0; }
    .menu-close-wrapper { display: flex; justify-content: flex-end; padding: 15px 20px; }
    .close-btn {
      background: none; border: none; color: #a0aec0;
      font-size: 28px; cursor: pointer; line-height: 1; padding: 0;
    }
    .close-btn:hover { color: #fff; }
    .side-menu ul { list-style: none; padding: 0; margin: 0; }
    .side-menu ul li a {
      display: block; padding: 16px 24px; color: #e2e8f0;
      text-decoration: none; font-size: 16px;
      border-bottom: 1px solid #4a5568; transition: background 0.2s;
    }
    .side-menu ul li a:hover { background-color: #4a5568; color: #fff; }
    .side-menu ul li.danger a { color: #feb2b2; }
    .side-menu ul li.danger a:hover { background-color: #9b2c2c; color: #fff; }
    header h1 {
      font-weight: normal; margin: 0; padding: 0;
      font-size: 0.8rem; letter-spacing: 0.1em;
    }
    @media screen and (max-width:700px) {
      header h1 { font-size: 0.7em; }
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── 記事内の補足スタイル ── */
    .callout {
      background: var(--bg-color);
      border-left: 4px solid var(--primary-color);
      border-radius: 0 8px 8px 0;
      padding: 14px 18px;
      margin: 20px 0;
      font-size: 14px;
      line-height: 1.7;
    }
    .callout strong { color: var(--primary-color); }
    .flow-diagram {
      background: var(--bg-color);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
      font-family: monospace;
      font-size: 13px;
      line-height: 2;
      overflow-x: auto;
      white-space: pre;
    }
    table.summary {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      font-size: 14px;
    }
    table.summary th, table.summary td {
      border: 1px solid var(--border-color);
      padding: 10px 14px;
      text-align: left;
    }
    table.summary th {
      background: var(--bg-color);
      color: var(--primary-color);
      font-weight: bold;
    }
  </style>
</head>
<body>

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

  <main class="article-layout">

    <div class="article-thumbnail">
      <img src="../thumbnails/25.png" alt="記事サムネイル">
    </div>

    <div class="article-meta">
      <span class="article-category">実装解説</span>
      <span class="article-tags">
        <span class="tag">PHP</span>
        <span class="tag">JavaScript</span>
        <span class="tag">UX</span>
        <span class="tag">タイムライン</span>
      </span>
    </div>

    <h1 class="article-title">投稿本文のURLを自動でリンク化する — PHPとJSで処理を揃える</h1>
    <p class="article-date">2026/09/15</p>

    <hr class="article-divider">

    <div class="article-body">

      <h2>背景：なぜ必要になったのか</h2>
      <p>TaneLogの投稿本文はこれまで、ただのテキストとして<code>htmlspecialchars</code>でエスケープされて表示されるだけでした。そのため投稿にURLを含めても、ユーザーはそれをコピーして手動でブラウザに貼り付けるしかありませんでした。</p>
      <p>投稿を見た人がその場でリンク先に飛べるよう、本文中のURLを自動で<code>&lt;a&gt;</code>タグに変換する機能を追加することにしました。</p>

      <div class="callout">
        <strong>表示箇所が2つあることが設計のポイント</strong><br>
        TaneLogのタイムラインは、ページ読み込み時にPHPが表示する投稿と、4秒間隔のポーリングでJavaScriptが追加する投稿の2種類があります。片方だけにURLリンク化を実装すると、投稿によってリンクになったりならなかったりする不整合が起きてしまうため、PHP側とJS側の両方に同じロジックを実装する必要がありました。
      </div>

      <h2>全体の流れ</h2>
      <div class="flow-diagram">投稿本文（生のテキスト）
─────────────────────────────────────────────
1. HTMLエスケープ
   → <, >, " などを無害化し、投稿者が仕込んだ
     タグやスクリプトが実行されないようにする

2. 正規表現でURLを検出
   → /https?:\/\/[^\s<]+/ にマッチする箇所を探す

3. 末尾の句読点・記号をリンクの外に出す
   → 「詳細はこちらhttps://example.com。」のような
     文末URLで、句点までリンクに含まれるのを防ぐ

4. a href="..." target="_blank" rel="noopener noreferrer"
   に置換して出力

   PHP側  → linkifyContent()  … 初期表示（timeline.php）
   JS側   → linkifyText()     … ポーリング追加分（buildPostCard）</div>

      <h2>登場する関数・ファイル</h2>
      <table class="summary">
        <tr>
          <th>関数・ファイル</th>
          <th>役割</th>
        </tr>
        <tr>
          <td><code>linkifyContent()</code>（PHP / timeline.php）</td>
          <td>初期表示時、DBから取得した投稿本文をエスケープ＋URLリンク化して出力</td>
        </tr>
        <tr>
          <td><code>linkifyText()</code>（JS / timeline.php内スクリプト）</td>
          <td>ポーリングで追加される投稿本文を、同じロジックでエスケープ＋URLリンク化</td>
        </tr>
        <tr>
          <td><code>buildPostCard()</code>（JS）</td>
          <td>投稿カードのHTML文字列を組み立てる関数。本文部分で<code>linkifyText()</code>を呼び出す</td>
        </tr>
      </table>

      <h2>1. エスケープを先に行う理由</h2>
      <p>URLをリンク化する処理よりも先に、必ずHTMLエスケープを行っています。もし先にURL検出・置換をしてしまうと、投稿者が本文に<code>&lt;script&gt;</code>のようなタグを含めていた場合、それがそのまま<code>innerHTML</code>やPHPの出力に混入し、XSS（クロスサイトスクリプティング）につながる恐れがあるためです。</p>

      <pre><code>// PHP側
$escaped = htmlspecialchars($rawText, ENT_QUOTES, 'UTF-8');

// JS側
const div = document.createElement('div');
div.textContent = rawText;
let escaped = div.innerHTML;</code></pre>

      <p>JS側では、要素に<code>textContent</code>で文字列を代入してから<code>innerHTML</code>を読み出すことで、ブラウザ標準の仕組みを使って安全にエスケープしています。</p>

      <h2>2. 正規表現によるURL検出とリンク化</h2>
      <p>エスケープ後の文字列に対して、<code>https?:\/\/[^\s<]+</code>というパターンでURLを検出します。<code>http://</code>または<code>https://</code>から始まり、空白文字か<code>&lt;</code>が現れるまでを1つのURLとみなす、というシンプルなルールです。</p>

      <pre><code>$pattern = '/(https?:\/\/[^\s<]+)/i';
$escaped = preg_replace_callback($pattern, function ($matches) {
    $url = $matches[1];
    // （末尾処理は後述）
    return '&lt;a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="front-link"&gt;' . $url . '&lt;/a&gt;';
}, $escaped);</code></pre>

      <p>PHPでは<code>preg_replace_callback</code>を、JSでは<code>String.replace()</code>にコールバック関数を渡す形で、マッチしたURLごとに置換処理を行っています。単純な<code>preg_replace</code>ではなくコールバック形式にしているのは、次に説明する「末尾の記号処理」をURLごとに個別に行う必要があるためです。</p>

      <div class="callout">
        <strong><code>class="front-link"</code>を付けている理由</strong><br>
        投稿カード全体には<code>post-card-link</code>という透明なリンクが重ねてあり、カードのどこをクリックしても詳細ページに飛ぶようになっています。生成した<code>&lt;a&gt;</code>タグにも<code>front-link</code>クラス（クリックイベントの伝播を止めて個別リンクとして機能させるためのクラス）を付けないと、本文中のURLをクリックしたつもりが詳細ページに遷移してしまいます。
      </div>

      <h2>3. 文末の句読点を巻き込まないようにする</h2>
      <p>「資料はこちらです→https://example.com。」のように文末にURLがある場合、正規表現は句点「。」まで拾ってしまいます。これを避けるため、マッチしたURLの末尾から句読点・記号を切り離し、リンクの外側に出す処理を加えています。</p>

      <pre><code>$trailing = '';
if (preg_match('/[).,!?、。」』]+$/u', $url, $tMatch)) {
    $trailing = $tMatch[0];
    $url = substr($url, 0, -mb_strlen($trailing));
}
return '&lt;a href="' . $url . '" ...&gt;' . $url . '&lt;/a&gt;' . $trailing;</code></pre>

      <p>句読点をURLの外に出してから<code>&lt;a&gt;</code>タグで囲むことで、リンク先には正しいURLだけが渡り、見た目上も句点がリンクの下線に巻き込まれません。</p>

      <h2>4. PHP・JS双方への組み込み</h2>
      <p>PHP側は、これまで<code>htmlspecialchars($p['content'])</code>と直接出力していた箇所を<code>linkifyContent($p['content'])</code>の呼び出しに置き換えるだけです。エスケープ処理を関数の内部に持たせているため、呼び出し側で二重にエスケープする必要はありません。</p>

      <pre><code>&lt;p class="post-content"&gt;&lt;?= linkifyContent($p['content']) ?&gt;&lt;/p&gt;</code></pre>

      <p>JS側も同様に、投稿カードのテンプレート文字列内で本文をそのまま埋め込んでいた箇所を<code>linkifyText(p.content)</code>の呼び出しに置き換えています。</p>

      <pre><code>&lt;p class="post-content"&gt;${linkifyText(p.content)}&lt;/p&gt;</code></pre>

      <h2>ポイントまとめ</h2>
      <ul>
        <li>URLリンク化より先に必ずHTMLエスケープを行い、投稿本文からのXSSを防止</li>
        <li>PHP（初期表示）とJS（ポーリング追加）に同じロジックを実装し、表示の不整合をなくす</li>
        <li>生成したリンクには<code>target="_blank" rel="noopener noreferrer"</code>を付与し、安全に新規タブで開けるようにする</li>
        <li><code>front-link</code>クラスを付与し、投稿カード全体のリンクとURLリンクのクリックが競合しないようにする</li>
        <li>文末の句読点・記号をURLから切り離し、意図しない文字までリンクに含まれるのを防止</li>
      </ul>

      <h2>今後の課題</h2>
      <p>現状は<code>http://</code>・<code>https://</code>から始まるURLのみを対象としており、「www.example.com」のようにスキームを省略した記法には対応していません。また、メールアドレスの自動リンク化（<code>mailto:</code>）や、ハッシュタグ・メンションのリンク化といった拡張も今後検討していく予定です。</p>

    </div>

    <hr class="article-divider">
    <a class="back-link" href="../">← 記事一覧に戻る</a>

  </main>

  <footer class="site-footer">
    <p>&copy; 2025 TaneLog</p>
  </footer>

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
    if (headerLogo) headerLogo.src = currentTheme === 'dark' ? '../../img/logo_dark.png' : '../../img/tanelog.png';
    if (themeLink)  themeLink.href  = currentTheme === 'dark' ? '../../css/style-dark.css' : '../../css/style-light.css';

    themeToggleBtn.addEventListener('click', () => {
      const isDark   = themeLink.href.includes('style-dark.css');
      const newTheme = isDark ? 'light' : 'dark';
      themeLink.href  = newTheme === 'dark' ? '../../css/style-dark.css' : '../../css/style-light.css';
      updateToggleBtnIcon(newTheme);
      if (headerLogo) headerLogo.src = newTheme === 'dark' ? '../../img/logo_dark.png' : '../../img/tanelog.png';
      localStorage.setItem('theme', newTheme);
    });

    menuBtn.addEventListener('click',  () => sideMenu.classList.add('active'));
    closeBtn.addEventListener('click', () => sideMenu.classList.remove('active'));
  </script>

</body>
</html>
