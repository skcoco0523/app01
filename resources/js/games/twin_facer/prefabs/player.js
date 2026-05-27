// games/twin_facer/prefabs/Player.js
import { Synth } from '../../common/sound_manager.js';
import { InputController } from '../../common/input_controller.js';
import Environment from '../stage/environment.js';

export default class Player extends Phaser.GameObjects.Container {
    // 🌟 第4引数にキャラクターを識別する一意のキー（'player1', 'player2' など）を追加
    constructor(scene, x, y, characterKey, scale, hitboxW, hitboxH, footY) {
        super(scene, x, y);
        scene.add.existing(this);
        scene.physics.add.existing(this);

        this.characterKey = characterKey; // 内部にキャラクターキーを保持

        // 既存の game_scene.js との完全な互換性を維持するモック構造
        this.anims = {
            isPlaying: false,
            currentAnim: { key: '' },
            play: (key, ignoreIfPlaying) => this.play(key, ignoreIfPlaying)
        };

        this._flipX = false;
        this.playerScale = scale;
        this.jumpCount = 0;
        this.currentForm = 'right';

        // 🌟 固定文字列ではなく、指定されたキャラクター専用のモーションJSONデータを動的に取得
        this.motionData = scene.cache.json.get(`${this.characterKey}_motion`);
        this.partsMap = {};

        // 外出しJSON側から物理（あたり判定）の設定を優先ロード
        const physConfig = this.motionData.physics || {};
        this.hitboxW = physConfig.hitboxWidth ?? hitboxW;
        this.hitboxH = physConfig.hitboxHeight ?? hitboxH;
        this.footY = physConfig.footY ?? footY;
        this.hitboxOffsetX = physConfig.offsetX ?? 0;
        
        // JSONの最上部から全パーツ共通の縮小倍率をロード
        this.globalPartScale = physConfig.globalPartScale ?? 1;

        // 物理ボディの初期設定
        this.body.setCollideWorldBounds(false).setDragX(1300);
        this.body.deltaMax.y = 10;
        
        // あたり判定とコンテナの拡大縮小を同期適用
        this.physicsScale();

        // 1. 管理者が定義した配置通りに全パーツをコンテナ内に生成
        this.motionData.setup.parts.forEach(pConfig => {
            // 🌟 修正：初期配置の x, y 座標に globalPartScale を掛け算して位置の広がりを直す
            const img = scene.add.image(
                pConfig.x * this.globalPartScale, 
                pConfig.y * this.globalPartScale, 
                `${this.characterKey}_atlas`, 
                pConfig.frame
            );
            img.setOrigin(pConfig.originX ?? 0.5, pConfig.originY ?? 0.5);
            img.setDepth(pConfig.depth ?? 0);
            
            const defaultScale = pConfig.scale !== undefined ? pConfig.scale : this.globalPartScale;
            img.setScale(defaultScale);
            
            this.add(img);
            this.partsMap[pConfig.name] = img; // 名前でアクセスできるように保持
        });

        this.currentMotion = null;
        this.currentFrameIndex = 0;
        this.motionTimer = 0;

        this.play('front');

        // 更新ループにアニメーション処理を登録
        scene.events.on('update', this.updateMotion, this);
    }

