//vite.config.js　でこのファイルを指定

import { precacheAndRoute } from 'workbox-precaching';
// Workbox runtime caching API の読み込み
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { StaleWhileRevalidate, NetworkFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';

// プリキャッシュの設定
precacheAndRoute(self.__WB_MANIFEST);

// =====================================================================
// サービスワーカーのインストールとアクティブ化
// =====================================================================
self.addEventListener('install', event => {
    console.log('ServiceWorker インストールイベント');
    self.skipWaiting(); // インストール後すぐにアクティブ化
});

self.addEventListener('activate', event => {
    console.log('ServiceWorker アクティブ化イベント');
    event.waitUntil(clients.claim()); // アクティブ化後すぐにページを制御
});



// =====================================================================
// プッシュ通知
// =====================================================================
self.addEventListener('push', function(event) {
    try {
        const data = event.data ? event.data.json() : {};
        // アイコンパスをservice workerのスコープに基づいて相対パスで生成
        const iconPath = data.icon || (self.registration.scope + 'img/icon/home_icon_192_192.png');

        self.registration.showNotification(data.title || '通知', {
            body: data.body || '',
            icon: iconPath,
            data: data.url || '/',
            actions: [
                {
                    action: 'open_url',
                    title: 'Open'
                }
            ]
        });
        console.log('push event成功');
    } catch (error) {
        console.error('push event失敗:', error);
    }
});

// 通知クリック時の処理
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.notification.data) {
        event.waitUntil(
            clients.openWindow(event.notification.data)
        );
    }
});


// =====================================================================
// キャッシュ設定
// =====================================================================

registerRoute(
    // request.destination で静的ファイルを判定
    ({ request }) =>
        ['style', 'script', 'image', 'font'].includes(request.destination),

    new StaleWhileRevalidate({
        cacheName: `${self.registration.scope}-static-assets-cache`,
        plugins: [
            new ExpirationPlugin({
                maxEntries: 50,                         // 最大 50 ファイル
                maxAgeSeconds: 30 * 24 * 60 * 60,       // 30日
            }),
        ],
    })
);

// ※ SPA / Laravelページのオフライン耐性を高める
const navigationHandler = new NetworkFirst({
    cacheName: `${self.registration.scope}-html-cache`,
    networkTimeoutSeconds: 3, // オプション：ネットワーク遅いときキャッシュ利用
    plugins: [
        new ExpirationPlugin({
            maxEntries: 20,
            maxAgeSeconds: 24 * 60 * 60, // 1日
        }),
    ],
});

// navigation routing の登録
registerRoute(new NavigationRoute(navigationHandler));