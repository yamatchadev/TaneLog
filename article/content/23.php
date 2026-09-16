<?php
require_once '../../db.php';
require_once '../../api/newscheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>フォロワーへのプッシュ通知を実装する | TaneLog</title>

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
  <?php require '../../header.php'; ?>

  <main class="article-layout">

    <div class="article-thumbnail">
      <img src="../thumbnail/23.png" alt="記事サムネイル">
    </div>

    <div class="article-meta">
      <span class="article-category">実装解説</span>
      <span class="article-tags">
        <span class="tag">PHP</span>
        <span class="tag">PWA</span>
        <span class="tag">Push API</span>
        <span class="tag">通知</span>
      </span>
    </div>

    <h1 class="article-title">フォロワーへのプッシュ通知を実装する — PWA・VAPID・CLI迂回の記録</h1>
    <p class="article-date">2026/09/16</p>

    <hr class="article-divider">

    <div class="article-body">

      <h2>背景：なぜ必要になったのか</h2>
      <p>TaneLogをPWA化しホーム画面から起動できるようにしたのに続き、「フォローしている人が投稿したら知らせてほしい」という要望に応えるため、Web Push通知を実装しました。ブラウザを開いていない状態でも端末に通知が届く仕組みです。</p>

      <div class="callout">
        <strong>関わる要素が多い機能だった</strong><br>
        Push通知はフロントエンド（Service Worker・通知許可）、バックエンド（購読情報の保存・VAPID鍵での送信）、そしてWindows/XAMPP特有の環境設定が絡み合う機能で、実装よりも環境構築の方に時間がかかりました。
      </div>

      <h2>全体の流れ</h2>
      <div class="flow-diagram">① ブラウザで通知を許可
   → Push Subscription（endpoint・鍵）を取得

② save-subscription.php
   → Subscriptionをpush_subscriptionsテーブルに保存

③ 投稿発生（post.php）
   → フォロワーのuser_idをfollowsテーブルから取得
   → sendPushNotification()を呼び出す

④ notify_function.php
   → CLI版PHPを別プロセスとして起動（Apache側のcurl問題を回避）

⑤ send_notification_cli.php
   → 対象ユーザーのSubscriptionを取得し、
     minishlink/web-push で実際に送信

⑥ ブラウザのService Worker
   → pushイベントを受信して通知を表示</div>

      <h2>登場する関数・ファイル</h2>
      <table class="summary">
        <tr>
          <th>関数・ファイル</th>
          <th>役割</th>
        </tr>
        <tr>
          <td><code>push-notifications.js</code></td>
          <td>通知許可のリクエストとPush Subscriptionの生成、サーバーへの送信</td>
        </tr>
        <tr>
          <td><code>sw.js</code></td>
          <td>Service Worker。<code>push</code>イベントを受け取り通知を表示する</td>
        </tr>
        <tr>
          <td><code>api/save-subscription.php</code></td>
          <td>Push Subscriptionをpush_subscriptionsテーブルにINSERT/UPDATE</td>
        </tr>
        <tr>
          <td><code>api/notify_function.php</code></td>
          <td>Apache側から呼ばれる「起動係」。CLIのPHPプロセスを起動するだけ</td>
        </tr>
        <tr>
          <td><code>api/send_notification_cli.php</code></td>
          <td>CLIプロセスの実体。実際にWebPushライブラリで送信する</td>
        </tr>
      </table>

      <h2>1. フロントエンド：通知の許可と購読</h2>
      <p>ユーザーが通知を許可すると、ブラウザはPush Subscription（送信先エンドポイントと暗号化用の鍵）を生成します。これをサーバーに送って保存しておくことで、後から任意のタイミングで通知を送れるようになります。</p>

      <pre><code>const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
});

await fetch('/api/save-subscription.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(subscription)
});</code></pre>

      <p><code>sw.js</code>側には、通知を受け取った際にどう表示するかを定義しておきます。</p>

      <pre><code>self.addEventListener('push', (event) =&gt; {
    const data = event.data ? event.data.json() : {};
    event.waitUntil(self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/icons/icon-192.png',
        data: { url: data.url }
    }));
});</code></pre>

      <h2>2. 購読情報の保存とキー重複判定</h2>
      <p>同じ端末から複数回購読が行われることもあるため、<code>user_id</code>と<code>endpoint</code>の組み合わせで既存レコードの有無をチェックし、あれば鍵を更新、なければ新規登録する形にしています。</p>

      <pre><code>$stmt = $pdo-&gt;prepare("SELECT EXISTS (SELECT 1 FROM push_subscriptions WHERE user_id = ? AND endpoint = ?) AS already_exists");
$stmt-&gt;execute([$_SESSION['user_id'], $endpoint]);
$already = $stmt-&gt;fetchColumn();

if ($already) {
    // UPDATE
} else {
    // INSERT
}</code></pre>

      <div class="callout">
        <strong>認証はAPI専用のログインチェックに分離</strong><br>
        既存の<code>logincheck.php</code>は未ログイン時に画面をリダイレクトする作りでしたが、これをAPIにそのまま使うと<code>fetch</code>がリダイレクト先のHTMLを受け取ってしまい正しく動作しません。そこで、未ログイン時は401＋JSONを返すだけの<code>api_logincheck.php</code>を別途用意しました。
      </div>

      <h2>3. 投稿時にフォロワーへ通知を送る</h2>
      <p>投稿がDBに保存された直後、<code>follows</code>テーブルから投稿者のフォロワー一覧を取得し、通知送信関数に渡します。</p>

      <pre><code>$stmt = $pdo-&gt;prepare("SELECT follower_id FROM follows WHERE followed_id = ?");
