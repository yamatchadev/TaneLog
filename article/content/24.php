<?php
require_once '../../db.php';
require_once '../../api/newscheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新規登録にメール認証を追加する | TaneLog</title>

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
      <img src="../thumbnail/24.png" alt="記事サムネイル">
    </div>

    <div class="article-meta">
      <span class="article-category">実装解説</span>
      <span class="article-tags">
        <span class="tag">PHP</span>
        <span class="tag">セキュリティ</span>
        <span class="tag">メール認証</span>
        <span class="tag">新規登録</span>
      </span>
    </div>

    <h1 class="article-title">新規登録にメール認証を追加する — 仮登録テーブルとコード認証の実装</h1>
    <p class="article-date">2026/09/10</p>

    <hr class="article-divider">

    <div class="article-body">

      <h2>背景：なぜメール認証が必要なのか</h2>
      <p>これまでのTaneLogの新規登録は、フォームに入力されたメールアドレスをそのまま<code>users</code>テーブルに保存するだけでした。この方式には、存在しないメールアドレスや他人のメールアドレスで誰でも登録できてしまうという弱点があります。</p>
      <p>そこで、本登録の前に「そのメールアドレスに実際にアクセスできるか」を確認するステップを挟むことにしました。具体的には、本登録前の情報を一時的に保存する<code>pre_users</code>テーブルを新設し、そこに6桁の認証コードとトークンを持たせる方式にしました。</p>

      <div class="callout">
        <strong>仮登録と本登録を分離する理由</strong><br>
        認証コードを送っただけの段階ではまだ<code>users</code>テーブルには一切書き込みません。こうすることで、認証が完了しないまま離脱したユーザーの未確認データで本番テーブルが汚れるのを防いでいます。
      </div>

      <h2>全体の流れ</h2>
      <div class="flow-diagram">ユーザー                          サーバー（PHP）
─────────────────────────────────────────────────
1. メールアドレスを入力          send_email.php
   → 送信                        ・重複チェック（SELECT EXISTS）
                                  ・6桁コード生成＋token生成
                                  ・pre_usersにINSERT
                                  ・Gmail APIでコードを送信
                                ←  verify_email.php?token=... へリダイレクト

2. 届いたコードを入力            verify_email.php
   → 送信                        ・tokenでpre_usersを検索（verified=0）
                                  ・24時間以内か＆コード一致を確認
                                  ・一致すればverified=1に更新
                                  ・$_SESSION['email']をセット
                                ←  register.php へリダイレクト

3. ニックネーム等を入力          register.php
   → 送信                        ・セッションのtoken/emailを再検証
                                  ・users テーブルにINSERT（password_hash）
                                ←  login.php へリダイレクト</div>

      <h2>登場するファイル</h2>
      <table class="summary">
        <tr>
          <th>ファイル</th>
          <th>役割</th>
        </tr>
        <tr>
          <td><code>send_email.php</code></td>
          <td>メールアドレスを受け取り、仮登録＋認証コード送信を行う</td>
        </tr>
        <tr>
          <td><code>verify_email.php</code></td>
          <td>認証コードを照合し、通過したらセッションを発行する</td>
        </tr>
        <tr>
          <td><code>register.php</code></td>
          <td>セッションを再検証したうえで本登録（usersテーブルへのINSERT）を行う</td>
        </tr>
        <tr>
          <td><code>pre_users</code>（テーブル）</td>
          <td>email, verify_code, token, verified, created_at を保持する仮登録テーブル</td>
        </tr>
      </table>

      <h2>1. send_email.php — 仮登録とコード送信</h2>
      <p>まず<code>FILTER_VALIDATE_EMAIL</code>で形式をチェックし、続けて<code>users</code>テーブルに同じメールアドレスがすでに存在しないかを<code>SELECT EXISTS</code>で確認します。重複がなければ、6桁のコードとランダムなトークンを発行します。</p>

      <pre><code>$code = sprintf('%06d', random_int(0, 999999));
