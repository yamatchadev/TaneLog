<?php
$hour = 23;

echo $hour."時です。<br>";
if ($hour > 23) {
    echo "23より大きい数字を入れないで！";
}elseif ($hour < 12) {
    echo "午前です。";
} elseif ($hour == 12) {
    echo "正午です。";
}else{
    echo "午後です。";
}
?>