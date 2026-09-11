<?php
require_once '../../db.php';
require_once '../../api/newscheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>大容量ファイルをCloudflare越しにアップロードする | TaneLog</title>

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
      <img src="../thumbnails/19.jpg" alt="記事サムネイル">
    </div>

    <div class="article-meta">
      <span class="article-category">実装解説</span>
      <span class="article-tags">
        <span class="tag">PHP</span>
        <span class="tag">JavaScript</span>
        <span class="tag">ファイルアップロード</span>
        <span class="tag">Cloudflare</span>
      </span>
    </div>

    <h1 class="article-title">大容量ファイルをCloudflare越しにアップロードする — チャンク分割アップロードの実装</h1>
    <p class="article-date">2025年7月</p>

    <hr class="article-divider">

    <div class="article-body">

      <h2>背景：なぜチャンク分割が必要なのか</h2>
      <p>TaneLogはCloudflare Tunnelを使って外部公開しています。Cloudflareの無料プランには<strong>リクエストあたり100MBの上限</strong>があるため、そのまま大きなファイルを<code>&lt;input type="file"&gt;</code>で送ると途中で切断されてしまいます。</p>
      <p>これはPHPの設定（<code>upload_max_filesize</code>や<code>post_max_size</code>）を変えても解決できない問題です。Cloudflareの制限はサーバーより手前にあるからです。</p>

      <div class="callout">
        <strong>チャンク分割アップロードとは</strong><br>
        ファイルをあらかじめ小さな塊（チャンク）に分割してから、1つずつ順番に送信する方法です。各チャンクが100MB未満であればCloudflareの制限を通過でき、サーバー側で受け取った塊を順番に結合することで元のファイルを復元します。
      </div>

      <h2>全体の流れ</h2>
      <div class="flow-diagram">ブラウザ（JS）                      サーバー（PHP）
─────────────────────────────────────────────────
1. post.php に投稿テキストをPOST  →  postsテーブルにINSERT
                                   ←  post_id を返す

2. ファイルを50MBずつに分割
   chunk 0 を upload_chunk.php へ →  tmp/{upload_id}/0.part に保存
   chunk 1 を upload_chunk.php へ →  tmp/{upload_id}/1.part に保存
   chunk N を upload_chunk.php へ →  全チャンク揃ったら結合
                                      attachmentsテーブルにINSERT

3. window.location.href = 'timeline.php'</div>

      <h2>登場するファイル</h2>
      <table class="summary">
        <tr>
          <th>ファイル</th>
          <th>役割</th>
        </tr>
        <tr>
          <td><code>post.php</code></td>
          <td>投稿テキストをDBに保存し、<code>post_id</code>をJSONで返す</td>
        </tr>
        <tr>
          <td><code>upload_chunk.php</code></td>
          <td>チャンクを受け取り、一時保存・結合・DB登録を行う</td>
        </tr>
        <tr>
          <td><code>download.php</code></td>
          <td>ファイルを50MBずつ分割してレスポンスする</td>
        </tr>
        <tr>
          <td><code>download_page.php</code></td>
          <td>ダウンロード専用ページ。進捗バーを表示しつつ自動ダウンロード</td>
        </tr>
      </table>

      <h2>1. post.php — 投稿テキストの保存</h2>
      <p>従来は<code>timeline.php</code>のPOST処理でテキストとファイルを一緒に扱っていましたが、チャンクアップロードではJSから非同期でリクエストを送るため、テキスト保存を独立したエンドポイントに切り出しました。</p>
      <p><code>lastInsertId()</code>で取得した<code>post_id</code>をJSONで返すのがポイントです。JSはこのIDを使って、後続のチャンクをどの投稿に紐づけるか指定します。</p>

      <h2>2. upload_chunk.php — チャンクの受け取りと結合</h2>
      <p>各チャンクには<code>upload_id</code>（ファイルごとにJSが生成するランダムなID）、<code>chunk_index</code>（何番目か）、<code>total_chunks</code>（全部で何個か）を一緒に送ります。</p>

      <pre><code>$upload_id = preg_replace('/[^a-f0-9]/', '', $_POST['upload_id']);</code></pre>

      <p><code>upload_id</code>はディレクトリ名として使うため、英数字以外を除去してパストラバーサルを防いでいます。</p>
      <p>受け取ったチャンクは<code>E:/learnphp_uploads/tmp/{upload_id}/0.part</code>のように連番で保存します。<code>glob()</code>で<code>*.part</code>ファイルの数を数え、<code>total_chunks</code>と一致したら結合処理に入ります。</p>

      <pre><code>$out = fopen($final_path, 'wb');
