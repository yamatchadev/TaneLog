<?php
// for ループ
for ($i=1; $i<=5; $i++){
    echo $i . " ";
}

$foods = ["寿司","ラーメン","カレー"];
foreach ($foods as $f) {
    echo "<li>{$f}</li>";
}
?>