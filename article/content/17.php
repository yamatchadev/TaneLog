<?php
require_once '../../db.php';
require_once '../../api/newscheck.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>【脆弱性解説】PHP/CVE-2024-4577とは何か | TaneLog</title>

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

  <?php require '../../header.php'; ?>
  <!-- ════════════════════════════════════════ -->

  <main class="article-layout">

    <!-- ───── サムネイル ───── -->
    <div class="article-thumbnail">
      <img src="../thumbnail/17.jpg" alt="CVE-2024-4577記事サムネイル">
    </div>

    <!-- ───── メタ情報 ───── -->
    <div class="article-meta">
      <span class="article-category">セキュリティ</span>
      <span class="article-tags">
        <span class="tag">PHP</span>
        <span class="tag">XAMPP</span>
        <span class="tag">脆弱性</span>
        <span class="tag">Windows</span>
      </span>
    </div>

    <!-- ───── タイトル ───── -->
    <h1 class="article-title">【脆弱性解説】PHP/CVE-2024-4577とは何か――XAMPP利用者が知っておくべきこと</h1>
    <p class="article-date">2026年6月30日</p>

    <hr class="article-divider">

    <!-- ───── 本文 ───── -->
    <div class="article-body">

      <div class="notice-box">
      <h2>重要：TaneLogでの対応について</h2>
      <p>
        TaneLog運営サーバーにおいても、先日ファイアウォールが「PHP/CVE-2024-4577」を悪用した攻撃を検知しましたが、
        この攻撃による当サーバー上の被害は一切ありませんでした。
        このサーバーで攻撃が確認された後、速やかにPHPのバージョンを「v8.2.12」から「v8.2.31」に更新しております。ご安心ください。
      </p>
      </div>

      <h2>CVE-2024-4577とは</h2>
      <p>
        CVE-2024-4577は、Windows上で動作するPHPに見つかった深刻な脆弱性です。
        条件がそろうと、外部の攻撃者がWebサーバーに対して任意のコードを実行できてしまう
        「リモートコード実行（RCE）」につながる、非常に危険なタイプの脆弱性に分類されます。
      </p>
      <p>
        2024年6月にセキュリティ研究者によって公開され、PoC（実証コード）も出回ったことから、
        公開直後から世界中で悪用が確認されています。日本国内の組織を狙った攻撃キャンペーンも
        報告されており、特定の地域だけの話ではなく、誰の環境でも標的になり得る脆弱性です。
      </p>

      <h2>なぜ起きるのか（仕組み）</h2>
      <p>
        原因はWindowsの「Best-Fit」という文字コード変換の仕様にあります。PHPをCGIモードで
        動かしている場合、Webサーバーが受け取ったリクエストはPHPの実行ファイルに渡されますが、
        このときWindowsがURL中の特殊な文字（ソフトハイフン）を、PHPがコマンドの区切りとして
        解釈してしまう普通のハイフンに勝手に変換してしまいます。
      </p>
      <p>
        その結果、攻撃者は本来許可されていないはずのPHP起動オプションを、URLのパラメータ経由で
        こっそり注入できてしまいます。これを使うと、サーバー上のソースコードを盗み見たり、
        最終的には任意のPHPコードを実行されたりする恐れがあります。
      </p>
      <p>
        実はこの問題自体は2012年に一度修正された「CVE-2012-1823」と根っこは同じで、
        2024年版はその修正をすり抜ける形で再び発見された、いわば“再発”の脆弱性です。
      </p>

      <h2>XAMPP利用者は特に注意</h2>
      <p>
        重要なポイントとして、<strong>Windows版のXAMPPはデフォルト設定のままで影響を受けます</strong>。
        XAMPPはPHPの実行ファイルがWeb経由でアクセスできる場所に置かれていることが多いため、
        CGIモードを意識して使っていなくても、攻撃の対象になり得ます。
      </p>
      <p>影響を受けるバージョンは以下の通りです。</p>
      <ul>
        <li>PHP 8.3系：8.3.8 より前</li>
        <li>PHP 8.2系：8.2.20 より前</li>
        <li>PHP 8.1系：8.1.29 より前</li>
      </ul>
      <p>
        注意したいのは、2026年現在もApache Friends公式のXAMPP Windows版（8.2系）は
        <code>8.2.12</code> のまま新しいビルドが提供されていない点です。つまり
        「最新版のXAMPPをインストールした」というだけでは、この脆弱性が解消されない場合があります。
      </p>

      <h2>対策方法</h2>
      <p>根本的に解決するには、PHP本体を安全なバージョンへ更新する必要があります。</p>
      <ol>
        <li>
          <strong>PHP本体だけを手動で差し替える</strong><br>
          <a href="https://windows.php.net/download/" target="_blank" rel="noopener">windows.php.net</a>
          から、使用環境（ZTS/x64/VC++のバージョン）に合った新しいPHPをダウンロードし、
          XAMPPの <code>php</code> フォルダの中身を入れ替える方法です。
          Apacheの設定はフォルダパスを固定で参照しているため、フォルダ名自体は変更しないのがポイントです。
        </li>
        <li>
          <strong>暫定的にphp-cgi.exeへのアクセスを遮断する</strong><br>
          すぐにアップデートできない場合は、Apacheの設定ファイル
          （<code>httpd-xampp.conf</code>）で、PHP実行ファイルへの直接アクセスを拒否するルールを
          追加することで、攻撃を一時的に防ぐことができます。
        </li>
      </ol>

      <h2>個人開発環境でも油断は禁物</h2>
      <p>
        「ローカル環境だから大丈夫」と思いがちですが、Cloudflare Tunnelなどでローカルサーバーを
        外部公開している場合、その瞬間からインターネット全体からの自動スキャン・攻撃の対象になります。
        実際、こうした脆弱性は公開後すぐに自動化されたボットによって世界中でスキャンされるため、
        個人の学習・開発用サーバーであっても無関係ではありません。
      </p>

      <h2>まとめ</h2>
      <ul>
        <li>CVE-2024-4577は、Windows版PHPの文字コード変換の不具合を突いたRCE脆弱性</li>
        <li>XAMPP for Windowsはデフォルトで影響を受ける</li>
        <li>XAMPP公式の「最新版」だけでは対策できていない場合がある</li>
        <li>PHP本体を安全なバージョンへ手動更新するのが確実な対策</li>
        <li>外部公開しているローカルサーバーは特に狙われやすいため要注意</li>
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