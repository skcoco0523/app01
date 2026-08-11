// Classifier module
let classifierInitialized = false;

Module.onRuntimeInitialized = function() {
    classifierInitialized = true;
};

class EdgeImpulseClassifier {
    _initialized = false;

    //Wasmファイル（edge-impulse-standalone.wasm）を読み込み、初期化する
    init() {
        if (classifierInitialized === true) return Promise.resolve();

        return new Promise((resolve, reject) => {
            Module.onRuntimeInitialized = () => {
                classifierInitialized = true;
                let ret = Module.init();
                if (typeof ret === 'number' && ret != 0) {
                    return reject('init() failed with code ' + ret);
                }
                resolve();
            };
        });
    }

    //設定情報の取得
    getProjectInfo() {
        if (!classifierInitialized) throw new Error('Module is not initialized');
        return this._convertToOrdinaryJsObject(Module.get_project(), Module.emcc_classification_project_t.prototype);
    }

    //設定情報の取得
    getProperties() {
        if (!classifierInitialized) throw new Error('Module is not initialized');
        return this._convertToOrdinaryJsObject(Module.get_properties(), Module.emcc_classification_properties_t.prototype);
    }

    //音声分析の実行
    classify(rawData, debug = false) {
        if (!classifierInitialized) throw new Error('Module is not initialized');

        const obj = this._arrayToHeap(rawData);// --- 特定用ログ差し込み ---
        console.log("--- classify 内部検証 ---");
        console.log("obj:", obj);
        console.log("1. malloc番地(ptr):", obj.ptr);
        console.log("2. byteOffset値:", obj.buffer.byteOffset);
        console.log("3. Wasmへ渡す長さ:", rawData.length);
        // -----------------------
        let ret = Module.run_classifier(obj.buffer.byteOffset, rawData.length, debug);
        console.log("ret:", ret);
        Module._free(obj.ptr);

        if (ret.result !== 0) {
            throw new Error('Classification failed (err code: ' + ret.result + ')');
        }

        return this._fillResultStruct(ret);
    }

    //リアルタイム分析の実行
    classifyContinuous(rawData, enablePerfCal = true) {
        if (!classifierInitialized) throw new Error('Module is not initialized');

        const obj = this._arrayToHeap(rawData);
        let ret = Module.run_classifier_continuous(obj.buffer.byteOffset, rawData.length, false, enablePerfCal);
        Module._free(obj.ptr);

        if (ret.result !== 0) {
            throw new Error('Classification failed (err code: ' + ret.result + ')');
        }

        return this._fillResultStruct(ret);
    }

    /**
     * Override the threshold on a learn block (you can find thresholds via getProperties().thresholds)
     * @param {*} obj, e.g. { id: 16, min_score: 0.2 } to set min. object detection threshold to 0.2 for block ID 16
     */
    setThreshold(obj) {
        const ret = Module.set_threshold(obj);
        if (!ret.success) {
            throw new Error(ret.error);
        }
    }

    _arrayToHeap(data) {
        let typedArray = new Float32Array(data);
        let numBytes = typedArray.length * typedArray.BYTES_PER_ELEMENT;
        let ptr = Module._malloc(numBytes);
        let heapBytes = new Uint8Array(Module.HEAPU8.buffer, ptr, numBytes);
        heapBytes.set(new Uint8Array(typedArray.buffer));
        // --- 特定用ログ差し込み ---
        console.log("--- _arrayToHeap 内部検証 ---");
        console.log("A. 入力データの合計値(JS):", data.reduce((a, b) => a + b, 0));
        console.log("B. HEAPU8書き込み後の先頭4バイト(Wasm):", Module.HEAPU8.slice(ptr, ptr + 4));
        const checkPtr = ptr;
        const jsValue = typedArray[0];
        const wasmValue = Module.HEAPF32[checkPtr >> 2]; // 物理番地から直接Float32として読み出す

        console.log(`--- アドレス照合 ---`);
        console.log(`物理番地: ${checkPtr}`);
        console.log(`JS側の元の値: ${jsValue}`);
        console.log(`Wasmメモリから直接読んだ値: ${wasmValue}`);

        if (Math.abs(jsValue - wasmValue) > 0.000001) {
            console.error("!!! 致命的: コピーした瞬間に値が化けています。ブラウザのメモリ保護またはアライメント違反です。");
        }
        // ---
        return { ptr: ptr, buffer: heapBytes };
    }

