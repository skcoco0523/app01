// games/common/asset_manifest.js

// 全ゲームの素材（アセット）を一括管理する中央目録
const ASSET_MANIFEST = {
    twin_facer: [
        {
            key: 'twin_player',
            type: 'spritesheet',
            path: 'img/sprite_sheet/twin_player1_1024.png',
            config: { frameWidth: 256, frameHeight: 256 }
        }
        // 💡 今後別のゲームを作ったら、ここに設定を書き足すだけで一発ロードできるようになります
        // space_shooter: [ { key: 'ship', type: 'image', path: 'img/ship.png' } ]
    ]
};

export const AssetLoader = {
    /**
     * 指定されたゲーム名に必要なアセットを目録から自動で引き抜いてロードする
     * @param {Phaser.Scene} scene - 実行元のPhaserシーン (this)
     * @param {string} gameName - 管理コード (例: 'twin_facer')
     */
    load(scene, gameName) {
        const list = ASSET_MANIFEST[gameName];
        if (!list) {
            console.error(`指定されたゲーム名のアセット目録が見つかりません: ${gameName}`);
            return;
        }

        // 共通のベースパスを自動計算
        const basePath = window.location.pathname.split('/games/')[0];

        list.forEach(asset => {
            const fullPath = `${basePath}/public/${asset.path}`;

            switch (asset.type) {
                case 'spritesheet':
                    scene.load.spritesheet(asset.key, fullPath, asset.config);
                    break;
                case 'image':
                    scene.load.image(asset.key, fullPath);
                    break;
                case 'audio':
                    scene.load.audio(asset.key, fullPath);
                    break;
            }
        });
    }
};