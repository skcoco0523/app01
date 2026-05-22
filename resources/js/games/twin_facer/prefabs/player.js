// games/twin_facer/prefabs/Player.js
import { Synth } from '../../common/sound_manager.js';
import { InputController } from '../../common/input_controller.js';
import Environment from '../stage/environment.js';

export default class Player extends Phaser.Physics.Arcade.Sprite {
    constructor(scene, x, y, scale, hitboxW, hitboxH, footY) {
        super(scene, x, y, 'twin_player', 0);
        scene.add.existing(this);
        scene.physics.add.existing(this);

        // 🌟 プレイヤー固有のこだわり設定を密閉
        this.setCollideWorldBounds(false).setDragX(1300);
        const offsetX = (256 - hitboxW) / 2;
        const offsetY = footY - hitboxH;
        this.body.setSize(hitboxW, hitboxH).setOffset(offsetX, offsetY); 
        this.setScale(scale);
        this.body.deltaMax.y = 10; // 空中床の貫通を防止するプロの制限命令

        // 内部ステート
        
        this.jumpCount = 0;
        this.playerScale = scale;
    }

    /**
     * ジャンプの入力処理
     */
    jump(enableDoubleJump, thunderColor) {
        if (this.scene.isDiving) return;

        if (this.body.touching.down) {
            this.setVelocityY(-660); 
            Synth.jump();
            this.jumpCount = 1; 
            this.scene.tweens.killTweensOf(this);
            this.setScale(this.playerScale);
        } 
        else if (enableDoubleJump && this.jumpCount < 2) {
            this.body.velocity.y = 0; 
            this.setVelocityY(-620); 
            Synth.jump();
            this.jumpCount = 2; 
            Environment.createExplosion(this.scene, this.x, this.y + 20, thunderColor, 8, 150);
            this.scene.tweens.killTweensOf(this);
            this.setScale(this.playerScale);
        }
    }

    /**
     * ヒップドロップ（急降下）の開始
     */
    startDive() {
        this.scene.isDiving = true;
        this.jumpCount = 99; 
        this.setVelocity(0, 1200); // 1200の安全かつ爽快な速度で落下
        Synth.play([120, 280, 60, 180], 'sawtooth', 0.35, 0.18);
        this.scene.cameras.main.shake(100, 0.012);
        this.scene.tweens.killTweensOf(this);
        this.setScale(this.playerScale);
    }

    /**
     * 移動の制御
     */
    handleMovement(isInterruptRunning, currentAnimKey) {
        if (this.scene.isDiving) return;

        if (isInterruptRunning && currentAnimKey.endsWith('_attack')) {
            this.setVelocityX(0); // 攻撃モーション中のスライドスライド現象をロック
        } else if (InputController.moveLeft) { 
            this.setVelocityX(-260); 
        } else if (InputController.moveRight) { 
            this.setVelocityX(260); 
        } else { 
            this.setVelocityX(0); 
        }
    }
}