<?php
require 'vendor/autoload.php';
use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();
print_r($keys); // publicKey と privateKey が出力される