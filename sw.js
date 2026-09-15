// タネログ PWA - 最低限のService Worker(インストール可能にするためのみ)
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  self.clients.claim();
});

// fetchイベントは何もキャッシュせず素通しする(オフラインキャッシュなし)
self.addEventListener('fetch', (event) => {
  // 何もしない = 通常通りネットワークから取得
});

// 通知受信時の処理
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};

  const title = data.title || 'タネログ';
  const options = {
    body: data.body || '',
    icon: data.icon || '/icon/icon-192.png',
    badge: data.badge || '/icon/icon-192.png',
    data: { url: data.url || '/' }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// 通知クリック時にアプリを開く
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});