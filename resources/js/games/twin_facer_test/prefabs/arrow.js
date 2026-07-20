import Projectile from './projectile.js';

export default class Arrow extends Projectile {
    // 🌟 修正：引数の最後に direction を追加（指定がなければデフォルト左向き）
    constructor(scene, x, y, direction = -1) {
        // 🌟 修正：speedXを正の数にし、受け取った direction を親に引き渡す
        super(scene, x, y, { width: 35, height: 12, speedX: 750, direction: direction });

        // 自分自身のユニークな見た目（紫色）だけをコンテナに追加する
        this.visual.add([
            scene.add.rectangle(0, 0, 35, 4, 0xbd00ff),
            scene.add.rectangle(0, 0, 40, 10, 0xbd00ff, 0.25)
        ]);
        
        // 🌟 追加：もし右向き（direction が 1）なら、見た目のコンテナも右に反転させる
        if (direction > 0) this.visual.setScaleX(-1);

        scene.tweens.add({ targets: this.visual, scaleY: 1.4, duration: 100, yoyo: true });
    }
}