$stmt-&gt;execute([$_SESSION['user_id']]);
$followerIds = $stmt-&gt;fetchAll(PDO::FETCH_COLUMN);

sendPushNotification(
    $pdo,
    $followerIds,
    'TaneLog',
    $_SESSION['nickname'] . 'さんが新しい投稿をしました',
    '/detail.php?contentid=' . $content_id
);</code></pre>

      <p>通知送信が失敗しても投稿自体は成功させたいため、この呼び出しはtry-catchで囲み、エラーはログに残すだけにとどめています。</p>

      <h2>4. Apache経由でcurlが動かない問題とCLI迂回</h2>
      <p>実装中、Web Push送信ライブラリ（<code>minishlink/web-push</code>）が要求するcurl拡張が、Apache（mod_php）経由では正しく読み込まれないという環境問題に直面しました。php.iniの設定やDLLの配置、PATH環境変数など一通り確認しましたが解決せず、最終的に<strong>Apacheを経由せず、CLI版PHPを別プロセスとして呼び出す</strong>方式に切り替えました。CLI版では最初からcurlが正常に動作していたためです。</p>

      <pre><code>// notify_function.php（Apache側から呼ばれる「起動係」）
function sendPushNotification(PDO $pdo, array $targetUserIds, string $title, string $body, string $url = '/'): void
{
    $payload = json_encode([
        'target_user_ids' =&gt; $targetUserIds,
        'title' =&gt; $title,
        'body' =&gt; $body,
        'url' =&gt; $url,
    ], JSON_UNESCAPED_UNICODE);

    // JSONを一時ファイルに書き出す（コマンドライン引数のクォート崩れを回避）
    $tmpFile = __DIR__ . '/tmp_notify_' . uniqid() . '.json';
    file_put_contents($tmpFile, $payload);

    $cmd = 'start /B "" ' . escapeshellarg('C:\\xampp\\php\\php.exe')
         . ' ' . escapeshellarg(__DIR__ . '\\send_notification_cli.php')
         . ' ' . escapeshellarg($tmpFile);

    pclose(popen($cmd, 'r'));
}</code></pre>

      <div class="callout">
        <strong>JSONをコマンドライン引数に直接渡さない理由</strong><br>
        最初はJSON文字列をそのままコマンドライン引数として渡していましたが、Windows環境では<code>escapeshellarg()</code>がダブルクォートを正しく処理しきれず、JSON内の<code>"</code>が失われて壊れたデータになってしまいました。一時ファイルに書き出し、そのファイルパスだけを引数として渡す方式に変更することで解決しました。
      </div>

      <p>CLI側の<code>send_notification_cli.php</code>は、このファイルを読み込んでから本来の送信処理を実行し、処理内容をログファイルに記録します。</p>

      <pre><code>$rawJson = file_get_contents($tmpFile);
$payload = json_decode($rawJson, true);
unlink($tmpFile);

sendPushNotificationCore(
    $pdo,
    $payload['target_user_ids'],
    $payload['title'],
    $payload['body'],
    $payload['url'] ?? '/'
);</code></pre>

      <h2>5. VAPID鍵とSSL証明書の設定</h2>
      <p>Web Pushの送信には、サーバーの身元を示すVAPID鍵（公開鍵・秘密鍵のペア）が必要です。<code>minishlink/web-push</code>付属のコマンドで生成し、環境変数ではなく設定用の<code>config.php</code>に切り出して管理するようにしました。</p>

      <pre><code>// config.php（.gitignore対象）
define('VAPID_SUBJECT', 'https://tanelog.yamatcha.net');
define('VAPID_PUBLIC_KEY', '...');
define('VAPID_PRIVATE_KEY', '...');</code></pre>

      <p>また、Windows環境ではcurlが参照するCA証明書バンドルが標準では設定されておらず、送信時に<code>SSL certificate ... unable to get local issuer certificate</code>というエラーが出ました。Mozillaが配布する<code>cacert.pem</code>を配置し、php.iniに<code>curl.cainfo</code>を設定することで解決しています。</p>

      <pre><code>curl.cainfo = "C:\xampp\php\cacert.pem"
openssl.cafile = "C:\xampp\php\cacert.pem"</code></pre>

      <h2>ポイントまとめ</h2>
      <ul>
        <li>Push Subscriptionは<code>user_id</code>と<code>endpoint</code>の組み合わせで一意に管理し、既存があれば更新する</li>
        <li>APIエンドポイントの認証チェックは、画面用のリダイレクト式ログインチェックとは別物として用意する</li>
        <li>投稿処理と通知送信は責務を分離し、通知失敗が投稿自体の失敗に波及しないようtry-catchで隔離する</li>
        <li>Apache（mod_php）環境でcurlが正しく動かない場合、CLI版PHPを別プロセスとして呼び出すことで迂回できる</li>
        <li>プロセス間でJSONを受け渡す際は、コマンドライン引数への直接埋め込みではなく一時ファイル経由にする</li>
        <li>VAPID鍵は秘密情報としてコードから切り離し、curlのSSL証明書設定も忘れずに行う</li>
      </ul>

      <h2>今後の課題</h2>
      <p>現状はフォロワー全員への一律通知のみですが、通知の種類（コメント・いいね等）ごとに送信有無を選べる設定や、通知本文に投稿内容のプレビューを含める改善、購読切れ端末の自動整理などを今後実装していく予定です。</p>

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
