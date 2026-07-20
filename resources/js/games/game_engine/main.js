import DataLoaderScene from './DataLoaderScene.js';
import CharacterSelectScene from './CharacterSelectScene.js';
import StageSelectScene from './StageSelectScene.js';
import GameScene from './GameScene.js';

const config = window.GAME_CONFIG;

function initPhaser() {
    console.log('--- PHASER STARTING ---');
    
    if (window.gameInstance) {
        window.gameInstance.destroy(true);
    }

    // スマホの画面サイズ（ブラウザの表示領域）に合わせて解像度を決定
    const width = window.innerWidth;
    const height = window.innerHeight;

    const phaserConfig = {
        type: Phaser.AUTO,
        width: width,
        height: height,
        parent: 'game-container',
        backgroundColor: '#222222',
        pixelArt: true,
        physics: {
            default: 'arcade',
            arcade: {
                gravity: { y: 0 }, 
                debug: true
            }
        },
        scale: {
            // 親要素に合わせてリサイズ。アスペクト比を維持せず全画面を使う
            mode: Phaser.Scale.RESIZE,
            autoCenter: Phaser.Scale.CENTER_BOTH
        },
        scene: [DataLoaderScene, CharacterSelectScene, StageSelectScene, GameScene]
    };

    window.gameInstance = new Phaser.Game(phaserConfig);
}

if (document.readyState === 'complete') {
    initPhaser();
} else {
    window.addEventListener('load', initPhaser);
}

// 画面回転時に再初期化（必要に応じて）
window.addEventListener('resize', () => {
    // 向きが変わった可能性があるため再描画の検討
});

if (import.meta.hot) {
    import.meta.hot.dispose(() => {
        if (window.gameInstance) {
            window.gameInstance.destroy(true);
        }
    });
}
