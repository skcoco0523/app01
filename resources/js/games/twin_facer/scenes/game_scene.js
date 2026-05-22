// games/twin_facer/scenes/game_scene.js
import { Synth } from '../../common/sound_manager.js';
import { InputController } from '../../common/input_controller.js';
import { AssetLoader } from '../../common/asset_manifest.js';
import { createAnimations } from '../utils/animations.js';
import Player from '../prefabs/player.js';      
import Platforms from '../stage/platforms.js';  
import Environment from '../stage/environment.js';

export default class GameScene extends Phaser.Scene {
    constructor() {
        super('GameScene');
    }

    init() {
        // 設定値のバインド
        this.SETTINGS = {
            maxHp: 3, timeLimit: 60, enableDoubleJump: true, enemyCount: 10,
            scrollSpeed: 0.4, playerScale: 0.3, hitboxWidth: 40, hitboxHeight: 40,
            footY: 215, idleToFrontTime: 3000
        };
        this.WORLD_HEIGHT = (this.SETTINGS.enemyCount * 140) + 600;

        // 状態のリセット
        this.gameState = 'PLAYING'; this.score = 0; this.combo = 0; this.isInvincible = false;
        this.currentSide = 'right'; this.isDiving = false; this.isFrontMode = true;
        this.lastInputTime = 0; this.lastAttackTime = 0; this.lastComboTime = 0;
        this.autoScrollY = this.WORLD_HEIGHT - 700; this.isScrollStarted = false;
        
        // 🌟【重要】消えていたライフ（HP）の初期化をここで確実に実行！
        this.hp = this.SETTINGS.maxHp;
    }

    preload() {
        AssetLoader.load(this, 'twin_facer');
    }

    create() {
        const invisibleCanvas = document.createElement('canvas');
        invisibleCanvas.width = 1; invisibleCanvas.height = 1;
        this.textures.addCanvas('transparent', invisibleCanvas);
        
        // グループの初期化
        this.platforms = this.physics.add.staticGroup();
        this.crystals = this.physics.add.group();
        this.enemies = this.physics.add.group();
        this.arrows = this.physics.add.group();

        this.cameras.main.scrollY = this.autoScrollY;

        // アニメーション・環境・マップ生成の委託
        createAnimations(this);
        this.environment = new Environment(this);
        Platforms.generate(this, this.SETTINGS.enemyCount, 140, this.WORLD_HEIGHT);

        // プレイヤーの召喚
        this.player = new Player(this, 225, this.WORLD_HEIGHT - 60, this.SETTINGS.playerScale, this.SETTINGS.hitboxWidth, this.SETTINGS.hitboxHeight, this.SETTINGS.footY);

        // コントローラーの接続
        InputController.init(this, { mode: 'split', invertSides: false, moveZoneRatio: 0.5 });
        this.setupControllerEvents();

        // 当たり判定・衝突ルールの設定
        this.physics.add.collider(this.player, this.platforms);
        this.physics.add.collider(this.enemies, this.platforms);
        this.physics.add.collider(this.crystals, this.platforms);
        this.setupOverlapRules();

        // UIとタイマー
        this.gameTime = this.SETTINGS.timeLimit;
        this.setupUI();
    }

    setupControllerEvents() {
        InputController.on('onFirstTouch', () => Synth.init());
        InputController.on('onAnyInput', () => { this.lastInputTime = this.time.now; this.isFrontMode = false; });
        InputController.on('onTap', () => this.player.jump(this.SETTINGS.enableDoubleJump, this.getThunderColor()));
        InputController.on('onSwipeUp', () => this.player.jump(this.SETTINGS.enableDoubleJump, this.getThunderColor()));
        InputController.on('onSwipeDown', () => { 
            if (!this.player.body.touching.down && this.player.jumpCount < 2 && !this.isDiving) this.player.startDive(); 
            else this.toggleDirection(); 
        });
        InputController.on('onSwipeRight', () => { this.setForm('right'); this.attack('right'); });
        InputController.on('onSwipeLeft', () => { this.setForm('left'); this.attack('left'); });
    }

