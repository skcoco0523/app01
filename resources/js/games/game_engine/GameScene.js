import { ModeHandlerFactory } from './view_modes/ModeHandlerFactory.js';

export default class GameScene extends Phaser.Scene {
    constructor() {
        super({ key: 'GameScene' });
        this.playerParts = {};
        this.currentAnim = null;
        this.animFrame = 0;
        this.animTimer = 0;
    }

    async init() {
        this.config = window.GAME_CONFIG;
        // Promiseを保存しておく
        this.modeHandlerPromise = ModeHandlerFactory.create(this.config.viewMode, this.config.gameKey);
    }

    async create(data) {
        console.log('--- GameScene create() START ---');
        
        // ハンドラーの読み込み完了を待機
        this.modeHandler = await this.modeHandlerPromise;
        const width = this.cameras.main.width;
        const height = this.cameras.main.height;

        const playerDef = data ? data.characterData : null;
        const stageData = data ? data.stageData : null;
        this.stageData = stageData;
        this.playerDef = playerDef;

        this.cameras.main.setBackgroundColor('#222222');

        // マップデータの取得（新仕様：stageData.map_data、旧仕様：stageData.custom_settings）
        const mapData = stageData.map_data || stageData.custom_settings || {};
        
        // ステージ基本設定の取得
        const stageBase = (mapData && mapData.base) ? mapData.base : {};
        const baseStageHeight = stageBase.height || 600;
        const baseStageWidth = stageBase.width || 2000;
        const baseGroundY = stageBase.groundY !== undefined ? stageBase.groundY : 580;

        // 画面の高さに合わせたステージスケールの計算
        const stageScale = height / baseStageHeight;
        this.stageScale = stageScale;
        
        // スケーリング済みワールドサイズ
        const worldWidth = baseStageWidth * stageScale;
        const worldHeight = baseStageHeight * stageScale;
        const groundY = baseGroundY * stageScale;
        this.worldWidth = worldWidth;
        this.groundY = groundY;

        // レイヤー描画
        if (mapData && mapData.layers) {
            const layers = mapData.layers.sort((a, b) => (a.depth || 0) - (b.depth || 0));
            layers.forEach(layer => {
                const atlasKey = layer.atlas; 
                const img = this.add.image((layer.x || 0) * stageScale, (layer.y || 0) * stageScale, atlasKey, layer.key);
                img.setOrigin(0, 0);
                img.setScale(stageScale);
                const depth = layer.depth || 0;
                img.setDepth(Math.min(depth, 99));
                
                let sfX = layer.scrollFactorX;
                if (sfX === undefined) {
                    if (depth < 0) {
                        sfX = Math.max(0.1, 1 + (depth * 0.1));
                    } else {
                        sfX = 1.0;
                    }
                }
                img.setScrollFactor(sfX, layer.scrollFactorY !== undefined ? layer.scrollFactorY : 1);
            });
        }

        this.modeHandler.initPhysics(this, stageData);

        // 地面の設定 (物理演算のためにプレイヤーより先に作成)
        const groundHeight = 100;
        const ground = this.add.rectangle(worldWidth / 2, groundY + groundHeight / 2, worldWidth, groundHeight, 0x888888);
        ground.setAlpha(0); 
        this.physics.add.existing(ground, true);
        this.ground = ground;

        // プレイヤー初期化
        if (playerDef && playerDef.motion_data) {
            const mData = playerDef.motion_data;
            const pConfig = mData.physics ? (mData.physics.default || {}) : {};
            
            this.hitboxWidth = pConfig.hitboxWidth || 32;
            this.hitboxHeight = pConfig.hitboxHeight || 64;
            this.hitboxOffsetX = pConfig.offsetX || 0;
            this.hitboxFootY = pConfig.footY || 0; // ★ここでfootY（18）が取得されている
            const globalScale = (pConfig.globalPartScale || 1.0);
            this.globalPartScale = globalScale;

            const startX = 100 * stageScale; // 初期位置
            
            // 【修正】コンテナの原点を地面(groundY)から、(footY * スケール) 分だけ上に浮かせる
            const startY = groundY - this.hitboxFootY - 1;
            
            this.player = this.add.container(startX, startY);
            this.player.setDepth(100); 
            this.player.setScale(globalScale);

            // 管理画面の基準点（原点）を考慮してパーツを配置
            const editorOriginX = this.config.editor ? this.config.editor.originX : 300;
            const editorOriginY = this.config.editor ? this.config.editor.originY : 220;

            if (mData.setup && mData.setup.parts) {
                const sortedParts = [...mData.setup.parts].sort((a, b) => (a.depth || 0) - (b.depth || 0));
                sortedParts.forEach(part => {
                if (this.textures.exists(part.image)) {

                    // 管理画面の絶対座標から基準点(Origin)を引いて相対座標にする
                    const spriteX = (part.x || 0);
                    const spriteY = (part.y || 0);
                    
                    const sprite = this.add.sprite(spriteX, spriteY, part.image, part.frame);
                    sprite.setOrigin(part.originX !== undefined ? part.originX : 0.5, part.originY !== undefined ? part.originY : 0.5);

                    if (part.scale) sprite.setScale(part.scale);
                    if (part.angle) sprite.setAngle(part.angle);

                    this.player.add(sprite);
                    this.playerParts[part.name] = sprite;

                    sprite.setData('baseX', part.x || 0);
                    sprite.setData('baseY', spriteY);
                    sprite.setData('baseAngle', part.angle || 0);
                    sprite.setData('baseFrame', part.frame);
                }
            });
            }

            // 全ての準備が整ってから物理ボディを有効化
            this.physics.add.existing(this.player);
            
            // 物理ボディ設定
            this.updateHitbox(1);
            this.player.body.setCollideWorldBounds(true); 

            // 地面との当たり判定
            this.physics.add.collider(this.player, this.ground);

            // 初期位置を確定
            this.player.body.reset(this.player.x, this.player.y);
        }

        this.ground.setDepth(50); 

        this.cursors = this.input.keyboard.createCursorKeys();
        this.input.addPointer(2);

        this.modeHandler.setupCamera(this, this.player, stageData);

        // メニューボタン
        const edgeMargin = Math.min(width, height) * 0.05;
        const menuBtn = this.add.rectangle(width - edgeMargin - 50, edgeMargin + 20, 100, 40, 0x000000, 0.5)
            .setInteractive({ useHandCursor: true })
            .setScrollFactor(0)
            .setDepth(1000);
        this.add.text(width - edgeMargin - 50, edgeMargin + 20, 'MENU', { font: '18px Arial', fill: '#ffffff' })
            .setOrigin(0.5)
            .setScrollFactor(0)
            .setDepth(1001);

        menuBtn.on('pointerdown', () => {
            if (confirm('Exit game and return to stage select?')) {
                this.scene.start('StageSelectScene', { characterData: this.playerDef });
            }
        });

        // 操作インジケーター
        this.inputIndicator = this.add.container(0, 0).setScrollFactor(0).setDepth(2000).setVisible(false);
        const baseCircle = this.add.circle(0, 0, 40, 0xffffff, 0.2);
        const leftArrow = this.add.triangle(-50, 0, 0, -15, 0, 15, -20, 0, 0xffffff, 0.5);
        const rightArrow = this.add.triangle(50, 0, 0, -15, 0, 15, 20, 0, 0xffffff, 0.5);
        this.inputIndicator.add([baseCircle, leftArrow, rightArrow]);
        this.inputIndicatorBase = baseCircle;
        this.inputIndicatorLeft = leftArrow;
        this.inputIndicatorRight = rightArrow;

        console.log('--- GameScene create() END ---');
    }
    updateHitbox(direction) {
        if (!this.player || !this.player.body) return;
        
        const scale = Math.abs(this.player.scaleX) || 1;
        const invScale = 1 / scale;

        const bw = this.hitboxWidth * invScale;
        const bh = this.hitboxHeight * invScale;
        
        const isFlipped = direction === -1;
        // プレイヤーコンテナ自体のスケールは常に正の値を維持（パーツ側で反転）
        this.player.scaleX = scale; 
        this.lastDirection = direction; // 最後に移動した向きを記憶

        // 座標の更新は playAnimation/resetPose に任せ、ここではスケール（向き）のみ適用
        for (const name in this.playerParts) {
            const sprite = this.playerParts[name];
            const baseScaleX = Math.abs(sprite.scaleX);
            sprite.scaleX = isFlipped ? -baseScaleX : baseScaleX;
        }

        // 横方向オフセット: (OffsetX - width / 2)
        const ox = (this.hitboxOffsetX - this.hitboxWidth / 2) * invScale;
        
        // 【修正】管理画面の基準（原点からfootY下がりが底、そこからhitboxHeight上が頭上）に合わせる
        const oy = (this.hitboxFootY - this.hitboxHeight) * invScale;
        
        this.player.body.setSize(bw, bh);
        this.player.body.setOffset(ox, oy);
        this.player.body.onWorldBounds = true;

        // 向きが変わった瞬間に即座に描画を更新
        this.applyCurrentFrame();
    }

