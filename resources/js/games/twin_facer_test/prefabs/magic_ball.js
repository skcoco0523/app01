import Projectile from './projectile.js';

export default class MagicBall extends Projectile {
    // 🌟 修正：引数の最後に direction を追加
    constructor(scene, x, y, direction = -1) {
        // 🌟 修正：speedXを正の数にし、受け取った direction を親に引き渡す
        super(scene, x, y, { width: 20, height: 20, speedX: 500, lifespan: 1500, direction: direction });

        // 水色の綺麗な丸いエネルギー弾の見た目にする
        this.visual.add([
            scene.add.circle(0, 0, 10, 0x00ffff),
            scene.add.circle(0, 0, 14, 0x00ffff, 0.3)
        ]);
        
        // 🌟 魔法弾は丸型なので左右反転（setScaleX）は無くても見た目に影響しませんが、一応あってもOKです
    }
}