for ($i = 0; $i < $total_chunks; $i++) {
    fwrite($out, file_get_contents($tmp_dir . $i . '.part'));
    unlink($tmp_dir . $i . '.part');
}
fclose($out);
rmdir($tmp_dir);</code></pre>

      <p><code>fread</code>ではなく連番ループで結合するので、チャンクがバラバラな順序で届いても正しく復元されます。結合後は<code>finfo</code>でMIMEタイプを実ファイルから取得し、DBに登録します。</p>

      <div class="callout">
        <strong>保存先はWebルートの外に置く</strong><br>
        <code>E:/learnphp_uploads/</code>はhtdocsの外のディレクトリです。ここに置けば<code>.php</code>ファイルをアップロードされてもWebから直接実行されません。ファイルへのアクセスは必ず<code>download.php</code>経由になります。
      </div>

      <h2>3. JS側 — チャンク分割と送信</h2>
      <p><code>File.slice()</code>でファイルを50MBずつ切り出し、<code>fetch()</code>で1チャンクずつ順番に送ります。<code>await</code>で直列処理にしているので、前のチャンクが完了してから次を送ります。</p>

      <pre><code>const CHUNK_SIZE = 50 * 1024 * 1024; // 50MB

for (let i = 0; i < total_chunks; i++) {
    const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
    const cd = new FormData();
    cd.append('chunk', chunk);
    cd.append('chunk_index', i);
    // ...
    await fetch('upload_chunk.php', { method: 'POST', body: cd });
}</code></pre>

      <h2>4. download.php — チャンク分割ダウンロード</h2>
      <p>ダウンロードも同じ問題があります。100MB以上のファイルをそのまま<code>readfile()</code>で返すとCloudflareに切断されます。そこでダウンロードも分割します。</p>
      <p><code>?id=1</code>だけのリクエストではファイル情報（サイズ・チャンク数）をJSONで返し、<code>?id=1&chunk=0</code>のリクエストでは<code>fseek()</code>でオフセットを移動して50MB分だけ読み出して返します。</p>

      <pre><code>$offset = $chunk_index * $chunk_size;
$fp = fopen($path, 'rb');
fseek($fp, $offset);
$data = fread($fp, $chunk_size);
fclose($fp);
echo $data;</code></pre>

      <h2>5. download_page.php — 進捗バーつき自動ダウンロード</h2>
      <p>共有リンクを開いた瞬間にダウンロードが始まるページです。JSでチャンクを順番に取得し、<code>Blob</code>として配列に溜めてから<code>new Blob(chunks)</code>で結合し、<code>URL.createObjectURL()</code>で仮想URLを作ってダウンロードさせます。</p>

      <pre><code>const merged = new Blob(chunks);
const url = URL.createObjectURL(merged);
const a = document.createElement('a');
a.href = url;
a.download = filename;
a.click();
URL.revokeObjectURL(url);</code></pre>

      <div class="callout">
        <strong>注意：大きなファイルはブラウザのメモリを使う</strong><br>
        チャンクをすべて<code>Blob</code>として配列に積んでから結合するため、ブラウザのメモリにファイル全体が乗ります。数GBのファイルを扱う場合はStreams APIを使った逐次書き込みが必要になります。
      </div>

      <h2>まとめ</h2>
      <ul>
        <li>Cloudflareの100MB制限はPHPの設定では回避できない。チャンク分割で迂回する</li>
        <li>アップロードはJS側で<code>File.slice()</code>、サーバー側で<code>*.part</code>ファイルを結合する</li>
        <li>ダウンロードも同様に<code>fseek()</code>で分割して返し、JS側で<code>Blob</code>を結合する</li>
        <li>保存先はWebルートの外に置き、<code>finfo</code>でMIMEを実ファイルから検証する</li>
        <li><code>upload_id</code>は<code>preg_replace</code>でサニタイズしてディレクトリトラバーサルを防ぐ</li>
      </ul>

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