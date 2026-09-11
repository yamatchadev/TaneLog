保存API（save_article.php）の設計方針

ロジックとしてはこういう形になります。

リクエストボディ（JSON）からarticle_id（nullなら新規）とblocks（配列）を受け取る
article_idが空の場合：INSERTして新しい行を作り、content_blocksにJSON文字列を保存。生成されたidをレスポンスとして返す
article_idがある場合：UPDATE articles SET content_blocks = :blocks, updated_at = NOW() WHERE id = :id AND author_id = :user_id（他人の記事を上書きできないようauthor_id条件は必須です）
content_blocksへの格納はjson_encode($blocks)、レスポンスはjson_encode(['success' => true, 'id' => $id])のような形

PDOのprepared statement部分はいつも通りご自身で書いていただく形でよいと思いますが、「新規INSERTかUPDATEかの分岐」「他人の記事を書き換えられないようauthor_idチェックを入れる」の2点は設計上の勘所なので覚えておいてください。