    play(key, ignoreIfPlaying = false) {
        if (ignoreIfPlaying && this.anims.currentAnim.key === key && this.anims.isPlaying) {
            return this;
        }

        let motionKey = key;

        // 🌟 左右で完全に別挙動にしたい共通キーの自動振り分け
        // シーンから 'attack' や 'damage' とだけ呼ばれた場合、現在の向き（right_ / left_）を自動で頭に付与する
        const separateMotions = ['attack', 'XXXXX'];
        if (separateMotions.includes(motionKey)) {
            motionKey = `${this.currentForm}_${motionKey}`;
        }

        // プレフィックス（向きの指定）があるかチェック
        if (key.startsWith('right_')) {
            this.currentForm = 'right';
            this._flipX = false;
        } else if (key.startsWith('left_')) {
            this.currentForm = 'left';
            this._flipX = true;
        } else if (key === 'front' || key.startsWith('front_')) { // 🌟【新設】正面向きの判定を追加
            this.currentForm = 'front';
            this._flipX = false;
        }

        // 🌟 超強力自動判別フォールバック・ロジック
        // ① まず指定された名前（例: 'left_walk' や 'walk'）でそのまま探す
        let motion = this.motionData.animations[motionKey];
        
        // ② なければ、左向き特有の流用処理（'left_walk' がなくても 'right_walk' があればそれを使う）
        if (!motion && motionKey.startsWith('left_')) {
            const fallbackKey = motionKey.replace('left_', 'right_');
            motion = this.motionData.animations[fallbackKey];
        }
        
        // ③ それでもなければ、プレフィックスを外した共通名（'right_walk' → 'walk'）で探す
        if (!motion) {
            const cleanKey = motionKey.replace('right_', '').replace('left_', '');
            motion = this.motionData.animations[cleanKey];
        }
        
        // ④ 最終防衛線：どうしても見つからなければ直立不動ポーズ
        if (!motion) {
            motion = this.motionData.animations['idle'] || this.motionData.animations['right_idle'];
        }
        
        if (!motion) {
            console.warn(`Motion not found at all: ${key}`);
            return this;
        }

        this.currentMotion = motion;
        this.anims.currentAnim.key = key;
        this.anims.isPlaying = true;
        this.currentFrameIndex = 0;
        this.motionTimer = 0;

        // 見た目のグラフィックと、コマのポーズを適用
        this.applyFormTextures();
        this.applyAnimationFrame();

        return this;
    }

    applyFormTextures() {
        const formConfig = this.motionData.forms[this.currentForm];
        if (!formConfig) return;

        Object.keys(formConfig).forEach(partName => {
            const partImg = this.partsMap[partName];
            if (partImg) {
                const frameName = formConfig[partName];
                if (frameName === 'transparent') {
                    partImg.setVisible(false);
                } else {
                    partImg.setVisible(true);
                    partImg.setFrame(frameName);
                }
            }
        });
    }

    applyAnimationFrame() {
        if (!this.currentMotion || !this.currentMotion.frames[this.currentFrameIndex]) return;

        const frameData = this.currentMotion.frames[this.currentFrameIndex];
        const defaultParts = this.motionData.setup.parts;

        defaultParts.forEach(defaultProp => {
            const partImg = this.partsMap[defaultProp.name];
            if (!partImg) return;

            const animProp = frameData.parts ? frameData.parts[defaultProp.name] : null;
            
            // セットアップ基本位置への「加算」計算
            let targetX = defaultProp.x;
            let targetY = defaultProp.y;
            if (animProp) {
                if (animProp.x !== undefined) targetX += animProp.x;
                if (animProp.y !== undefined) targetY += animProp.y;
            }

            // 縮小スケールの適用
            targetX *= this.globalPartScale;
            targetY *= this.globalPartScale;

            // 角度の計算
            let targetAngle = animProp && animProp.angle !== undefined ? animProp.angle : 0;
            
            let defaultScale = defaultProp.scale !== undefined ? defaultProp.scale : this.globalPartScale;
            let targetScaleX = animProp && animProp.scale !== undefined ? animProp.scale : defaultScale;
            let targetScaleY = targetScaleX;

            // 🌟 左右の折り返し処理（左右非対称グラフィック対応版）
            if (this._flipX) {
                partImg.x = -targetX;
                partImg.angle = -targetAngle;
                partImg.scaleX = -targetScaleX;
            } else {
                partImg.x = targetX;
                partImg.angle = targetAngle;
                partImg.scaleX = targetScaleX;
            }
            partImg.y = targetY;
            partImg.scaleY = targetScaleY;
            
            // コマ個別の重なり（depth）のリアルタイム反映
            let targetDepth = defaultProp.depth ?? 0;
            if (animProp && animProp.depth !== undefined) {
                targetDepth = animProp.depth;
            }
            partImg.setDepth(targetDepth);
            
            // 🌟 コマ個別の画像変更・復元処理
            if (animProp && animProp.frame) {
                // このコマで個別指定（攻撃顔やエフェクトなど）があればそれを表示
                partImg.setVisible(animProp.frame !== 'transparent');
                if (animProp.frame !== 'transparent') partImg.setFrame(animProp.frame);
            } else {
                // 個別指定がないコマでは、現在のフォーム（右/左/前）のベース画像に戻す
                const formConfig = this.motionData.forms[this.currentForm] || {};
                const baseFrame = formConfig[defaultProp.name] || defaultProp.frame;
                
                partImg.setVisible(baseFrame !== 'transparent');
                if (baseFrame !== 'transparent') partImg.setFrame(baseFrame);
            }
        });
        // 🌟【新設】ループが終わった後に、コンテナ内の全パーツを depth の数値で並び替える
        this.sort('depth');
    }

