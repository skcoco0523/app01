import BaseModeHandler from './BaseModeHandler.js';

export default class TopDownHandler extends BaseModeHandler {
    initPhysics(scene, stageData) {
        // 見下ろし型は重力なし
        scene.physics.world.gravity.y = 0;

        if (stageData && stageData.custom_settings && stageData.custom_settings.base) {
            const base = stageData.custom_settings.base;
            const worldWidth = base.width || 2000;
            const worldHeight = base.height || 2000;
            scene.physics.world.setBounds(0, 0, worldWidth, worldHeight);
        }
    }

    updatePlayer(scene, player, cursors, delta) {
        if (!player || !player.body) return;

        const speed = 300;
        player.body.setVelocity(0);

        let isMoving = false;
        let directionX = 0;
        let directionY = 0;

        if (cursors.left.isDown) {
            directionX = -1;
            isMoving = true;
        } else if (cursors.right.isDown) {
            directionX = 1;
            isMoving = true;
        }

        if (cursors.up.isDown) {
            directionY = -1;
            isMoving = true;
        } else if (cursors.down.isDown) {
            directionY = 1;
            isMoving = true;
        }

        if (isMoving) {
            // 斜め移動の速度補正
            const velocity = new Phaser.Math.Vector2(directionX, directionY).normalize().scale(speed);
            player.body.setVelocity(velocity.x, velocity.y);
            
            // 向きに合わせて反転処理
            if (directionX !== 0) {
                scene.updateHitbox(directionX);
            }
            
            scene.playAnimation('walk', delta);
        } else {
            scene.resetPose();
        }
    }

    setupCamera(scene, player, stageData) {
        let worldWidth = 2000;
        let worldHeight = 2000;

        if (stageData && stageData.custom_settings && stageData.custom_settings.base) {
            const base = stageData.custom_settings.base;
            worldWidth = base.width || 2000;
            worldHeight = base.height || 2000;
        }

        scene.cameras.main.setBounds(0, 0, worldWidth, worldHeight);
        scene.cameras.main.startFollow(player, true, 0.1, 0.1);
    }
}
