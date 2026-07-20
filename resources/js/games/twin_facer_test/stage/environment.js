// games/twin_facer/stage/environment.js

export default class Environment {
    constructor(scene) {
        this.scene = scene;
        this.rainData = Array.from({ length: 25 }, () => ({
            x: Phaser.Math.Between(0, 450), y: Phaser.Math.Between(0, 800),
            len: Phaser.Math.Between(15, 40), speed: Phaser.Math.Between(6, 14), width: Phaser.Math.FloatBetween(1.5, 3)
        }));
        this.starData = Array.from({ length: 45 }, () => ({
            x: Phaser.Math.Between(0, 450), y: Phaser.Math.Between(0, 800),
            size: Phaser.Math.FloatBetween(1, 3), alpha: Phaser.Math.FloatBetween(0.3, 0.9), speed: Phaser.Math.FloatBetween(0.2, 0.6)
        }));

        this.bgParticles = scene.add.graphics().setScrollFactor(0);
    }

    /**
     * リアルタイムの背景アニメーション（星の瞬きや雨流れ）の更新
     */
    update(currentSide, isScrollStarted, scrollSpeed, playerVelocityY) {
        this.bgParticles.clear();
        let rainColor = currentSide === 'right' ? 0x00ffff : 0xbd00ff;
        let activeScrollSpeed = isScrollStarted ? scrollSpeed : 0;
        
        this.starData.forEach(s => {
            s.alpha += Phaser.Math.FloatBetween(-0.02, 0.02);
            s.alpha = Phaser.Math.Clamp(s.alpha, 0.2, 0.9);
            s.y += s.speed + activeScrollSpeed + (playerVelocityY * -0.005);
            if (s.y > 800) s.y = 0;
            this.bgParticles.fillStyle(0xffffff, s.alpha);
            this.bgParticles.fillRect(s.x, s.y, s.size, s.size);
        });

        this.rainData.forEach(r => {
            r.y += r.speed + activeScrollSpeed + (playerVelocityY * -0.02);
            if (r.y > 800) { r.y = -r.len; r.x = Phaser.Math.Between(0, 450); }
            this.bgParticles.fillStyle(rainColor, Phaser.Math.FloatBetween(0.15, 0.4));
            this.bgParticles.fillRect(r.x, r.y, r.width, r.len);
        });
    }

    /**
     * 🌟 どこからでも呼び出せる汎用エフェクト関数
     */
    static createExplosion(scene, x, y, color, count, speed) {
        for (let i = 0; i < count; i++) {
            const part = scene.add.rectangle(x, y, Phaser.Math.Between(3, 5), Phaser.Math.Between(3, 5), color);
            scene.physics.add.existing(part);
            part.body.setAllowGravity(false);
            part.body.setVelocity(Phaser.Math.Between(-speed, speed), Phaser.Math.Between(-speed, speed));
            scene.tweens.add({ targets: part, alpha: 0, scale: 0, duration: 400, onComplete: () => part.destroy() });
        }
    }
}