    applyCurrentFrame() {
        if (!this.player || !this.playerParts) return;

        const anim = this.currentAnim ? this.playerDef.motion_data.animations[this.currentAnim] : null;
        const frameData = anim ? anim.frames[this.animFrame] : null;

        // 足元基準での反転を考慮（記憶した向きまたは速度から判定）
        let isFlipped = this.lastDirection === -1;
        if (this.player.body && this.player.body.velocity.x !== 0) {
            isFlipped = this.player.body.velocity.x < 0;
        }

        // 現在の向き（forms）に応じた画像切り替え
        const currentForm = isFlipped ? 'left' : 'right';
        const forms = this.playerDef.motion_data.forms || {};

        for (const partName in this.playerParts) {
            const part = this.playerParts[partName];
            const diff = (frameData && frameData.parts) ? frameData.parts[partName] || {} : {};
            
            // 1. 画像(フレーム)の更新
            let frameName = forms[currentForm]?.[partName];
            if (diff.frame) {
                frameName = diff.frame;
            }
            if (frameName && frameName !== 'transparent' && part.frame.name !== frameName) {
                part.setFrame(frameName);
                part.setVisible(true);
            } else if (frameName === 'transparent') {
                part.setVisible(false);
            }

            // 2. 座標と角度の更新
            const baseX = part.getData('baseX') || 0;
            const baseY = part.getData('baseY') || 0;
            const baseAngle = part.getData('baseAngle') || 0;

            const animX = diff.x || 0;
            const animY = diff.y || 0;
            const animAngle = diff.angle || 0;

            part.x = isFlipped ? -(baseX + animX) : (baseX + animX);
            part.y = baseY + animY;
            part.angle = isFlipped ? -(baseAngle + animAngle) : (baseAngle + animAngle);
            
            // 3. スケールの反転
            const baseScaleX = Math.abs(part.scaleX);
            part.scaleX = isFlipped ? -baseScaleX : baseScaleX;

            // 4. 重なり(Depth)の更新
            if (diff.depth !== undefined) {
                this.player.moveTo(part, diff.depth);
            }
        }
    }

