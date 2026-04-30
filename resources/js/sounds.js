

//==================================================================
//音源ファイルを再生する場合は事前にroadする
//==================================================================
// 音声ファイルのキャッシュ用オブジェクト
const soundCache = {};

// 事前に音声ファイルをロードする関数
//preloadSounds(["select01", "XXX", "XXXXX"]);
window.preloadSounds = function preloadSounds(soundList) {
    soundList.forEach(soundType => {
        const soundFile = publicDir + '/' + soundType + '.mp3';
        fetch(soundFile)
            .then(response => response.arrayBuffer())
            .then(data => SoundManager.audioCtx.decodeAudioData(data))
            .then(buffer => {
                soundCache[soundType] = buffer;
            })
            .catch(error => console.error("音声のロード失敗:", soundType, error));
    });
}

//==================================================================

//定義サウンド：SoundManager.play('roulette');
//カスタムサウンド：SoundManager.play('',100,50,'square',1);
window.SoundManager = {
    // app.js の共通関数を呼ぶようにする
    get audioCtx() {
        return window.getSharedAudioContext();
    },
    
    /**
     * 単音を鳴らす
     * @param {string} soundType - 音のキー名
     * @param {number} playtime - 再生時間 (ミリ秒)
     * @param {string} waveType - 波形タイプ ('sine', 'square', 'sawtooth', 'triangle')
     * @param {boolean} short - "ツッ" のような短い音を出す場合は true
     */
    play: function (soundType, selectTone = 0, selectPtime = 0, selectWave = 0, selectShort = 0) {
        const ctx = this.audioCtx;
        // 再生直前にコンテキストを強制的にアクティブにする
        if (ctx.state === 'suspended') { ctx.resume(); }
        let soundFileFlag = false; //音声ファイルを読み込んで再生する場合
        let tone = [[100]];
        let playtime = [0];
        let waveType = 'square';
        let short = false;
        
        switch (soundType){
            //tone：    サウンド音(配列：和音)
            //playtime：再生ミリ秒      //wavetype：サウンドの種別      //short：   音を途切れさせる
            //[[992]]：単一音     [[880], [1318.51, 1760]]：和音の単一音    [[100, 200, 300], [300], [300]]：音を複数並べる
            //tone：https://www.szynalski.com/tone-generator/#support
            //wavetype：https://musiclab.chromeexperiments.com/Oscillators/

            //==========================音源定義=============================
            case "rouletteSpin":    //ルーレット回転音
                tone = [[987]];//B5　シ
                playtime = [50];   waveType = 'square'; short = true;
                break;
            case "rouletteHit":
                tone = [[220], [0], [330], [440]];
                playtime = [100, 200, 150, 300]; waveType = 'sawtooth'; short = false;
                break;
            case "test1"://テッテレー　こもった音？
                tone = [[220], [0], [330], [440]];
                playtime = [150, 150, 150, 300]; waveType = 'sawtooth'; short = false;
                break;
                
            //=============================ファイル再生==========================
            //https://taira-komori.jpn.org/freesound.html
            
            //もとの音源ファイルより短く再生したい場合のみ、playtimeを指定する
            default:
                soundFileFlag = true;
                break;

        } 
        //カスタムサウンド
        if(selectTone) tone = selectTone;
        if(selectPtime) playtime = selectPtime;
        if(selectWave) waveType = selectWave;
        if(selectShort == 1) short = true;  if(selectShort == 2) short = false;

        if (soundFileFlag) {
            if (soundCache[soundType]) {
                const source = ctx.createBufferSource();
                source.buffer = soundCache[soundType];
                source.connect(ctx.destination);
                source.start(0);
        
                // playtime が指定されている場合は指定時間後に停止
                if (playtime[0] && playtime.length > 0) {
                    const duration = playtime[0];  // 配列の最初の値を取得
                    setTimeout(() => {
                        source.stop();
                    }, duration);
                }
            } else {
                console.error("音声がキャッシュされていません:", soundType);
            }

        }else if (Array.isArray(tone)) {
            // tone が 2次元配列の場合（順次再生）
            let startTime = ctx.currentTime;

            tone.forEach((chord, index) => {
                const gainNode = ctx.createGain();
                gainNode.gain.setValueAtTime(0.2, startTime);
                gainNode.connect(ctx.destination);

                if (!Array.isArray(chord)) {
                    chord = [chord];  // 単一音なら和音の形に変換
                }

                chord.forEach(frequency => {
                    const oscillator = ctx.createOscillator();
                    oscillator.type = waveType;
                    oscillator.frequency.setValueAtTime(frequency, startTime);
                    oscillator.connect(gainNode);
                    oscillator.start(startTime);
                    oscillator.stop(startTime + playtime[index] / 1000);
                });

                if (short) {
                    gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + playtime[index] / 1000);
                }

                startTime += playtime[index] / 1000; // 次の音を再生する時間を加算
            });
        }
    },resumeAudioContext: function () {
        const ctx = this.audioCtx;
        if (ctx.state === 'suspended') {
            ctx.resume().then(() => {
                console.log("✅ AudioContext resumed");
                const buffer = ctx.createBuffer(1, 1, 22050);
                const source = ctx.createBufferSource();
                source.buffer = buffer;
                source.connect(ctx.destination);
                source.start();
                console.log("🔇 無音再生で初回ラグを回避");
            }).catch(err => console.error("❌ AudioContext resume error:", err));
        }
    }
};


// iOS / スマホで AudioContext を有効化
document.addEventListener('touchstart', () => {
    SoundManager.resumeAudioContext();
}, { once: true });

/*
// PC 用 (念のため)
document.addEventListener('click', () => {
    SoundManager.resumeAudioContext();
}, { once: true });
*/