    updateMotion(time, delta) {
        if (!this.anims.isPlaying || !this.currentMotion) return;

        const msPerFrame = 1000 / this.currentMotion.fps;
        this.motionTimer += delta;

        if (this.motionTimer >= msPerFrame) {
            this.motionTimer -= msPerFrame;
            this.currentFrameIndex++;

            if (this.currentFrameIndex >= this.currentMotion.frames.length) {
                if (this.currentMotion.loop) {
                    this.currentFrameIndex = 0;
                } else {
                    this.currentFrameIndex = this.currentMotion.frames.length - 1;
                    this.anims.isPlaying = false;
                }
            }
            this.applyAnimationFrame();
        }
    }

    setFlipX(value) {
        if (this._flipX !== value) {
            this._flipX = value;
            this.physicsScale();
            this.applyAnimationFrame();
        }
        return this;
    }

    setScale(scale) {
        this.playerScale = scale;
        this.physicsScale();
        return this;
    }

    physicsScale() {
        this.scaleX = this.playerScale;
        this.scaleY = this.playerScale;

        this.body.setSize(this.hitboxW, this.hitboxH);
        
        const offsetX = (-this.hitboxW / 2) + this.hitboxOffsetX;
        
        // 🌟 管理画面の「原点(0,0)から footY 下がった位置が接地線」という仕様と完全同期
        // ヒットボックスの下端が footY になるため、offsetY は「footY - ヒットボックス高」になる
        const offsetY = this.footY - this.hitboxH; 
        
        this.body.setOffset(offsetX, offsetY);
    }

    jump(enableDoubleJump, thunderColor) {
        if (this.scene.isDiving) return;
        if (this.body.touching.down) {
            this.setVelocityY(-660); Synth.jump(); this.jumpCount = 1;
        } else if (enableDoubleJump && this.jumpCount < 2) {
            this.body.velocity.y = 0; this.setVelocityY(-620); Synth.jump(); this.jumpCount = 2;
            Environment.createExplosion(this.scene, this.x, this.y + 20, thunderColor, 8, 150);
        }
    }

    startDive() {
        this.scene.isDiving = true; this.jumpCount = 99; this.setVelocity(0, 1200);
        Synth.play([120, 280, 60, 180], 'sawtooth', 0.35, 0.18); this.scene.cameras.main.shake(100, 0.012);
    }

    handleMovement(isInterruptRunning, currentAnimKey) {
        if (this.scene.isDiving) return;
        if (isInterruptRunning && currentAnimKey.endsWith('_attack')) {
            this.setVelocityX(0);
        } else if (InputController.moveLeft) {
            this.setVelocityX(-260);
        } else if (InputController.moveRight) {
            this.setVelocityX(260);
        } else {
            this.setVelocityX(0);
        }
    }

    destroy(fromScene) {
        this.scene.events.off('update', this.updateMotion, this);
        super.destroy(fromScene);
    }

    setVelocity(x, y) { this.body.setVelocity(x, y); return this; }
    setVelocityX(x) { this.body.setVelocityX(x); return this; }
    setVelocityY(y) { this.body.setVelocityY(y); return this; }
}