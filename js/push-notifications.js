// push-notifications.js
const VAPID_PUBLIC_KEY = 'BIJpysyKVdnHSOxfBxtHyjd9W6xev8gVExcFmk3MJfotkgdG3SHatYrmi8oiarmazkdZgC5lzGEkBzmDNMMF42Y';

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}

async function subscribeToPush() {
  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    console.log('通知が許可されませんでした');
    return;
  }

  const registration = await navigator.serviceWorker.ready;

  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
  });

  // サーバーに購読情報を送信
  await fetch('/api/save-subscription.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(subscription)
  });

  console.log('通知登録完了');
}

// 例：設定画面などのボタンから呼び出す
document.getElementById('enable-push-btn')?.addEventListener('click', subscribeToPush);