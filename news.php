<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせ - TaneLog</title>
    <link rel="stylesheet" id="theme-link" href="css/style-light.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.getElementById('theme-link').href = savedTheme === 'dark' ? 'css/style-dark.css' : 'css/style-light.css';
    </script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 40px 20px;
            text-align: center;
        }
        a {
            color: var(--primary-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>準備中。<a href="timeline.php">戻る</a></h1>
</body>
</html>