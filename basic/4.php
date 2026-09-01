<?php
//　関数を定義
function greet($name){
    return "こんにちは！{$name}さん！";
}

echo greet("浦田");

function tax($price) {
    $rate=0.1;
    return $price * (1 + $rate);
}
echo tax(1000);
?>