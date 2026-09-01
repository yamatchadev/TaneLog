<?php
require 'vendor/autoload.php';

function sendGmail(string $to, string $subject, string $body): void
{
    require_once 'secret.php';
    $client = new Google\Client();
    $client->setClientId($clientid);
    $client->setClientSecret($clientsecret);

    // 保存済みトークンを読み込む
    $tokenData = json_decode(file_get_contents('../token_gmail.json'), true);
    $client->setAccessToken($tokenData);

    // トークンが期限切れなら自動更新
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents('../token_gmail.json', json_encode($client->getAccessToken()));
    }

    $service = new Google\Service\Gmail($client);

    // RFC 2822 形式のメールを作成
    $message  = "To: {$to}\r\n";
    $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "\r\n";
    $message .= base64_encode($body);

    // Gmail API 用にエンコード（URL-safe Base64）
    $encodedMessage = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

    $gmailMessage = new Google\Service\Gmail\Message();
    $gmailMessage->setRaw($encodedMessage);

    $service->users_messages->send('me', $gmailMessage);
    $mailresult = "true";
}
