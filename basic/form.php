<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
$name = htmlspecialchars($_POST['name']);
$food = htmlspecialchars($_POST['food']);
echo "あなたの名前は、「".$name."」です。";
echo "好きな食べ物は、".$food."でしょう。";
}
?>
<html>
    <form action="form.php" method="post">
        <p>お名前は？</p>
        <input type="text" name="name">
        <p>好きな食べ物は？</p>
        <input type="text" name="food">
        <button type="submit">確認</button>
    </form>
</html>