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
  <title>RememberMe機能の実装――クッキーとトークンで自動ログイン | TaneLog</title>

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

            /* お知らせボックス（薄い赤・角丸） */
            .notice-box {
                background-color: #fdecea;
                border-radius: 12px;
                padding: 20px 24px;
                margin: 24px 0;
            }
            .notice-box h2 {
                margin-top: 0;
                color: #333333;
            }
            .notice-box p {
                margin-bottom: 0;
                color: #333333;
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
      <img src="../thumbnails/18.jpg" alt="RememberMe機能記事サムネイル">
    </div>

    <!-- ───── メタ情報 ───── -->
    <div class="article-meta">
      <span class="article-category">認証・セキュリティ</span>
      <span class="article-tags">
        <span class="tag">PHP</span>
        <span class="tag">Cookie</span>
        <span class="tag">ログイン</span>
        <span class="tag">セキュリティ</span>
      </span>
    </div>

    <!-- ───── タイトル ───── -->
    <h1 class="article-title">RememberMe機能の実装――クッキーとトークンで自動ログインを作る</h1>
    <p class="article-date">2026年7月10日</p>

    <hr class="article-divider">

    <!-- ───── 本文 ───── -->
    <div class="article-body">

      <h2>RememberMe機能とは</h2>
      <p>
        ログインフォームの「ログイン状態を保持する」チェックボックスを有効にすると、
        ブラウザを閉じて再度開いてもログインが維持される――このような機能を
        「RememberMe（ログイン状態の持続）」と呼びます。
      </p>
      <p>
        通常のセッションはブラウザを閉じると失われますが、RememberMeではクッキーに
        認証トークンを保存しておくことで、次回アクセス時にそのトークンを使って
        自動的にログインします。
      </p>

      <h2>なぜパスワードをそのままクッキーに入れてはいけないのか</h2>
      <p>
        クッキーはブラウザの開発者ツールから簡単に確認できるほか、XSSや通信の盗聴などで
        盗まれるリスクがあります。万が一クッキーの中身が漏れたとき、そこにパスワードが
        直接入っていれば、アカウントを完全に乗っ取られてしまいます。
      </p>
      <p>
        そこで、パスワードとは独立した「使い捨て可能なトークン」をクッキーに保存する
        方法が使われます。トークンが漏れても無効化できますし、パスワード自体が
        外部に露出する心配がありません。
      </p>

      <h2>仕組みの概要</h2>
      <p>RememberMe機能の流れは大きく3段階です。</p>
      <ol>
        <li><strong>ログイン時：</strong>チェックボックスがONなら、ランダムなトークンを生成してDBに保存し、クッキーにも書き込む。</li>
        <li><strong>次回アクセス時：</strong>セッションが切れていたら、クッキーのトークンをDBと照合し、一致すればセッションを再生成してログイン状態を復元する。</li>
        <li><strong>ログアウト時：</strong>クッキーを削除し、DBのトークンも無効化する。</li>
      </ol>

      <h2>DBテーブルの準備</h2>
      <p>まずトークンを保存するテーブルを作ります。</p>

      <pre><code>CREATE TABLE remember_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);</code></pre>

      <p>
        <code>expires_at</code> で有効期限を管理します。期限切れのトークンは認証に使えないように
        処理でチェックします。
      </p>

      <h2>ログイン時のトークン生成</h2>
      <p>
        チェックボックスがONだった場合に、ログイン成功後の処理として以下を追記します。
      </p>

      <pre><code>if (isset($_POST['remember_me'])) {
    // 安全なランダムトークンを生成（32バイト→16進数64文字）
    $token = bin2hex(random_bytes(32));

    // 有効期限を30日後に設定
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

    // DBに保存
    $stmt = $pdo->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $token, $expires_at]);

    // クッキーにセット（30日間・HttpOnly・Secure・SameSite=Strict）
    setcookie(
        'remember_token',
        $token,
        [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]
    );
}</code></pre>

      <p>
        <code>random_bytes()</code> はPHPが提供する暗号論的に安全な乱数生成関数です。
        <code>rand()</code> や <code>mt_rand()</code> は予測可能な場合があるため、
        認証トークンには必ず <code>random_bytes()</code> を使いましょう。
      </p>

      <h2>次回アクセス時の自動ログイン処理</h2>
      <p>
        セッションが有効でない場合に、クッキーのトークンを確認する処理を
        ログインチェック用のファイル（例: <code>logincheck.php</code>）に追加します。
      </p>

      <pre><code>session_start();

if (!isset($_SESSION['user_id'])) {
    // セッションがない場合、クッキーのトークンを確認
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];

        $stmt = $pdo->prepare(
            'SELECT user_id FROM remember_tokens
             WHERE token = ? AND expires_at > NOW()'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // トークンが有効 → セッションを再生成してログイン状態を復元
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];
        } else {
            // 無効なトークン → クッキーを削除してログインページへ
            setcookie('remember_token', '', time() - 1, '/');
            header('Location: /login.php');
            exit;
        }
    } else {
        header('Location: /login.php');
        exit;
    }
}</code></pre>

      <p>
        <code>session_regenerate_id(true)</code> は、トークン認証後に必ず呼ぶことで
        セッション固定攻撃（Session Fixation）を防ぎます。
      </p>

      <h2>ログアウト時の後処理</h2>
      <p>
        ログアウト処理では、クッキーの削除とDBのトークン削除を両方行います。
        どちらか片方だけだと、古いトークンが悪用されるリスクが残ります。
      </p>

      <pre><code>// DBのトークンを削除
if (isset($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE token = ?');
    $stmt->execute([$_COOKIE['remember_token']]);
}

// クッキーを削除（expires を過去の日時にする）
setcookie('remember_token', '', time() - 1, '/');

// セッションを破棄
$_SESSION = [];
session_destroy();</code></pre>

      <h2>セキュリティ上の注意点</h2>
      <ul>
        <li>
          <strong>HttpOnly属性を必ず付ける</strong>：JavaScriptからクッキーにアクセスできなくなり、XSSによるトークン盗難を防ぎます。
        </li>
        <li>
          <strong>HTTPS環境ではSecure属性も追加する</strong>：<code>'secure' => true</code> を <code>setcookie()</code> のオプションに加えると、HTTP通信ではクッキーが送信されなくなります。
        </li>
        <li>
          <strong>トークンはDB側でハッシュ化して保存するとより安全</strong>：クッキーには生のトークン、DBには <code>hash('sha256', $token)</code> の値を保存し、照合時もハッシュして比較する方法があります。DBが漏れてもトークンそのものは分からなくなります。
        </li>
        <li>
          <strong>トークンは1回使い切りにする（推奨）</strong>：自動ログインのたびに新しいトークンを発行し、古いトークンを削除する「トークンローテーション」を実装すると、盗まれたトークンの悪用を早期に検知できます。
        </li>
        <li>
          <strong>有効期限を設ける</strong>：無期限のトークンは危険です。30日程度を上限に設定し、期限切れのレコードは定期的にDBから削除するようにしましょう。
        </li>
      </ul>

      <h2>まとめ</h2>
      <ul>
        <li>RememberMeはパスワードではなく、使い捨てのランダムトークンをクッキーに保存する</li>
        <li>トークンの生成には <code>random_bytes()</code> を使い、安全な乱数を確保する</li>
        <li>クッキーには <code>HttpOnly</code>・<code>SameSite</code> 属性を必ず付ける</li>
        <li>ログアウト時はクッキーとDBの両方でトークンを削除する</li>
        <li>より高いセキュリティを求めるなら、トークンのDB側ハッシュ化とローテーションを検討する</li>
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