    _convertToOrdinaryJsObject(emboundObj, prototype) {
        let newObj = { };
        for (const key of Object.getOwnPropertyNames(prototype)) {
            const descriptor = Object.getOwnPropertyDescriptor(prototype, key);

            if (descriptor && typeof descriptor.get === 'function') {
                newObj[key] = emboundObj[key]; // Evaluates the getter and assigns as an own property.
            }
        }
        return newObj;
    }

    //結果構造体をJavaScriptのオブジェクトに変換する
    _fillResultStruct(ret) {
        let props = Module.get_properties();
        console.log("推論完了。構造体変換開始...");

        let jsResult = {
            anomaly: ret.anomaly,
            results: []
        };

        for (let cx = 0; cx < ret.size(); cx++) {
            let c = ret.get(cx);
            if (props.model_type === 'object_detection' || props.model_type === 'constrained_object_detection') {
                jsResult.results.push({ label: c.label, value: c.value, x: c.x, y: c.y, width: c.width, height: c.height });
            }
            else {
                jsResult.results.push({ label: c.label, value: c.value });
            }
            c.delete();
        }

        if (props.has_object_tracking) {
            jsResult.object_tracking_results = [];
            for (let cx = 0; cx < ret.object_tracking_size(); cx++) {
                let c = ret.object_tracking_get(cx);
                jsResult.object_tracking_results.push({ object_id: c.object_id, label: c.label, value: c.value, x: c.x, y: c.y, width: c.width, height: c.height });
                c.delete();
            }
        }

        if (props.has_visual_anomaly_detection) {
            jsResult.visual_ad_max = ret.visual_ad_max;
            jsResult.visual_ad_mean = ret.visual_ad_mean;
            jsResult.visual_ad_grid_cells = [];
            for (let cx = 0; cx < ret.visual_ad_grid_cells_size(); cx++) {
                let c = ret.visual_ad_grid_cells_get(cx);
                jsResult.visual_ad_grid_cells.push({ label: c.label, value: c.value, x: c.x, y: c.y, width: c.width, height: c.height });
                c.delete();
            }
        }

        if (ret.freeform) {
            jsResult.freeform = [];
            for (let ix = 0; ix < ret.freeform.size(); ix++) {
                let arr = [];
                const tensor = ret.freeform.get(ix);
                for (let jx = 0; jx < tensor.size(); jx++) {
                    arr.push(tensor.get(jx));
                }
                jsResult.freeform.push(arr);
            }
        }

        
        ret.delete();
        console.log("構造体変換完了");

        return jsResult;
    }
}

// --- 設定・変数 ---
const classifier = new EdgeImpulseClassifier();
let audioBufferQueue = []; 
let isCapturing = false;
let savedVector = null; // 登録済みベクトル
let mode = 'waiting';    // 'register', 'compare', 'waiting'
const AUDIO_CONFIG = {
    smartphone:     {minAmplitude: 0.003},
    pc:             {minAmplitude: 0.001},
    esp:            {minAmplitude: 0.0003}// 仮（後で実測で調整）
};
function getDeviceType() {
    if (window.isESP) return 'esp'; // ← MQTTなどで明示
    if (/iPhone|Android/.test(navigator.userAgent)) return 'smartphone';
    return 'pc';
}

// グローバルスコープに公開
window.get_ww_data = get_ww_data;
window.ww_analyze_execute = ww_analyze_execute;
window.ww_score_check = ww_score_check;
window.getClassifier = () => classifier;

