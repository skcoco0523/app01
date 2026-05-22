// games/twin_facer/twin_facer.js
import GameScene from './scenes/game_scene.js';

document.addEventListener('DOMContentLoaded', function () {
    const config = {
        type: Phaser.AUTO,
        parent: 'game-container',
        width: 450,
        height: 800,
        backgroundColor: '#030407',
        scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH },
        physics: { 
            default: 'arcade', 
            arcade: { 
                gravity: { y: 1400 }, 
                debug: false,
                steps: 6 
            } 
        },
        input: { activePointers: 5 }, 
        // 分割したGameSceneクラスをセットする
        scene: [GameScene]
    };

    new Phaser.Game(config);
});