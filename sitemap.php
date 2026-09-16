<?php
// sitemap.php
// Apacheの設定で /sitemap.xml へのリクエストをこのファイルに向けるか、
// このファイル自体を sitemap.xml として直接配置してください。

header('Content-Type: application/xml; charset=utf-8');
require_once 'db.php'; // $pdo を使う既存の接続ファイル

// 静的ページ
$staticUrls = [
    ['loc' => 'https://tanelog.yamatcha.net/', 'priority' => '1.0'],
    ['loc' => 'https://tanelog.yamatcha.net/register.php', 'priority' => '0.5'],
];

// 記事一覧を取得(deletedカラムがある場合は除外)
$stmt = $pdo->query("SELECT id, created_at FROM articles ORDER BY created_at DESC");
$articles = $stmt->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticUrls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc']) ?></loc>
        <priority><?= $url['priority'] ?></priority>
    </url>
<?php endforeach; ?>

<?php foreach ($articles as $article): ?>
    <url>
        <loc>https://tanelog.yamatcha.net/articles/content/<?= (int)$article['id'] ?>.php</loc>
        <lastmod><?= date('Y-m-d', strtotime($article['created_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
</urlset>