async function get_ww_data($check_msec = 2000) {
    // 初期化
    isCapturing = false;
    audioBufferQueue = [];

    // app.js で定義した共通関数から Context を取得
    const ctx = window.getSharedAudioContext();
    
    // 2. PWA/ブラウザ制限対策：録音の直前に必ず resume する
    if (ctx.state === 'suspended') {
        try {
            await ctx.resume();
        } catch (e) {
            console.warn("AudioContext resume failed:", e);
        }
    }

    // 3. 確定した ctx から sampleRate を取得（再宣言エラー防止のためここで1回だけ定義）
    const originalSampleRate = ctx.sampleRate;

    // マイクのストリームを取得
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (e) {
        throw new Error('マイクのアクセスが拒否されたか、マイクが見つかりません。');
    }

    const source = ctx.createMediaStreamSource(stream);
    const processor = ctx.createScriptProcessor(4096, 1, 1);

    processor.onaudioprocess = (e) => {
        if (!isCapturing) return;
        const inputData = e.inputBuffer.getChannelData(0);
        for (let i = 0; i < inputData.length; i++) {
            audioBufferQueue.push(inputData[i]);
        }
    };

    source.connect(processor);
    processor.connect(ctx.destination);

    console.log(`>>> 録音開始 (${$check_msec}ms)...`);
    isCapturing = true;

    await new Promise(resolve => setTimeout(resolve, $check_msec));

    isCapturing = false;
    console.log(`>>> 録音終了。合計蓄積数: ${audioBufferQueue.length}`);

    // 後始末
    source.disconnect();
    processor.disconnect();
    stream.getTracks().forEach(track => track.stop());

    const props = classifier.getProperties();
    const requiredCount = props.input_features_count;

    // --- サンプリングレート取得（修正：再宣言 const を削除） ---
    let resampled;
    const minRequired = Math.ceil(requiredCount * (originalSampleRate / 16000));
    
    if (audioBufferQueue.length < minRequired) {
        throw new Error(`録音データ不足 (${audioBufferQueue.length} / ${minRequired})`);
    }

    // 取得済みの originalSampleRate を使用してリサンプリング判定
    if (originalSampleRate === 16000) {
        resampled = new Float32Array(audioBufferQueue);
    } else {
        resampled = await resampleTo16kHz(audioBufferQueue, originalSampleRate);
    }

    console.log("resampled length:", resampled.length);

    // --- 無音チェック ---
    let sum = 0;
    for (let i = 0; i < resampled.length; i++) {
        sum += resampled[i] * resampled[i];
    }
    const rms = Math.sqrt(sum / resampled.length);

    console.log("RMS:", rms);

    const deviceType = getDeviceType();
    // 閾値を少し厳しめにするか、環境に合わせて調整
    const threshold = AUDIO_CONFIG[deviceType].minAmplitude * 2.0; 
    if (rms < threshold) {
        throw new Error(`声が小さすぎるか、入力がありません。(音量: ${rms.toFixed(5)})`);
    }

    // --- 長さを16000に揃える ---
    const MAX_LENGTH = requiredCount * 2;

    if (resampled.length > MAX_LENGTH) {
        resampled = resampled.slice(-MAX_LENGTH);
    } else if (resampled.length < requiredCount) {
        const padded = new Float32Array(requiredCount);
        padded.set(resampled);
        resampled = padded;
    }

    console.log("MFCC resampled", resampled.length);
    return resampled;
}

