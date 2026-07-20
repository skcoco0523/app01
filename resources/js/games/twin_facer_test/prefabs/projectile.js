// games/twin_facer/prefabs/projectile.js

/**
 * すべての飛び道具（矢、銃弾、魔法など）のベースとなる基底クラス
 */
export default class Projectile extends Phaser.GameObjects.Sprite {
    constructor(scene, x, y, config = {}) {
        // 物理本体は透明
        super(scene, x, y, 'transparent');
        scene.add.existing(this);
        scene.physics.add.existing(this);

        // 各武器個別の設定をマージ（デフォルトは左へ飛ぶ矢のスペック）
        this.config = Object.assign({
            width: 35, height: 12,
            speedX: 750, // 🌟 変更：基準となる速度は「正の数（右向き）」にしておく
            speedY: 0,
            lifespan: 1000,
            direction: -1 // 🌟 追加：デフォルトは左向き(-1)
        }, config);

        this.setSize(this.config.width, this.config.height);
        this.body.setAllowGravity(false);
        
        // 🌟 修正：基本速度に「向き」を掛け算することで、右（プラス）にも左（マイナス）にも対応！
        this.body.setVelocity(this.config.speedX * this.config.direction, this.config.speedY);

        // 見た目のコンテナ
        this.visual = scene.add.container(x, y);

        // 時間切れで自動消滅
        this.destroyTimer = scene.time.delayedCall(this.config.lifespan, () => this.destroy());
    }

    // 毎フレーム、物理本体と見た目を自動で完全同期（これでScene側のループ監視が不要に！）
    preUpdate(time, delta) {
        super.preUpdate(time, delta);
        if (this.visual) {
            this.visual.setPosition(this.x, this.y);
        }
    }

    // 消滅時は見た目も一緒に綺麗さっぱり消す
    destroy(fromScene) {
        if (!this.active) return;
        if (this.destroyTimer) this.destroyTimer.destroy();
        if (this.visual) this.visual.destroy();
        super.destroy(fromScene);
    }
}