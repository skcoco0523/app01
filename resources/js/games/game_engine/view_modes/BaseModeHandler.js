export default class BaseModeHandler {
    constructor(gameKey) {
        this.gameKey = gameKey;
    }

    /**
     * Physics（物理エンジン）の初期設定
     * @param {Phaser.Scene} scene 
     * @param {object} stageData
     */
    initPhysics(scene, stageData) {
        // デフォルトは重力あり
        scene.physics.world.gravity.y = 800;

        let worldWidth = 2000;
        let worldHeight = 600;
        const stageScale = scene.stageScale || 1.0;

        if (stageData && stageData.custom_settings && stageData.custom_settings.base) {
            const base = stageData.custom_settings.base;
            worldWidth = (base.width || 2000) * stageScale;
            worldHeight = (base.height || 600) * stageScale;
        }
        
        // 物理ワールドの境界を厳密に設定
        scene.physics.world.setBounds(0, 0, worldWidth, worldHeight);
        
        // 操作データ初期化
        this.inputData = {
            rightArea: {
                startX: 0,
                startY: 0,
                startTime: 0,
                isDown: false
            }
        };

        scene.input.on('pointerdown', (pointer) => {
            const screenWidth = scene.cameras.main.width;
            if (pointer.x >= screenWidth / 2) {
                // 右側エリア
                this.inputData.rightArea.startX = pointer.x;
                this.inputData.rightArea.startY = pointer.y;
                this.inputData.rightArea.startTime = scene.time.now;
                this.inputData.rightArea.isDown = true;
            } else {
                // 左側エリア: アイコン位置を固定するために開始位置を記録
                // このポインターIDを追跡して、右画面にはみ出しても移動として扱う
                scene.leftTouchId = pointer.id;
                scene.leftTouchStartPos = { x: pointer.x, y: pointer.y };
            }
        });

        scene.input.on('pointerup', (pointer) => {
            const screenWidth = scene.cameras.main.width;
            
            // 右側エリアの判定
            if (this.inputData.rightArea.isDown && pointer.id !== scene.leftTouchId) {
                const duration = scene.time.now - this.inputData.rightArea.startTime;
                const distX = pointer.x - this.inputData.rightArea.startX;
                const distY = pointer.y - this.inputData.rightArea.startY;
                const threshold = 50;

                if (duration < 300) {
                    if (Math.abs(distX) > threshold || Math.abs(distY) > threshold) {
                        // スワイプ
                        if (Math.abs(distX) > Math.abs(distY)) {
                            this.handleRightAction(scene, distX > 0 ? 'right' : 'left');
                        } else {
                            this.handleRightAction(scene, distY > 0 ? 'down' : 'up');
                        }
                    } else {
                        // タップ
                        this.handleRightAction(scene, 'tap');
                    }
                }
                this.inputData.rightArea.isDown = false;
            }

            // 左側タッチ終了のクリア
            if (pointer.id === scene.leftTouchId) {
                scene.leftTouchId = null;
                scene.leftTouchStartPos = null;
            }
        });
    }

    /**
     * 右側エリアのアクション分岐
     * @param {Phaser.Scene} scene 
     * @param {string} type 'tap'|'right'|'left'|'up'|'down'
     */
    handleRightAction(scene, type) {
        console.log(`Right area action: ${type}`);
        
        switch (type) {
            case 'tap':
                // タップはジャンプ
                if (scene.player && scene.player.body && scene.player.body.touching.down) {
                    scene.player.body.setVelocityY(-600);
                }
                break;
            case 'right':
                this.triggerAttack(scene, 'right');
                break;
            case 'left':
                this.triggerAttack(scene, 'left');
                break;
            case 'up':
                // 将来用
                break;
            case 'down':
                // 将来用
                break;
        }
    }

    triggerAttack(scene, direction) {
        if (scene.player) {
            scene.updateHitbox(direction === 'right' ? 1 : -1);
            
            scene.isAttacking = true;
            scene.attackTimer = 0;
            scene.animFrame = 0;
        }
    }

    /**
     * プレイヤーの移動制御ロジック
     * @param {Phaser.Scene} scene 
     * @param {Phaser.GameObjects.Container} player 
     * @param {object} cursors 
     * @param {number} delta 
     */
    updatePlayer(scene, player, cursors, delta) {
        if (!player || !player.body) return;

        const speed = 300;
        const jumpVelocity = -600;
        const config = window.GAME_CONFIG;
        const screenWidth = scene.cameras.main.width;

        let moveLeft = cursors.left.isDown;
        let moveRight = cursors.right.isDown;
        let wantJump = cursors.up.isDown || cursors.space.isDown;

        // タッチ入力の処理
        const pointers = [scene.input.pointer1, scene.input.pointer2, scene.input.activePointer];
        pointers.forEach(p => {
            if (p && p.isDown) {
                if (config.orientation === 'landscape') {
                    // 移動用のポインター（左側で開始したもの）かどうかの判定
                    if (p.id === scene.leftTouchId) {
                        // 左側で開始したポインターなら、右画面にはみ出しても移動として扱う
                        if (scene.leftTouchStartPos) {
                            const diffX = p.x - scene.leftTouchStartPos.x;
                            const deadzone = 10;
                            if (diffX < -deadzone) moveLeft = true;
                            else if (diffX > deadzone) moveRight = true;
                        }
                    } else if (p.x >= screenWidth / 2) {
                        // 右半分エリア（かつ移動用ポインターでない）はジャンプ
                        wantJump = true;
                    }
                } else {
                    // 縦画面：従来通りの二分法
                    if (p.x < screenWidth / 2) moveLeft = true;
                    else moveRight = true;
                }
            }
        });

        // 攻撃中の制御
        if (scene.isAttacking) {
            player.body.setVelocityX(0);
            scene.playAnimation('attack', delta);
            scene.attackTimer += delta;
            if (scene.attackTimer > 500) {
                scene.isAttacking = false;
                scene.resetPose();
            }
            return;
        }

        player.body.setVelocityX(0);
        let isMoving = false;

        if (moveLeft) {
            player.body.setVelocityX(-speed);
            scene.updateHitbox(-1);
            isMoving = true;
        } else if (moveRight) {
            player.body.setVelocityX(speed);
            scene.updateHitbox(1);
            isMoving = true;
        }

        if (isMoving && player.body.touching.down) {
            scene.playAnimation('walk', delta);
        } else if (!isMoving && player.body.touching.down) {
            scene.resetPose();
        }

        if (wantJump && player.body.touching.down) {
            player.body.setVelocityY(jumpVelocity);
        }
    }

    /**
     * カメラの設定
     * @param {Phaser.Scene} scene 
     * @param {Phaser.GameObjects.Container} player 
     * @param {object} stageData
     */
    setupCamera(scene, player, stageData) {
        let worldWidth = 2000;
        let worldHeight = scene.cameras.main.height;
        const stageScale = scene.stageScale || 1.0;

        if (stageData && stageData.custom_settings && stageData.custom_settings.base) {
            const base = stageData.custom_settings.base;
            worldWidth = (base.width || 2000) * stageScale;
            worldHeight = (base.height || 600) * stageScale;
        }

        scene.cameras.main.setBounds(0, 0, worldWidth, worldHeight);
        scene.cameras.main.startFollow(player, true, 0.1, 0.1);
        scene.cameras.main.setFollowOffset(0, 0);
    }
}
