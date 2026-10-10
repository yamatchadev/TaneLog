<?php

header('Content-Type: application/json');
require_once __DIR__.'/logincheck.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/notify_function.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit;
}

$content = trim($_POST['content'] ?? '');
if ($content === '') {
    echo json_encode(['ok' => false, 'error' => 'content is empty']);
    exit;
}
function linkifyHtml(string $sanitizedHtml): string
{
    // " ' < > と空白、エンティティ化された引用符・括弧類をURLの終端とみなす
    $pattern = '/https?:\/\/(?:(?!&(?:quot|#0?39|lt|gt);)[^\s<>"\'])+/iu';

    return preg_replace_callback($pattern, function (array $m): string {
        $url = $m[0];

        // 末尾の句読点・記号をリンクの外に出す
        $trailing = '';
        if (preg_match('/[).,!?、。」』]+$/u', $url, $t)) {
            $trailing = $t[0];
            $url = mb_substr($url, 0, mb_strlen($url) - mb_strlen($trailing));
        }

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="front-link">'
             . $url . '</a>' . $trailing;
    }, $sanitizedHtml);
}
require_once __DIR__ . '/../vendor/autoload.php';
function sanitizeRichText(string $html): string
{
    static $purifier = null;

    if ($purifier === null) {
        // キャッシュ保存先（公開ディレクトリの外が理想です）
        $cacheDir = 'C:/tanelog_files/cache/htmlpurifier';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

        // 許可するタグと属性（これ以外はすべて除去されます）
        $config->set('HTML.Allowed', 'p[style],br,strong,em,span[style],ul,ol,li');

        // style内で許可するCSSプロパティ
        $config->set('CSS.AllowedProperties', 'strong,em,ul,ol,li,color,text-align');

        // 中身が空のタグを除去（<p></p> など）
        $config->set('AutoFormat.RemoveEmpty', true);

        $config->set('Cache.SerializerPath', $cacheDir);

        $purifier = new HTMLPurifier($config);
    }

    return $purifier->purify($html);
}

/**
 * サニタイズ後のHTMLが実質的に空かどうかを判定する
 */
function isRichTextEmpty(string $sanitizedHtml): bool
{
    $text = strip_tags($sanitizedHtml);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    // 半角・全角スペース、&nbsp;（U+00A0）を除去して判定
    $text = preg_replace('/[\s\x{00A0}\x{3000}]+/u', '', $text);
    return $text === '';
}
$content_clean = sanitizeRichText($_POST['content']);
if(isRichTextEmpty($content_clean)){
    echo json_encode(['ok' => false, 'error' => 'content is empty']);
    exit;
}
    $content_clean = linkifyHtml($content_clean);
    $content_id = '';
    for ($i = 0; $i < 15; $i++) {
        $content_id .= random_int(0, 9);
    }
    $parent_id = $_SESSION['parent_id'] ?? null;
    try{
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, content_id, parent_id) VALUES (?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'],$content_clean,$content_id,$parent_id]);
        $new_post_id = (int)$pdo->lastInsertId();
        }catch(PDOException $e){
            echo json_encode(['ok' => false, 'error' => 'Database error']);
            exit();
        }
        if(isset($_SESSION['parent_id'])){
            try{
                // 返信先ユーザーに通知を送信
                $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE content_id = ?");
                $stmt->execute([$_SESSION['parent_id']]);
                $followerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                // 通知送信（投稿者自身のニックネームを本文に含める）
                $content_preview = mb_strlen($content_clean) > 30
                    ? mb_substr($content_clean, 0, 30) . '…'
                    : $content_clean;

                sendPushNotification(
                    $pdo,
                    $followerIds,
                    'TaneLog',
                    $_SESSION['nickname'] . 'さんから返信が届きました：' . $content_preview,
                    '/detail.php?contentid=' . $content_id
                );
            }catch(PDOException $e){
                error_log('Push notification failed:'.$e->getMessage());
            }
            echo json_encode(['ok' => true, 'post_id' => $new_post_id, 'contentid' => $content_id]);
            unset($_SESSION['parent_id']);   
            exit;
            }else{
                try{
                    // フォロワーのuser_idを取得
                    $stmt = $pdo->prepare("SELECT follower_id FROM follows WHERE followed_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $followerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    // 通知送信（投稿者自身のニックネームを本文に含める）
                    $content_preview = mb_strlen($content_clean) > 30
                        ? mb_substr($content_clean, 0, 30) . '…'
                        : $content_clean;

                    sendPushNotification(
                        $pdo,
                        $followerIds,
                        'TaneLog',
                        $_SESSION['nickname'] . 'さんが新しい投稿をしました：' . $content_preview,
                        '/detail.php?contentid=' . $content_id
                    );
                }catch(PDOException $e){
                    error_log('Push notification failed:'.$e->getMessage());
                }
                echo json_encode(['ok' => true, 'post_id' => $new_post_id, 'contentid' => $content_id]);
            }

