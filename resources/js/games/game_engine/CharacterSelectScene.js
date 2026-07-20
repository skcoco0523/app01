export default class CharacterSelectScene extends Phaser.Scene {
    constructor() {
        super({ key: 'CharacterSelectScene' });
    }

    create() {
        const width = this.cameras.main.width;
        const height = this.cameras.main.height;
        const config = window.GAME_CONFIG;
        const characters = this.cache.json.get('characters');
        const user = config.user || { isLoggedIn: false, isAdmin: false };
        const v = config.versions || {};

        // マージン設定（画面端から少し離す）
        const edgeMargin = Math.min(width, height) * 0.05;

        this.add.rectangle(0, 0, width, height, 0x111111).setOrigin(0);
        
        const titleY = config.orientation === 'portrait' ? 80 : 50;
        this.add.text(width / 2, titleY, 'SELECT CHARACTER', {
            font: '32px Arial',
            fill: '#ffffff'
        }).setOrigin(0.5);

        // 終了ボタン (セーフエリアを考慮して配置)
        const closeBtn = this.add.rectangle(width - edgeMargin - 50, edgeMargin + 20, 100, 40, 0xff3333).setInteractive({ useHandCursor: true });
        this.add.text(width - edgeMargin - 50, edgeMargin + 20, 'CLOSE', { font: '18px Arial', fill: '#ffffff' }).setOrigin(0.5);
        closeBtn.on('pointerdown', () => {
            window.location.href = '/'; 
        });

        const playerCharacters = characters.filter(c => c.type === 'player');

        const startY = height * 0.25;
        const spacing = config.orientation === 'portrait' ? 90 : 100;

        playerCharacters.forEach((char, index) => {
            const x = width / 2;
            const y = startY + (index * spacing);

            let isLocked = false;
            let lockReason = '';

            if (char.admin_only_flag && !user.isAdmin) {
                isLocked = true;
                lockReason = '(Admin Only)';
            } else if (char.login_user_flag && !user.isLoggedIn) {
                isLocked = true;
                lockReason = '(Login Required)';
            }

            const btnWidth = Math.min(width * 0.8, 300);
            const btnHeight = 80;
            const btnColor = isLocked ? 0x444444 : 0x3366ff;
            const bg = this.add.rectangle(x, y, btnWidth, btnHeight, btnColor).setOrigin(0.5);
            
            const nameText = this.add.text(x, y, char.name + (isLocked ? ` ${lockReason}` : ''), {
                font: '20px Arial',
                fill: isLocked ? '#888888' : '#ffffff'
            }).setOrigin(0.5);

            if (!isLocked) {
                bg.setInteractive({ useHandCursor: true });
                bg.on('pointerover', () => bg.setFillStyle(0x4477ff));
                bg.on('pointerout', () => bg.setFillStyle(0x3366ff));
                bg.on('pointerdown', () => {
                    const charKey = char.character_key;
                    this.load.json(`char_data_${charKey}`, `${config.apiUrl}/character/get/${charKey}?v=${v.characters || ''}`);
                    this.load.once('complete', () => {
                        const detail = this.cache.json.get(`char_data_${charKey}`);
                        this.scene.start('StageSelectScene', { characterData: detail });
                    });
                    this.load.start();
                });
            }
        });

        if (playerCharacters.length === 0) {
            this.add.text(width / 2, height / 2, 'No characters available.', {
                font: '20px Arial',
                fill: '#ff0000'
            }).setOrigin(0.5);
        }
    }
}
