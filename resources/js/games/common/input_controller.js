// resources/js/games/common/input_controller.js

/**
 * 全画面ジェスチャー特化コントローラー（オートラン仕様）
 * 画面全体のタップでジャンプ、上下左右のスワイプを検知。
 */
export const InputController = {
    // 自動移動の方向を外部から制御できるようにフラグだけ残す
    moveLeft: false,
    moveRight: false,

    _scene: null,
    _config: null,
    _events: {},
    _zone: null,
    _gestureStartX: 0,
    _gestureStartY: 0,
    _gestureRecognized: false,
    _gestureTimer: null,

    init(scene, userConfig = {}) {
        this._scene = scene;
        this.moveLeft = false;
        this.moveRight = false;
        this._events = {};

        if (this._zone) {
            this._zone.destroy();
            this._zone = null;
        }

        this._config = Object.assign({
            swipeThreshold: 15
        }, userConfig);

        const W = scene.cameras.main.width;
        const H = scene.cameras.main.height;

        // 🌟 修正：rectangleだと拡大縮小時に黒い境界線が映り込むバグがあるため、描画を行わないインプット専用のZoneオブジェクトに変更
        this._zone = scene.add.zone(W / 2, H / 2, W, H)
            .setInteractive()
            .setScrollFactor(0);

        scene.events.once('shutdown', () => {
            if (this._zone) { this._zone.destroy(); this._zone = null; }
        });

        this._bindActionEvents(this._zone);
    },

    on(eventName, callback) {
        this._events[eventName] = callback;
    },

    _trigger(eventName, ...args) {
        if (this._events[eventName]) this._events[eventName](...args);
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

                this._trigger('onAnyInput'); // スワイプ時に入力ありを通知

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