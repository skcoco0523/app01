// games/twin_facer/stage/platforms.js
import Enemy from '../prefabs/enemy.js';

export default class Platforms {
    /**
     * マップ上のすべての足場、敵、クリスタルを自動でビルドする
     */
    static generate(scene, count, gap, worldHeight) {
        // 大元の地面
        const ground = scene.add.rectangle(225, worldHeight - 8, 450, 16, 0x070913).setStrokeStyle(2, 0x00ffff, 0.3);
        scene.platforms.add(ground);
        
        let currentBuildY = worldHeight - 160; 

        for (let i = 0; i < count; i++) {
            let x = (i % 2 === 0) ? 120 : 330;
            let w = 160;
            
            // 空中の足場を薄い12pxで生成
            const plat = scene.add.rectangle(x, currentBuildY, w, 12, 0x0b0d16).setStrokeStyle(2, 0x222943, 1);
            scene.platforms.add(plat);
            
            // 敵の配置
            let enemyType = (i % 2 === 0) ? 'left' : 'right'; 
            let enemy = new Enemy(scene, x, currentBuildY - 35, enemyType, 40);
            scene.enemies.add(enemy);
            
            // クリスタルのランダム生成
            if (Phaser.Math.Between(0, 10) > 3) {
                let cry = scene.add.rectangle(x + Phaser.Math.Between(-30, 30), currentBuildY - 32, 13, 13, 0x00ffcc).setStrokeStyle(1.5, 0xffffff, 0.9);
                cry.angle = 45; 
                scene.physics.add.existing(cry);
                cry.body.setAllowGravity(false);
                scene.tweens.add({ targets: cry, scaleX: -1, duration: 1200, yoyo: true, repeat: -1, ease: 'Sine.easeInOut' });
                scene.crystals.add(cry);
            }
            currentBuildY -= gap; 
        }

        const goalPortal = scene.add.circle(225, currentBuildY, 24, 0x00ffff, 0).setStrokeStyle(3, 0x00ffff, 1);
        scene.physics.add.existing(goalPortal, true);
        scene.tweens.add({ targets: goalPortal, scale: 1.2, alpha: 0.3, duration: 800, yoyo: true, repeat: -1 });
        
        // シーン側にゴールを登録して、接触判定（overlap）が動くようにバインドする
        scene.goalPortal = goalPortal;
    }
}