// games/common/asset_manifest.js

// 全ゲームの素材（アセット）を一括管理する中央目録
const ASSET_MANIFEST = {
    twin_facer: [
        // 🌟 プレイヤー1用の素材セット
        {
            key: 'player1_atlas',
            type: 'atlas',
            path: 'storage/sprite_sheet/twin_twin_facer_player1.png',
            atlasPath: 'storage/sprite_sheet/twin_twin_facer_player1_atlas.json'
        },
        {
            key: 'player1_motion',
            type: 'json',
            path: 'storage/sprite_sheet/twin_twin_facer_player1_motion.json'
        },

        // プレイヤー2、3を増やす時は、以下のようにビルドなしでここに追記する
        {
            key: 'player2_atlas',
            type: 'atlas',
            path: 'storage/sprite_sheet/twin_twin_facer_player2.png',
            atlasPath: 'storage/sprite_sheet/twin_twin_facer_player2_atlas.json'
        },
        {
            key: 'player2_motion',
            type: 'json',
            path: 'storage/sprite_sheet/twin_twin_facer_player2_motion.json'
        }
    ]
};

export const AssetLoader = {
    load(scene, gameName) {
        const list = ASSET_MANIFEST[gameName];
        if (!list) return;

        const basePath = window.location.pathname.split('/games/')[0];

        list.forEach(asset => {
            const fullPath = `${basePath}/${asset.path}`;

            switch (asset.type) {
                case 'atlas':
                    const fullAtlasPath = `${basePath}/${asset.atlasPath}`;
                    scene.load.atlas(asset.key, fullPath, fullAtlasPath);
                    break;
                case 'json':
                    scene.load.json(asset.key, fullPath);
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