// resources/js\games\common\sound_manager.js

// --- Web Audio APIによるプログラムシンセサイザーシステム ---
export const Synth = {
    ctx: null,
    init() { if (!this.ctx) this.ctx = new (window.AudioContext || window.webkitAudioContext)(); },
    play(freqs, type, duration, volume = 0.1) {
        this.init(); const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator(); const gain = this.ctx.createGain();
        osc.type = type; osc.connect(gain); gain.connect(this.ctx.destination);
        gain.gain.setValueAtTime(volume, now); gain.gain.exponentialRampToValueAtTime(0.001, now + duration);
        if (Array.isArray(freqs)) {
            freqs.forEach((f, i) => osc.frequency.setValueAtTime(f, now + (duration / freqs.length) * i));
        } else { osc.frequency.setValueAtTime(freqs, now); }
        osc.start(now); osc.stop(now + duration);
    },
    jump() { this.play([200, 400, 600], 'square', 0.15, 0.08); },
    attack() { this.play([500, 200], 'sawtooth', 0.1, 0.08); },
    hit() { this.play([150, 300, 100], 'triangle', 0.2, 0.15); },
    shift() { this.play([300, 900], 'sine', 0.2, 0.06); },
    coin() { this.play([800, 1200], 'sine', 0.15, 0.08); },
    clear() { this.play([523, 659, 783, 1046], 'triangle', 0.4, 0.1); },
    gameover() { this.play([300, 200, 100], 'sawtooth', 0.5, 0.15); }
};
