<?php
session_start();

// セッション変数を全部空にする
$_SESSION = [];

// クッキーを削除
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}
// セッションを破棄
session_destroy();

header('Location: login.php');
exit;
?>