    playAnimation(animName, delta) {
        const anim = this.playerDef.motion_data.animations[animName];
        if (!anim) return;

        let hasFrameChanged = false;

        // アニメーションが切り替わった場合の処理
        if (this.currentAnim !== animName) {
            this.currentAnim = animName;
            this.animFrame = 0;
            this.animTimer = 0;
            hasFrameChanged = true; // 切り替え直後に1コマ目を描画させる
        }

        this.animTimer += delta;
        const msPerFrame = 1000 / (anim.fps || 10);

        while (this.animTimer >= msPerFrame) {
            this.animTimer -= msPerFrame;
            this.animFrame = (this.animFrame + 1) % anim.frames.length;
            hasFrameChanged = true;
        }

        if (hasFrameChanged) {
            this.applyCurrentFrame();
        }
    }

    resetPose() {
        this.animFrame = 0;
        this.animTimer = 0;
        this.currentAnim = null; // ポーズリセット時はアニメーションなし状態へ
        this.applyCurrentFrame();
    }

    update(time, delta) {
        if (!this.modeHandler) return;
        
        this.modeHandler.updatePlayer(this, this.player, this.cursors, delta);

        const pointer = [this.input.pointer1, this.input.pointer2, this.input.activePointer].find(p => p.isDown && p.id === this.leftTouchId);
        if (pointer && this.config.orientation === 'landscape' && this.leftTouchStartPos) {
            this.inputIndicator.setVisible(true);
            this.inputIndicator.setPosition(this.leftTouchStartPos.x, this.leftTouchStartPos.y);
            
            const diffX = pointer.x - this.leftTouchStartPos.x;
            const deadzone = 10;
            this.inputIndicatorLeft.setAlpha(diffX < -deadzone ? 1.0 : 0.3);
            this.inputIndicatorRight.setAlpha(diffX > deadzone ? 1.0 : 0.3);
        } else {
            this.inputIndicator.setVisible(false);
        }

        // キャラクターの画面外はみ出し防止
        if (this.player && this.player.body) {
            const worldWidth = this.worldWidth || 2000;
            const body = this.player.body;
            if (body.x < 0) {
                body.x = 0;
                body.setVelocityX(Math.max(0, body.velocity.x));
            }
            if (body.right > worldWidth) {
                body.x = worldWidth - body.width;
                body.setVelocityX(Math.min(0, body.velocity.x));
            }
        }
    }
}