// MFCC用 分析実行
async function ww_analyze_execute(classifier, recordedData) {
    const props = classifier.getProperties();
    const required = props.input_features_count;

    // 1. ピーク検出で声の中心を探す
    const windowSize = 160; 
    let maxEnergy = -1;
    let peakIndex = 0;
    let currentEnergy = 0;

    for (let i = 0; i < windowSize; i++) {
        currentEnergy += recordedData[i] * recordedData[i];
    }
    maxEnergy = currentEnergy;

    for (let i = 1; i <= recordedData.length - windowSize; i++) {
        currentEnergy = currentEnergy - (recordedData[i - 1] ** 2) + (recordedData[i + windowSize - 1] ** 2);
        if (currentEnergy > maxEnergy) {
            maxEnergy = currentEnergy;
            peakIndex = i;
        }
    }

    let bestStartIndex = Math.max(0, peakIndex - 3200); 
    let bestEndIndex = Math.min(recordedData.length, bestStartIndex + required);
    const voiceFocusData = recordedData.slice(bestStartIndex, bestEndIndex);

    // 2. 切り出したデータから「無音部分」をカットして「声の部分」だけを抽出
    const source = Array.from(voiceFocusData);
    
    // 動的ノイズ閾値の推定（エネルギーベース）
    let maxAbs = 0;
    for (let i = 0; i < source.length; i++) {
        const a = Math.abs(source[i]);
        if (a > maxAbs) maxAbs = a;
    }
    
    // 最大音量の5%を切り出し閾値とする（感度をさらに大幅向上）
    const dynamicThreshold = Math.max(0.02, maxAbs * 0.05);

    let startIdx = 0;
    let endIdx = source.length - 1;

    // 前後の保護バッファ（1600サンプル = 0.1秒分）
    const bufferSize = 1600;

    for (let i = 0; i < source.length; i++) {
        if (Math.abs(source[i]) > dynamicThreshold) {
            startIdx = Math.max(0, i - bufferSize); 
            break;
        }
    }
    for (let i = source.length - 1; i > startIdx; i--) {
        if (Math.abs(source[i]) > dynamicThreshold) {
            endIdx = Math.min(source.length - 1, i + bufferSize);
            break;
        }
    }

    const voicedSegment = source.slice(startIdx, endIdx);
    
    // 3. 多次元特徴量（MFCC/音色）の抽出
    // 固定長(250)への圧縮をやめ、20ms(320サンプル)ごとのフレームデータとして抽出
    const frameSize = 320; 
    const stepSize = 160; // 50%オーバーラップ
    let finalFeatures = [];

    for (let i = 0; i + frameSize <= voicedSegment.length; i += stepSize) {
        const frame = voicedSegment.slice(i, i + frameSize);
        
        // 簡易的な周波数分析（4帯域のエネルギー分布）
        // 本来はFFTすべきだが、計算コストを考慮しデジタルフィルタ的な簡易積分を実施
        let bands = [0, 0, 0, 0]; // 低, 中低, 中高, 高
        for (let j = 0; j < frameSize; j++) {
            const val = Math.abs(frame[j]);
            if (j % 4 === 0) bands[0] += val;
            else if (j % 4 === 1) bands[1] += val;
            else if (j % 4 === 2) bands[2] += val;
            else bands[3] += val;
        }
        
        // 音量（RMS）も一つの次元として追加
        let sumSq = 0;
        for (let j = 0; j < frameSize; j++) sumSq += frame[j] * frame[j];
        const rms = Math.sqrt(sumSq / frameSize);
        
        // 正規化して量子化 (0-255)
        // 係数を大幅に上げ（500->3000）、小さな音色の違いを際立たせる
        const normalizedFrame = bands.map(b => Math.min(255, Math.round((b / frameSize) * 3000)));
        // RMS（音量）は少し抑えて音色（bands）の比重を相対的に高める
        normalizedFrame.push(Math.min(255, Math.round(rms * 500)));
        
        finalFeatures.push(normalizedFrame);
    }

    // トリミング後のサンプル数も情報として付加
    console.log(`音声抽出完了: ${voicedSegment.length} サンプルを ${finalFeatures.length} フレームの多次元データに変換`);
    return {
        features: finalFeatures,
        sampleCount: voicedSegment.length
    };
}

//スコア判定
function ww_score_check(vectorA, vectorB) {
    if (!vectorA || !vectorB || vectorA.length !== vectorB.length) return 0;

    let dot = 0;
    let normA = 0;
    let normB = 0;

    for (let i = 0; i < vectorA.length; i++) {
        dot += vectorA[i] * vectorB[i];
        normA += vectorA[i] * vectorA[i];
        normB += vectorB[i] * vectorB[i];
    }

    normA = Math.sqrt(normA);
    normB = Math.sqrt(normB);

    if (normA === 0 || normB === 0) return 0;

    const cosine = dot / (normA * normB);

    // 0〜1に正規化
    return (cosine + 1) / 2;
}

//録音後に16kHzへ変換
async function resampleTo16kHz(audioData, originalSampleRate) {
    const targetRate = 16000;

    const offlineCtx = new OfflineAudioContext(
        1,
        Math.ceil(audioData.length * targetRate / originalSampleRate),
        targetRate
    );

    const buffer = offlineCtx.createBuffer(1, audioData.length, originalSampleRate);
    buffer.copyToChannel(Float32Array.from(audioData), 0);

    const source = offlineCtx.createBufferSource();
    source.buffer = buffer;
    source.connect(offlineCtx.destination);
    source.start();

    const rendered = await offlineCtx.startRendering();
    return rendered.getChannelData(0);
}

