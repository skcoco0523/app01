export default class DataLoaderScene extends Phaser.Scene {
    constructor() {
        super({ key: 'DataLoaderScene' });
        this.started = false;
    }

    preload() {
        console.log('DataLoaderScene preload() started');
        const config = window.GAME_CONFIG;

        // --- ローディングUIの構築 ---
        const width = this.cameras.main.width;
        const height = this.cameras.main.height;

        // 背景（少し明るいグレー）
        this.add.rectangle(0, 0, width, height, 0x333333).setOrigin(0);

        // 「LOADING...」テキスト
        this.loadingText = this.add.text(width / 2, height / 2 - 50, 'LOADING...', {
            font: '32px monospace',
            fill: '#ffffff'
        }).setOrigin(0.5);

        // パーセント表示
        this.percentText = this.add.text(width / 2, height / 2, '0%', {
            font: '24px monospace',
            fill: '#ffffff'
        }).setOrigin(0.5);

        // 読み込み中のファイル名
        this.assetText = this.add.text(width / 2, height / 2 + 50, '', {
            font: '18px monospace',
            fill: '#aaaaaa'
        }).setOrigin(0.5);

        // プログレスバー
        const progressBar = this.add.graphics();
        const progressBox = this.add.graphics();
        progressBox.fillStyle(0x222222, 0.8);
        progressBox.fillRect(width / 2 - 160, height / 2 + 80, 320, 30);

        // --- ロードイベントのリスナー ---
        this.load.on('progress', (value) => {
            const currentWidth = this.cameras.main.width;
            const currentHeight = this.cameras.main.height;
            const percent = Math.floor(value * 100);
            this.percentText.setText(percent + '%');
            progressBar.clear();
            progressBar.fillStyle(0x00ff00, 1);
            progressBar.fillRect(currentWidth / 2 - 150, currentHeight / 2 + 90, 300 * value, 10);
        });

        this.load.on('fileprogress', (file) => {
            this.assetText.setText('Loading: ' + file.key);
        });

        this.load.on('loaderror', (file) => {
            console.error('Failed to load file:', file.key, file.src);
        });

        // APIから基礎データをロード（バージョン付き）
        const v = config.versions || {};
        this.load.json('atlas_sheets', `${config.apiUrl}/atlas/get?v=${v.atlas || ''}`);
        this.load.json('characters', `${config.apiUrl}/characters/get?v=${v.characters || ''}`);
        this.load.json('stages', `${config.apiUrl}/stages/get?v=${v.stages || ''}`);
        this.load.json('weapons', `${config.apiUrl}/weapons/get?v=${v.stages || ''}`);
        this.load.json('items', `${config.apiUrl}/items/get?v=${v.stages || ''}`);

        // 追加の共通アセットがあればここでロード
        // this.load.audio('shift', `${config.assetBaseUrl}/sounds/shift.mp3`);
    }

    create() {
        console.log('DataLoaderScene create() - Checking atlas_sheets for assets');
        const sheets = this.cache.json.get('atlas_sheets');
        const config = window.GAME_CONFIG;

        const nextScene = () => {
            if (this.started) return;
            this.started = true;
            this.scene.start('CharacterSelectScene');
        };

        if (sheets && Array.isArray(sheets) && sheets.length > 0) {
            let loadCount = 0;
            sheets.forEach(sheet => {
                const key = sheet.filename;
                let subPath = sheet.category ? `${sheet.category}/` : '';
                const imageUrl = `${config.globalAssetBaseUrl}/${subPath}${sheet.filename}`;
                
                if (sheet.pixel_data) {
                    this.load.atlas(key, imageUrl, sheet.pixel_data);
                    loadCount++;
                } else {
                    this.load.image(key, imageUrl);
                    loadCount++;
                }
            });
            
            if (loadCount > 0) {
                this.load.once('complete', () => {
                    nextScene();
                });
                // 追加ロード開始
                this.load.start();
            } else {
                nextScene();
            }
        } else {
            nextScene();
        }
    }
}
