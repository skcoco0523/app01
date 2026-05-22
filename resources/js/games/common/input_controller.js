// resources/js\games\common\input_controller.js

/**
 * コントローラーの初期化とエリアの自動展開
 * @param {Phaser.Scene} scene - 展開するPhaserのシーン
 * @param {Object} userConfig - 設定パラメータ
 * * --- mode の3つのパターン ---
 * 1. 'split'       [画面分割] 移動とアクションを両方使うモード（ツインフェイサー仕様）
 * - 　　　　　　　　画面の半分で移動（ジョイスティック）、もう半分でアクション（タップ/スワイプ）を検知。
 * - 　　　　　　　　向いているゲーム：2Dアクション、ベルトスクロール、横スクロールアクションなど
 * 
 * * 2. 'full_move' [全画面移動] どこを触っても移動になるジョイスティック単体モード
 * - 　　　　　　　　画面全体が移動エリアになり、アクション判定は行わない（ジョイスティックがどこでも出る）。
 * - 　　　　　　　　向いているゲーム：全方位シューティング、迷路脱出RPG、レーシング、お買い物ゲームなど
 * 
 * * 3. 'full_action' [全画面アクション] ジョイスティック無しのジェスチャー特化モード
 * - 　　　　　　　　　ジョイスティックは表示されず、画面全体のタップや上下左右のスワイプ方向だけを検知。
 * - 　　　　　　　　　向いているゲーム：落ち物パズル、フリック入力ゲーム、ターン制コマンドバトルなど
 */
export const InputController = {
    moveLeft: false,
    moveRight: false,

    _scene: null,
    _config: null,
    _events: {},
    _joystickBase: null,
    _joystickTip: null,
    _joystickActive: false,
    _joystickStartPos: { x: 0, y: 0 },
    _gestureStartX: 0,
    _gestureStartY: 0,
    _gestureRecognized: false,
    _gestureTimer: null,

    init(scene, userConfig = {}) {
        this._scene = scene;
        this.moveLeft = false;
        this.moveRight = false;
        this._events = {};
        this._joystickActive = false;

        this._config = Object.assign({
            mode: 'split',         
            invertSides: false,    
            moveZoneRatio: 0.5,    
            moveThreshold: 12,     
            swipeThreshold: 15     
        }, userConfig);

        const W = scene.cameras.main.width;
        const H = scene.cameras.main.height;

        if (this._config.mode !== 'full_action') {
            this._joystickBase = scene.add.circle(0, 0, 55, 0x00ffff, 0).setStrokeStyle(2, 0x00ffff, 0.25).setVisible(false).setDepth(99).setScrollFactor(0);
            this._joystickTip = scene.add.circle(0, 0, 24, 0x00ffff, 0.15).setStrokeStyle(2, 0x00ffff, 0.6).setVisible(false).setDepth(99).setScrollFactor(0);
        }

        this._setupZones(W, H);
    },

    on(eventName, callback) {
        this._events[eventName] = callback;
    },

    _trigger(eventName, ...args) {
        if (this._events[eventName]) this._events[eventName](...args);
    },

    _setupZones(W, H) {
        const conf = this._config;
        let moveBounds = null, actionBounds = null;

        if (conf.mode === 'full_move') {
            moveBounds = { x: W / 2, y: H / 2, w: W, h: H };
        } else if (conf.mode === 'full_action') {
            actionBounds = { x: W / 2, y: H / 2, w: W, h: H };
        } else {
            const moveW = W * conf.moveZoneRatio;
            const actionW = W * (1 - conf.moveZoneRatio);

            if (conf.invertSides) {
                actionBounds = { x: actionW / 2, y: H / 2, w: actionW, h: H };
                moveBounds = { x: actionW + moveW / 2, y: H / 2, w: moveW, h: H };
            } else {
                moveBounds = { x: moveW / 2, y: H / 2, w: moveW, h: H };
                actionBounds = { x: moveW + actionW / 2, y: H / 2, w: actionW, h: H };
            }
        }

        const scene = this._scene;
        if (moveBounds) {
            const moveZone = scene.add.rectangle(moveBounds.x, moveBounds.y, moveBounds.w, moveBounds.h, 0x000000, 0.001).setInteractive().setScrollFactor(0);
            this._bindMoveEvents(moveZone);
        }
        if (actionBounds) {
            const actionZone = scene.add.rectangle(actionBounds.x, actionBounds.y, actionBounds.w, actionBounds.h, 0x000000, 0.001).setInteractive().setScrollFactor(0);
            this._bindActionEvents(actionZone);
        }
    },

    _bindMoveEvents(zone) {
        zone.on('pointerdown', (p) => {
            this._trigger('onFirstTouch');
            this._joystickActive = true;
            this._joystickStartPos = { x: p.x, y: p.y };
            this._joystickBase.setPosition(p.x, p.y).setVisible(true);
            this._joystickTip.setPosition(p.x, p.y).setVisible(true);
        });

        zone.on('pointermove', (p) => {
            if (!this._joystickActive) return;
            const dist = Phaser.Math.Distance.Between(this._joystickStartPos.x, this._joystickStartPos.y, p.x, p.y);
            const angle = Phaser.Math.Angle.Between(this._joystickStartPos.x, this._joystickStartPos.y, p.x, p.y);
            
            this._joystickTip.x = this._joystickStartPos.x + Math.cos(angle) * Math.min(dist, 45);
            this._joystickTip.y = this._joystickStartPos.y + Math.sin(angle) * Math.min(dist, 45);
            
            const dx = this._joystickTip.x - this._joystickStartPos.x;
            this.moveLeft = dx < -this._config.moveThreshold;
            this.moveRight = dx > this._config.moveThreshold;
            if (this.moveLeft || this.moveRight) this._trigger('onAnyInput');
        });

        const stopJoystick = () => {
            this._joystickActive = false;
            this._joystickBase.setVisible(false);
            this._joystickTip.setVisible(false);
            this.moveLeft = this.moveRight = false;
        };
        zone.on('pointerup', stopJoystick);
        zone.on('pointerout', stopJoystick);
    },

    _bindActionEvents(zone) {
        const scene = this._scene;
        const conf = this._config;

        zone.on('pointerdown', (p) => {
            this._trigger('onFirstTouch');
            this._gestureStartX = p.x;
            this._gestureStartY = p.y;
            this._gestureRecognized = false;

            if (this._gestureTimer) this._gestureTimer.destroy();
            this._gestureTimer = scene.time.delayedCall(100, () => {
                if (!this._gestureRecognized) {
                    this._gestureRecognized = true;
                    this._trigger('onTap');
                }
            });
        });

        zone.on('pointermove', (p) => {
            if (this._gestureRecognized) return;
            const deltaX = p.x - this._gestureStartX;
            const deltaY = p.y - this._gestureStartY;

            if (Math.abs(deltaX) > conf.swipeThreshold || Math.abs(deltaY) > conf.swipeThreshold) {
                this._gestureRecognized = true;
                if (this._gestureTimer) this._gestureTimer.destroy();

                if (Math.abs(deltaX) > Math.abs(deltaY)) {
                    this._trigger(deltaX > 0 ? 'onSwipeRight' : 'onSwipeLeft');
                } else {
                    this._trigger(deltaY > 0 ? 'onSwipeDown' : 'onSwipeUp');
                }
            }
        });

        zone.on('pointerup', () => {
            if (this._gestureTimer) this._gestureTimer.destroy();
            if (!this._gestureRecognized) {
                this._gestureRecognized = true;
                this._trigger('onTap');
            }
        });
    }
};