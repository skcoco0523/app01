import BaseModeHandler from './BaseModeHandler.js';

export default class VerticalAutoScrollHandler extends BaseModeHandler {
    initPhysics(scene, stageData) {
        super.initPhysics(scene, stageData);
        
        // Twin Facer的な重力設定
        scene.physics.world.gravity.y = 800;

        const stageBase = (stageData && stageData.custom_settings && stageData.custom_settings.base) ? stageData.custom_settings.base : {};
        const worldHeight = (stageBase.height || 5000);
        const worldWidth = (stageBase.width || 450);
        
        scene.physics.world.setBounds(0, 0, worldWidth, worldHeight);
        
        // Twin Facer用の状態初期化
        scene.autoScrollY = worldHeight - scene.cameras.main.height;
        scene.isScrollStarted = false;
        scene.currentSide = 'right';
        scene.isDiving = false;
        
        // カスタム設定の反映
        this.scrollSpeed = stageBase.scrollSpeed || 0.4;
        this.boundsLeft = stageBase.boundsLeft || 0;
        this.boundsRight = stageBase.boundsRight || worldWidth;
    }

    updatePlayer(scene, player, cursors, delta) {
        if (!player || !player.body) return;

        const speed = 300;
        const jumpVelocity = -600;

        // 左右移動（Twin Facerはオートランだが、汎用版は一旦入力を受け付ける）
        player.body.setVelocityX(0);
        if (cursors.left.isDown) {
            player.body.setVelocityX(-speed);
            scene.updateHitbox(-1);
        } else if (cursors.right.isDown) {
            player.body.setVelocityX(speed);
            scene.updateHitbox(1);
        }

        if (cursors.up.isDown && player.body.touching.down) {
            player.body.setVelocityY(jumpVelocity);
        }

        // オートスクロール処理
        if (scene.isScrollStarted) {
            scene.autoScrollY -= this.scrollSpeed;
        }
        scene.cameras.main.scrollY = Math.round(scene.autoScrollY);

        // 画面外（下）に落ちたらゲームオーバー
        if (player.y > scene.autoScrollY + scene.cameras.main.height) {
            // scene.triggerGameOver(); // TODO: GameScene側に実装
        }

        // 基本的な左右移動やアニメーション制御はBaseModeHandlerを拡張するか、
        // Twin Facer特有の「オートラン」にするかを設定で切り替える
        
        // 簡易的な実装（Twin Facerロジックの移植）
        if (player.body.touching.down) {
            if (!scene.isScrollStarted && player.y < scene.physics.world.bounds.height - 100) {
                scene.isScrollStarted = true;
            }
        }

        // 境界制限
        if (player.x < this.boundsLeft) {
            player.x = this.boundsLeft;
            player.body.setVelocityX(0);
        }
        if (player.x > this.boundsRight) {
            player.x = this.boundsRight;
            player.body.setVelocityX(0);
        }

        // 共通のアニメーション再生
        if (!player.body.touching.down) {
            scene.playAnimation(player.body.velocity.y < 0 ? 'jump' : 'fall', delta);
        } else if (player.body.velocity.x !== 0) {
            scene.playAnimation('walk', delta);
        } else {
            scene.resetPose();
        }
    }

    setupCamera(scene, player, stageData) {
        // 縦スクロールは startFollow を使わず、update内で手動制御する場合が多いが、
        // プレイヤーを中央に捉えたい場合は設定する
        scene.cameras.main.setBounds(0, 0, scene.physics.world.bounds.width, scene.physics.world.bounds.height);
    }
}