    setupOverlapRules() {
        // 矢が敵に当たった
        this.physics.add.overlap(this.arrows, this.enemies, (arrow, enemy) => {
            if (enemy.enemyType === 'left') { 
                this.updateCombo(); this.killEnemy(enemy, 0xbd00ff); this.cameras.main.flash(40, 189, 0, 255, 0.15);
            } else {
                Environment.createExplosion(this, arrow.x, arrow.y, 0xffffff, 4, 150); Synth.play(120, 'sawtooth', 0.05, 0.05);
            }
            // 🌟【修正】敵に当たった際、見た目のグラフィックコンテナも連動して消去する
            if (arrow.visual) arrow.visual.destroy();
            arrow.destroy();
        });

        // プレイヤーが敵に接触した
        this.physics.add.overlap(this.player, this.enemies, (p, enemy) => {
            if (this.gameState !== 'PLAYING') return;
            let color = this.getThunderColor();
            if (this.isDiving) { this.updateCombo(); this.killEnemy(enemy, color); this.score += 150; return; }
            if (this.isInvincible) return;
            
            if (p.body.velocity.y > 0 && p.y < enemy.y - 18 && this.currentSide === enemy.enemyType) {
                this.updateCombo(); this.killEnemy(enemy, color); p.setVelocityY(-550);
                this.tweens.killTweensOf(p); p.setScale(this.SETTINGS.playerScale);
            } else {
                this.handlePlayerDamage(p, enemy);
            }
        });

        // クリスタル回収
        this.physics.add.overlap(this.player, this.crystals, (p, c) => {
            c.destroy(); Synth.coin(); this.score += 300; this.uiScoreText.setText('SCORE: ' + this.score);
            Environment.createExplosion(this, c.x, c.y, 0x00ffcc, 10, 200);
        });

        // ゴール到達
        this.physics.add.overlap(this.player, this.goalPortal, () => { if (this.gameState === 'PLAYING' && !this.isFrontMode) this.triggerStageClear(); });
    }

    update(time, delta) {
        if (this.gameState !== 'PLAYING' || !this.player || !this.player.body) return; 

        if (time - this.lastComboTime > 2500) this.uiComboText.setVisible(false);
        if (this.isScrollStarted) this.autoScrollY -= this.SETTINGS.scrollSpeed;
        this.cameras.main.scrollY = this.autoScrollY;

        if (this.player.y > this.autoScrollY + 800 - 15) { this.triggerGameOver(); return; }

        if (this.player.body.touching.down) {
            this.handlePlayerLanding();
        }

        if (this.player.x < 19) { this.player.x = 19; this.player.setVelocityX(0); }
        if (this.player.x > 431) { this.player.x = 431; this.player.setVelocityX(0); }
        if (this.isDiving) { this.player.setVelocityX(0); Environment.createExplosion(this, this.player.x, this.player.y + 10, this.getThunderColor(), 2, 80); }

        this.environment.update(this.currentSide, this.isScrollStarted, this.SETTINGS.scrollSpeed, this.player.body.velocity.y);

        const currentAnimKey = this.player.anims.currentAnim ? this.player.anims.currentAnim.key : '';
        const isInterruptRunning = this.player.anims.isPlaying && (currentAnimKey.endsWith('_attack') || currentAnimKey.endsWith('_damage'));

        if (!this.isDiving && !isInterruptRunning && this.player.body.touching.down && this.player.body.velocity.x === 0) {
            if (time - this.lastInputTime > this.SETTINGS.idleToFrontTime) this.isFrontMode = true;
        }

        if (!this.isDiving && !isInterruptRunning) {
            let vx = this.player.body.velocity.x; let vy = this.player.body.velocity.y;
            if (this.isFrontMode) {
                this.player.play('front', true); this.player.setFlipX(false);
            } else {
                if (!this.player.body.touching.down) this.player.play(vy < 0 ? `${this.currentSide}_jump` : `${this.currentSide}_fall`, true);
                else if (vx !== 0) this.player.play(`${this.currentSide}_walk`, true);
                else this.player.play(`${this.currentSide}_idle`, true);
                if (vx < 0) this.player.setFlipX(true); else if (vx > 0) this.player.setFlipX(false);
            }
        }

        this.player.handleMovement(isInterruptRunning, currentAnimKey);
        this.enemies.getChildren().forEach(e => e.patrol());

        // 🌟【修正】放たれた矢のグラフィックコンテナを、移動する物理本体の位置に毎フレーム同期させる
        this.arrows.getChildren().forEach(a => {
            if (a && a.visual) a.visual.setPosition(a.x, a.y);
        });
    }

    getThunderColor() { return this.currentSide === 'right' ? 0xffd700 : 0xbd00ff; }

    toggleDirection() { this.setForm(this.currentSide === 'right' ? 'left' : 'right'); }

