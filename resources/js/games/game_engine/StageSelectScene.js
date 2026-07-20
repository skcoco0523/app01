export default class StageSelectScene extends Phaser.Scene {
    constructor() {
        super({ key: 'StageSelectScene' });
    }

    create(data) {
        const width = this.cameras.main.width;
        const height = this.cameras.main.height;
        const config = window.GAME_CONFIG;
        const stages = this.cache.json.get('stages');
        const user = config.user || { isLoggedIn: false, isAdmin: false };
        const v = config.versions || {};
        const characterData = data.characterData;

        // マージン設定
        const edgeMargin = Math.min(width, height) * 0.05;

        this.add.rectangle(0, 0, width, height, 0x111111).setOrigin(0);
        
        const titleY = config.orientation === 'portrait' ? 80 : 50;
        this.add.text(width / 2, titleY, 'SELECT STAGE', {
            font: '32px Arial',
            fill: '#ffffff'
        }).setOrigin(0.5);

        // 戻るボタン (セーフエリア考慮)
        const backBtn = this.add.rectangle(width - edgeMargin - 50, edgeMargin + 20, 100, 40, 0x666666).setInteractive({ useHandCursor: true });
        this.add.text(width - edgeMargin - 50, edgeMargin + 20, 'BACK', { font: '18px Arial', fill: '#ffffff' }).setOrigin(0.5);
        backBtn.on('pointerdown', () => {
            this.scene.start('CharacterSelectScene');
        });

        const startY = height * 0.25;
        const spacing = config.orientation === 'portrait' ? 90 : 100;

        stages.forEach((stage, index) => {
            const x = width / 2;
            const y = startY + (index * spacing);

            let isLocked = false;
            let lockReason = '';

            if (stage.admin_only_flag && !user.isAdmin) {
                isLocked = true;
                lockReason = '(Admin Only)';
            } else if (stage.login_user_flag && !user.isLoggedIn) {
                isLocked = true;
                lockReason = '(Login Required)';
            }

            const btnWidth = Math.min(width * 0.8, 300);
            const btnHeight = 80;
            const btnColor = isLocked ? 0x444444 : 0x00aa44;
            const bg = this.add.rectangle(x, y, btnWidth, btnHeight, btnColor).setOrigin(0.5);
            
            const nameText = this.add.text(x, y, stage.name + (isLocked ? ` ${lockReason}` : ''), {
                font: '20px Arial',
                fill: isLocked ? '#888888' : '#ffffff'
            }).setOrigin(0.5);

            if (!isLocked) {
                bg.setInteractive({ useHandCursor: true });
                bg.on('pointerover', () => bg.setFillStyle(0x00cc55));
                bg.on('pointerout', () => bg.setFillStyle(0x00aa44));
                bg.on('pointerdown', () => {
                    const stageUrl = `${config.assetBaseUrl}/stages/${stage.number}.json?v=${v.stages || ''}`;
                    this.load.json(`stage_data_${stage.number}`, stageUrl);
                    this.load.once('complete', () => {
                        const stageDetail = this.cache.json.get(`stage_data_${stage.number}`);
                        this.scene.start('GameScene', { 
                            characterData: characterData,
                            stageData: stageDetail
                        });
                    });
                    this.load.start();
                });
            }
        });

        if (stages.length === 0) {
            this.add.text(width / 2, height / 2, 'No stages available.', {
                font: '20px Arial',
                fill: '#ff0000'
            }).setOrigin(0.5);
        }
    }
}
