document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('menuBtn');
    const closeBtn = document.getElementById('closeBtn');
    const sideMenu = document.getElementById('sideMenu');

    // ハンバーガーマークを押したときにメニューを開く
    if (menuBtn && sideMenu) {
        menuBtn.addEventListener('click', () => {
            sideMenu.classList.add('active');
        });
    }

    // 閉じる（×）ボタンを押したときにメニューを閉じる
    if (closeBtn && sideMenu) {
        closeBtn.addEventListener('click', () => {
            sideMenu.classList.remove('active');
        });
    }
});