$token = bin2hex(random_bytes(16));</code></pre>

      <p><code>random_int</code>は暗号論的に安全な乱数生成関数なので、<code>rand()</code>より推測されにくいコードになります。<code>sprintf('%06d', ...)</code>で0埋めし、必ず6桁の文字列にしています。</p>
      <p>この情報を<code>pre_users</code>にINSERTしたあと、Gmail API（<code>gmail.php</code>の<code>sendGmail</code>）でコードを本文に含めたメールを送信し、<code>verify_email.php?token=...</code>へリダイレクトします。</p>

      <h2>2. verify_email.php — 認証コードの確認</h2>
      <p>このページはGETパラメータの<code>token</code>を起点に動きます。まず<code>verified = 0</code>の条件でトークンが存在するかを確認し、存在しなければ「URLが期限切れか、誤っています」というエラーを表示します。</p>
      <p>ユーザーがコードを入力して送信すると、あらためて<code>created_at</code>が24時間以内かどうかを確認したうえで、コードが一致するかを照合します。</p>

      <pre><code>$stmt = $pdo->prepare(
    "SELECT * FROM pre_users WHERE token = ? AND created_at > NOW() - interval 24 HOUR"
);
$stmt->execute([$token]);
$verified_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($verified_user && $_POST['verify_code'] == $verified_user['verify_code']) {
    $verified = $pdo->prepare("UPDATE pre_users SET verified = 1 WHERE id = ?");
    $verified->execute([$verified_user['id']]);

    $_SESSION['email'] = $verified_user['email'];
    $_SESSION['register_token'] = $verified_user['token'];
    header('Location: register.php');
    exit;
}</code></pre>

      <p>一致した時点で<code>pre_users.verified</code>を1に更新し、<code>$_SESSION['email']</code>と<code>$_SESSION['register_token']</code>をセットします。この2つのセッション値が、次の<code>register.php</code>への「通行許可証」の役割を果たします。</p>

      <div class="callout">
        <strong>存在チェックとコード照合を分けている理由</strong><br>
        「トークンが存在するか（＝URL自体が有効か）」と「コードが一致するか（＝本人がメールを受け取ったか）」を別々のクエリで確認しているため、期限切れURLと単なる入力ミスを別のエラーメッセージで案内できます。
      </div>

      <h2>3. register.php — セッションによるアクセス制御</h2>
      <p><code>register.php</code>の冒頭では、<code>$_SESSION['register_token']</code>と<code>$_SESSION['email']</code>の両方がセットされているかをまず確認し、さらに<code>pre_users</code>テーブルでその組み合わせが実在するかを再確認しています。</p>

      <pre><code>$stmt = $pdo->prepare(
    "SELECT EXISTS (SELECT * FROM pre_users WHERE token = ? AND email = ?)"
);
$stmt->execute([$_SESSION['register_token'], $_SESSION['email']]);</code></pre>

      <p>ここで存在しなければ<code>index.php</code>へ強制的にリダイレクトされるため、メール認証を経ずに<code>register.php</code>へ直接アクセスすることはできません。</p>
      <p>フォーム上のメールアドレス欄は、認証済みの値をそのまま表示しつつ<code>disabled</code>属性で編集不可にしています。</p>

      <pre><code>&lt;input type="email" name="email" required
       value="&lt;?= $email; ?&gt;" disabled&gt;</code></pre>

      <div class="callout">
        <strong>disabled属性の入力はPOSTされない</strong><br>
        <code>disabled</code>を付けた<code>&lt;input&gt;</code>はフォーム送信時に値がPOSTデータへ含まれません。そのためregister.php側の<code>$email</code>はPOSTデータではなく<code>$_SESSION['email']</code>から取得するようにしています。もしここでPOSTされた値を信用してしまうと、認証していない別のメールアドレスで登録される抜け道になってしまいます。
      </div>

      <p>あとはニックネーム・ユーザー名・パスワードを受け取り、パスワード一致を確認したうえで<code>password_hash</code>でハッシュ化し、<code>users</code>テーブルへINSERTすれば本登録は完了です。</p>

      <h2>セキュリティ面のポイントまとめ</h2>
      <ul>
        <li>6桁コード＋ランダムトークンの二重の値で認証し、どちらも<code>random_int</code>／<code>random_bytes</code>で生成</li>
        <li><code>created_at &gt; NOW() - interval 24 HOUR</code>による有効期限チェック</li>
        <li><code>pre_users</code>テーブルで仮登録を分離し、未認証データが<code>users</code>テーブルに混入しない設計</li>
        <li>メールアドレスは常に<code>$_SESSION</code>経由で扱い、フォームの<code>disabled</code>な値やPOSTデータを信用しない</li>
        <li>パスワードは<code>password_hash</code>でハッシュ化してから保存</li>
      </ul>

      <h2>今後の課題</h2>
      <p>現状の実装では、認証コードの入力試行回数に制限がなく、6桁（100万通り）は理論上総当たりが可能な範囲です。今後はコードの試行回数制限や、一定回数失敗した場合にトークンを無効化する仕組みを検討する余地があります。また、コードが届かない場合の再送機能も未実装のため、あわせて検討していく予定です。</p>

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