    setForm(targetSide) {
        if (this.currentSide === targetSide) return; this.currentSide = targetSide; Synth.shift();
        let isRight = this.currentSide === 'right';
        Environment.createExplosion(this, this.player.x, this.player.y, this.getThunderColor(), 8, 200);
        this.cameras.main.flash(80, isRight ? 255 : 120, isRight ? 210 : 0, isRight ? 0 : 255, 0.08);
        this.uiModeText.setText(`${this.currentSide.toUpperCase()} FORM`).setColor(isRight ? '#ffd700' : '#bd00ff').setShadow(0, 0, isRight ? '#ffd700' : '#bd00ff', 12);
        this.player.play(`${this.currentSide}_idle`); this.player.setFlipX(false); this.tweens.killTweensOf(this.player);
    }

    attack(side) {
        if (this.time.now < this.lastAttackTime + 350 || this.isDiving) return;
        this.lastAttackTime = this.time.now; Synth.attack();

        this.player.setFlipX(side === 'left');
        this.player.play(`${side}_attack`, true);
        this.tweens.killTweensOf(this.player); this.player.setScale(this.SETTINGS.playerScale);

        if (side === 'right') {
            const wave = this.add.circle(this.player.x + 40, this.player.y, 10, 0xffd700, 0).setStrokeStyle(3, 0xffd700, 1);
            this.tweens.add({ targets: wave, radius: 85, alpha: 0, duration: 180, onComplete: () => wave.destroy() });
            this.enemies.getChildren().slice().forEach(e => { if (e && e.active && Phaser.Math.Distance.Between(this.player.x + 40, this.player.y, e.x, e.y) < 85) { if (e.enemyType === 'right') { this.updateCombo(); this.killEnemy(e, 0xffd700); } else { Environment.createExplosion(this, e.x, e.y, 0xffffff, 4, 120); Synth.play(150, 'sawtooth', 0.05, 0.05); } } });
        } else {
            const arrow = this.physics.add.sprite(this.player.x - 20, this.player.y, 'transparent').setSize(35, 12);
            arrow.visual = this.add.container(0, 0); arrow.visual.add([this.add.rectangle(0, 0, 35, 4, 0xbd00ff), this.add.rectangle(0, 0, 40, 10, 0xbd00ff, 0.25)]);
            this.arrows.add(arrow); arrow.body.setAllowGravity(false); arrow.setVelocityX(-750);
            this.tweens.add({ targets: arrow.visual, scaleY: 1.4, duration: 100, yoyo: true });
            this.time.delayedCall(1000, () => { if (arrow.visual) arrow.visual.destroy(); arrow.destroy(); });
        }
    }

    handlePlayerLanding() {
        if (this.isDiving) {
            this.isDiving = false; this.tweens.killTweensOf(this.player); this.player.setScale(this.SETTINGS.playerScale);
            Environment.createExplosion(this, this.player.x, this.player.y + 18, this.getThunderColor(), 35, 450);
            this.cameras.main.shake(250, 0.025); Synth.play([90, 45, 20], 'triangle', 0.25, 0.2);
            this.enemies.getChildren().slice().forEach(e => { if (e && e.active && Phaser.Math.Distance.Between(this.player.x, this.player.y, e.x, e.y) < 130) { this.updateCombo(); this.killEnemy(e, this.getThunderColor()); } });
        }
        this.player.jumpCount = 0; if (this.player.y < this.WORLD_HEIGHT - 80 && !this.isScrollStarted) this.isScrollStarted = true;
    }

    handlePlayerDamage(p, enemy) {
        this.hp--; this.combo = 0; this.score = Math.max(0, this.score - 250);
        this.uiHpText.setText('LIFE: ' + '❤️'.repeat(this.hp)); this.uiScoreText.setText('SCORE: ' + this.score);
        this.cameras.main.shake(250, 0.025); Synth.hit(); p.play(`${this.currentSide}_damage`);
        if (this.hp <= 0) this.triggerGameOver();
        else {
            this.isInvincible = true; p.setVelocity(p.x < enemy.x ? -450 : 450, -350);
            this.tweens.add({ targets: this.player, alpha: 0.2, duration: 80, yoyo: true, repeat: 5 });
            this.time.delayedCall(1000, () => { this.player.alpha = 1; this.isInvincible = false; });
        }
    }

    updateCombo() {
        const now = this.time.now; if (now - this.lastComboTime < 3000) this.combo++; else this.combo = 1; this.lastComboTime = now;
        if (this.combo > 1) { this.uiComboText.setText(`${this.combo} COMBO!`).setVisible(true).setScale(1.5); this.uiComboText.scene.tweens.add({ targets: this.uiComboText, scale: 1, duration: 150, ease: 'Back.easeOut' }); }
    }

