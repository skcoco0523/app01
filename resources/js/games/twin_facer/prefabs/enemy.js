// games/twin_facer/prefabs/enemy.js

export default class Enemy extends Phaser.Physics.Arcade.Sprite {
    constructor(scene, x, y, type, patrolRange) {
        // 物理本体は透明にして生成
        super(scene, x, y, 'transparent');
        scene.add.existing(this);
        scene.physics.add.existing(this);
        this.setSize(32, 40);

        this.enemyType = type;
        this.startX = x;
        this.range = patrolRange;
        this.moveDir = 1;

        // 見た目のベクターコンテナを自前で構築
        this.visual = scene.add.container(x, y);
        const color = type === 'right' ? 0xffd700 : 0xbd00ff;
        const body = scene.add.circle(0, 0, 16, 0x0e1017).setStrokeStyle(2, color, 1);
        const core = scene.add.circle(0, 0, 8, color, 0.3); 
        this.eye = scene.add.circle(5, -4, 4, 0xff3333);
        this.visual.add([body, core, this.eye]);
        
        // フワフワ上下に浮く演出アニメ
        scene.tweens.add({
            targets: this.visual, y: y - 6, duration: 500 + Math.random() * 200, yoyo: true, repeat: -1, ease: 'Sine.easeInOut'
        });
    }

    /**
     * 毎フレームのパトロールAIと見た目の同期
     */
    patrol() {
        if (!this.body) return;

        if (this.x > this.startX + this.range) { this.moveDir = -1; }
        else if (this.x < this.startX - this.range) { this.moveDir = 1; }
        
        this.body.setVelocityX(this.moveDir * 50);

        if (this.visual && this.visual.scene) {
            this.eye.setX(this.moveDir * 5); // 進む方向に視線を合わせる
            this.visual.setPosition(this.x, this.y);
        }
    }

    /**
     * 撃破された時のコンテナ消去処理のオーバーライド
     */
    destroy() {
        if (this.visual) this.visual.destroy();
        super.destroy();
    }
}