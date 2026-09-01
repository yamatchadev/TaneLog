// テーマ切り替え、テーマ設定の読み込みを行います。
// ① 初期テーマの決定とCSS読み込み（即時実行・head内で使う想定）
(function () {
    var theme = localStorage.getItem('theme') || 'light';
    document.write('<link rel="stylesheet" id="theme-link" href="css/style-' + theme + '.css">');
})();

// ② ボタン操作・ロゴ切り替えなど（DOM構築後に実行）
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const headerLogo = document.getElementById('headerLogo');
    const themeLink = document.getElementById('theme-link');

    function updateToggleBtnIcon(theme) {
        if (themeToggleBtn) {
            themeToggleBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }

    const currentTheme = localStorage.getItem('theme') || 'light';
    updateToggleBtnIcon(currentTheme);

    if (headerLogo) {
        headerLogo.src = currentTheme === 'dark' ? 'img/logo_dark.png' : 'img/tanelog.png';
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isDark = themeLink.href.includes('css/style-dark.css');
            const newTheme = isDark ? 'light' : 'dark';

            themeLink.href = newTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
            updateToggleBtnIcon(newTheme);
            if (headerLogo) {
                headerLogo.src = newTheme === 'dark' ? 'img/logo_dark.png' : 'img/tanelog.png';
            }

            localStorage.setItem('theme', newTheme);
        });
    }
});