    killEnemy(enemy, color) {
        Environment.createExplosion(this, enemy.x, enemy.y, color, 25, 400);
        this.score += 100 * this.combo; this.uiScoreText.setText('SCORE: ' + this.score);
        Synth.play([400, 700], 'sawtooth', 0.15, 0.1); enemy.destroy();
    }

    setupUI() {
        this.uiModeText = this.add.text(225, 45, 'RIGHT FORM', { fontFamily: 'Orbitron', fontSize: '22px', fill: '#ffd700', fontWeight: 'bold' }).setOrigin(0.5).setShadow(0,0,'#ffd700',10).setScrollFactor(0);
        this.uiScoreText = this.add.text(30, 40, 'SCORE: 0', { fontFamily: 'Orbitron', fontSize: '13px', fill: '#fff', fontWeight: 'bold' }).setScrollFactor(0);
        this.uiHpText = this.add.text(330, 40, 'LIFE: ' + '❤️'.repeat(this.hp), { fontSize: '13px' }).setScrollFactor(0);
        this.uiComboText = this.add.text(225, 150, '', { fontFamily: 'Orbitron', fontSize: '26px', fill: '#ff00ff', fontWeight: 'bold' }).setOrigin(0.5).setVisible(false).setScrollFactor(0);
        this.uiTimeText = this.add.text(225, 78, 'TIME: ' + this.gameTime + 's', { fontFamily: 'Orbitron', fontSize: '13px', fill: '#00ffff' }).setOrigin(0.5).setScrollFactor(0);
        
        this.timerEvent = this.time.addEvent({
            delay: 1000, callback: () => { 
                if (this.gameState === 'PLAYING' && this.isScrollStarted) { 
                    this.gameTime--; this.uiTimeText.setText('TIME: ' + this.gameTime + 's'); 
                    if (this.gameTime <= 0) this.triggerGameOver(); 
                } 
            }, loop: true
        });
    }

    triggerGameOver() {
        this.gameState = 'GAMEOVER'; if (this.timerEvent) this.timerEvent.destroy(); Synth.gameover(); this.player.setVelocity(0, 0); this.player.body.setAllowGravity(false);
        this.add.rectangle(225, 400, 450, 800, 0x000000, 0.85).setDepth(300).setScrollFactor(0); this.add.text(225, 340, 'GAME OVER', { fontFamily: 'Orbitron', fontSize: '38px', fill: '#ff3333', fontWeight: 'bold' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.add.text(225, 410, 'SCORE: ' + this.score, { fontFamily: 'Orbitron', fontSize: '20px', fill: '#fff' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.add.text(225, 480, 'TAP TO RETRY', { fontFamily: 'Orbitron', fontSize: '14px', fill: '#888' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.input.once('pointerdown', () => this.scene.restart());
    }

    triggerStageClear() {
        this.gameState = 'CLEAR'; if (this.timerEvent) this.timerEvent.destroy(); Synth.clear(); this.physics.world.disable(this.player); this.player.setVelocity(0, 0); const timeBonus = Math.max(0, this.gameTime * 25); const lifeBonus = this.hp * 600; this.score += (timeBonus + lifeBonus);
        this.add.rectangle(225, 400, 450, 800, 0x000000, 0.88).setDepth(300).setScrollFactor(0); this.add.text(225, 260, 'STAGE CLEAR', { fontFamily: 'Orbitron', fontSize: '38px', fill: '#00ffff', fontWeight: 'bold' }).setOrigin(0.5).setShadow(0,0,'#00ffff',15).setDepth(301).setScrollFactor(0); this.add.text(225, 350, `TIME BONUS: +${timeBonus}`, { fontFamily: 'monospace', fontSize: '16px', fill: '#ffd700' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.add.text(225, 390, `LIFE BONUS: +${lifeBonus}`, { fontFamily: 'monospace', fontSize: '16px', fill: '#ff3366' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.add.text(225, 470, 'TOTAL SCORE: ' + this.score, { fontFamily: 'Orbitron', fontSize: '24px', fill: '#fff', fontWeight: 'bold' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.add.text(225, 550, 'TAP TO REPLAY', { fontFamily: 'Orbitron', fontSize: '14px', fill: '#888' }).setOrigin(0.5).setDepth(301).setScrollFactor(0); this.input.once('pointerdown', () => this.scene.restart());
    }
}