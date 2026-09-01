<?php
// 変数は $ から始める
$name = "浦田";
$age = 16;
$price = 2010.5;
$isLogin = true;

//出力 echo か print
echo "こんにちは。{$name}さん！"; //ダブルクォートは変数展開OK
echo "<br>年齢は".$age."歳<br>"; //. でつなぐ

//型を確認したいとき
var_dump($price); // float(2010.5)
?>

