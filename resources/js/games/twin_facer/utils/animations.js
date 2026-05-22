// games/twin_facer/utils/animations.js
/*
    * Twin Facerのアニメーション定義
    * - 画面右側のプレイヤーはフレーム1を基準に、左側のプレイヤーはフレーム9を基準にしている。
    * - 各アニメーションは、フレーム番号の配列で定義されている。
    * - 例えば、右側プレイヤーの歩行アニメーションはフレーム2,1,3,1をループする。
    * - これにより、両プレイヤーのアニメーションがシンプルかつ効率的に管理されている。
    * - もし将来、別のゲームを作る場合は、同様の構造でアニメーション定義を追加すれば簡単に拡張できる。
*/
export function createAnimations(scene) {
    const animConfigs = [
        { key: 'right', base: 1, walk: [2, 1, 3, 1], attack: [4, 1, 4, 1], jump: 5, fall: 6, damage: 7 },
        { key: 'left',  base: 9, walk: [11, 10, 12, 10], attack: [12, 9, 12, 9], jump: 13, fall: 14, damage: 15 }
    ];

    scene.anims.create({ key: `front`, frames: [{ key: 'twin_player', frame: 0 }], frameRate: 1 });
    
    animConfigs.forEach(config => {
        scene.anims.create({ key: `${config.key}_idle`,   frames: [{ key: 'twin_player', frame: config.base }], frameRate: 1 });
        scene.anims.create({ key: `${config.key}_walk`,   frames: scene.anims.generateFrameNumbers('twin_player', { frames: config.walk }), frameRate: 6, repeat: -1 });
        scene.anims.create({ key: `${config.key}_attack`, frames: scene.anims.generateFrameNumbers('twin_player', { frames: config.attack }), frameRate: 12, repeat: 0 });
        scene.anims.create({ key: `${config.key}_damage`, frames: [{ key: 'twin_player', frame: config.damage }], frameRate: 1 });
        scene.anims.create({ key: `${config.key}_jump`,   frames: [{ key: 'twin_player', frame: config.jump }], frameRate: 1 });
        scene.anims.create({ key: `${config.key}_fall`,   frames: [{ key: 'twin_player', frame: config.fall }], frameRate: 1 });
    });
}