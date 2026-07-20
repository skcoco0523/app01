// vite.config.js

//npm run dev
//npm run build

import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';

// .env から環境変数を読み込み
const host          = process.env.VITE_SUB_DOMAIN || 'localhost'; 
const appName       = process.env.VITE_APP_NAME || 'default';

const isLocal       = host === 'localhost' || host.includes('127.0.0.1');
const protocol      = isLocal ? 'ws' : 'wss';
const clientPort    = isLocal ? 5173 : 443;
// ベースURLパスを定義 (Apache設定に基づき '/' に統一)
const baseUrlPath   = '/';

export default defineConfig({
    // アセットのベースパス
    base: baseUrlPath,
    //デバッグ用設定
    server: {
        host: '0.0.0.0', // 外部からのアクセスを許可
        port: 5173,     // app01 プロジェクトのViteポート
        hmr: {
            host: host, // HMR のホストをサブドメイン名に設定
            protocol:protocol, // HTTPS対応
            clientPort: clientPort, // HTTPS環境では443ポート
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss', // Sassファイル
                'resources/js/app.js',     // JavaScriptファイル
                'resources/css/app.css',   // CSSファイル
                //ここからはゲームのエントリーポイントを追加していく
                'resources/js/games/game_engine/main.js',
            ],
            refresh: true,
            detectTls: false,
            //detectTls: 'skcoco.com',
        }),
        vue(),
        VitePWA({
            registerType: 'autoUpdate', // サービスワーカーの自動更新
            //strategies: 'generateSW', 
            strategies: 'injectManifest',   // カスタムサービスワーカーを使用
            // カスタムサービスワーカーのソースディレクトリとファイル名
            srcDir: 'resources/js', 
            filename: 'custom-sw.js',
            
            scope: baseUrlPath,
            // Service Worker登録スクリプトの自動挿入を無効化
            injectManifest: {
                globPatterns: ['**/*.{js,css,png,svg}'], // precache対象 
            },
            //web.php側で動的に設定するため、不要
            manifest: {},
            //workboxはカスタムSWで設定するため機能しない
            workbox: {},
        }),
        {
            name: 'copy-custom-sw',
            buildEnd() {
                const src = path.resolve(__dirname, 'public/build/custom-sw.js');
                const dest = path.resolve(__dirname, 'public/custom-sw.js');

                if (fs.existsSync(src)) {
                    fs.copyFileSync(src, dest);
                    console.log('custom-sw.js を public/ にコピーしました');
                } else {
                    console.warn('custom-sw.js が public/build に存在しません');
                }
            }
        },
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                entryFileNames: '[name].[hash].js',
                chunkFileNames: '[name].[hash].js',
                assetFileNames: '[name].[hash][extname]',
            },
        },
    },
});
