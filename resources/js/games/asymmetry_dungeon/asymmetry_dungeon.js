import { Synth } from '../common/asset_manifest.js';

document.addEventListener('DOMContentLoaded', function () {
    const config = {
        type: Phaser.AUTO,
        parent: 'game-container',
        width: 450,
        height: 800,
        backgroundColor: '#111111',
        scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH },
        physics: { default: 'arcade', arcade: { gravity: { y: 1400 }, debug: false } },
        scene: { preload: preload, create: create, update: update }
    };

    const game = new Phaser.Game(config);

    function preload() {}

    function create() {
        const scene = this;
        scene.add.text(225, 400, 'ASYMMETRY DUNGEON', { fontFamily: 'Orbitron', fontSize: '28px', fill: '#ff3333', fontWeight: 'bold' }).setOrigin(0.5);
        scene.add.text(225, 450, 'COMING SOON', { fontFamily: 'Orbitron', fontSize: '18px', fill: '#fff' }).setOrigin(0.5);
    }

    function update() {}
});
