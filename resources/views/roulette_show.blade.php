@extends('layouts.app')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('home') }}'"></i>
    <div id="sections">
        <div id="spinArea">
            <!--円グラフ-->
            <canvas id="roulette"></canvas>
        </div>
        <div id="pointer"></div>
    </div>

    <div id="resultDisplay" style="display:none; padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc; margin-top: 10px;">
        <span id="resultText"></span>
    </div>

    <button id="spinButton" class="btn btn-primary">スタート</button>

    <p id="result" class="text-center mt-4"></p>
    <!-- 入力フォーム -->
    <div class="input-form mt-4">
        <form id="rouletteForm">
            <!-- データ選択 -->
            <div class="mb-3">
                <div class="row">
                    <div class="col-4">
                        <select class="form-select" id="dataSelector" style="max-width: 100%;">
                            <option value="0">1</option>
                            <option value="1">2</option>
                            <option value="2">3</option>
                            <option value="3">4</option>
                            <option value="4">5</option>
                        </select>
                    </div>
                    <div class="col-5">
                        <input type="text" class="form-control" id="dataName">
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </div>
                <input type="hidden" id="dataCnt" value="5">
            </div>
                
            </div>
            <div class="mb-3">
                <div class="row">
                    <div class="col-4">
                        <label class="form-label">項目(必須)</label>
                    </div>
                    <div class="col-4">
                        <label class="form-label">値</label>
                    </div>
                    <div class="col-4">
                        <label class="form-label">倍率</label>
                    </div>
                </div>
                @for($i=0; $i<5; $i++)
                <div class="row">
                    <div class="col-4">
                        <input type="text" class="form-control" id="itemName{{$i}}" placeholder="項目">
                    </div>
                    <div class="col-4">
                        <input type="text" class="form-control" id="itemValue{{$i}}" placeholder="値">
                    </div>
                    <div class="col-4">
                    <input type="number" class="form-control" id="multiple{{$i}}" placeholder="倍率">
                    </div>
                </div>
                @endfor
            </div>
        </form>
    </div>

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const dataSelector = document.getElementById('dataSelector');
        const dataCnt   = $('#dataCnt').val();
        
        //円グラフの初期化が必要なため定義しておく
        let rouletteChart = null;

        //回転処理関連
        let isSpinning = false;  // 回転中かどうかのフラグ
        let rotationSpeed = 40;  // 回転速度
        let rotationSpeed_tmp = rotationSpeed;//初期の回転速度
        let currentRotation = 0; // 現在の回転角度
        let rotationInterval;    // 回転のインターバルID

        // 再生する音声ファイルをroadしておく       選択、ルーレット確定、各定時もう一度
        preloadSounds(["poka01", "correct_answer3", "blip03"]);

        // 初期データの読み込み
        loadData();

        // データ切り替え時の処理
        dataSelector.addEventListener('change', loadData);

        // クッキーからデータを取得してフォームに反映
        function loadData() {
            const selectedData = dataSelector.value; // 変更された選択の値を取得

            //const dataName = getCookie(`roulette_data${selectedData}_name`) || '';  //選択データ名を保存
            const dataNameArray = rouletteNameGetCookie(dataCnt);
            
            // 配列のデータをもとにoption要素を追加
            dataSelector.innerHTML = "";
            dataNameArray.forEach((name, index) => {
                const option = document.createElement("option");
                option.value = index;
                option.textContent = `${index + 1}: ${name}`;
                dataSelector.appendChild(option);
                    // もし以前の選択値と一致するなら選択状態にする
                if (option.value === selectedData) {
                    option.selected = true;
                }
            });
            
    
            const dataName = dataNameArray[selectedData];
            document.getElementById(`dataName`).value = dataName;
            


            const rouletteSections = []; // セクションデータを保持する配列
            // フォームに反映する処理（必要に応じて追加）
            const rouletteData = rouletteGetCookie(selectedData, dataCnt);
            rouletteData.forEach((data, index) => {
                document.getElementById(`itemName${index}`).value = data.itemName;
                document.getElementById(`itemValue${index}`).value = data.itemValue;
                document.getElementById(`multiple${index}`).value = data.multiple;

                if (data.sectionStr) {
                    rouletteSections.push({ sectionStr: data.sectionStr, multiple: data.multiple });
                }
            });
            rouletteSections.push({ sectionStr: "もう一回", multiple: 1 });

            
            updateRoulette(rouletteSections); // ルーレットのセクションを更新
        }

        function updateRoulette(sections) {
            
            //console.log(`sections: `,sections);
            //let total_cnt = 0;  //  倍率を考慮した総件数
            //sections.forEach((section, index) => {total_cnt += Number(section.multiple);});
            let total_cnt = sections.reduce((sum, section) => sum + Number(section.multiple || 1), 0);
            
            let rotate = -90;    //グラフテキストの角度
            const perRotate = 360 / total_cnt; //1倍率当たりの角度

            //const roulette = document.getElementById('sections');
            const roulette = document.getElementById('roulette').getContext('2d');;
            if (!roulette) return;


            const data = sections.map(section => Number(section.multiple));  // 各セクションの数値（multiple）を取得
            const labels = sections.map(section => section.sectionStr);      // セクション名を取得

            // 既存のグラフがあれば破棄する
            if (rouletteChart) rouletteChart.destroy();

            Chart.register(ChartDataLabels);
            // 円グラフの作成
            rouletteChart = new Chart(roulette, {
                type: 'pie',  // 円グラフの指定（ドーナツ型ではなく普通の円グラフ）
                data: {
                    labels: labels,  // セクション名をラベルとして設定
                    datasets: [{
                        data: data,  // 各セクションの割合データ
                        backgroundColor: [
                            'rgb(135, 206, 235)',  // 明るいスカイブルー
                            'rgb(144, 238, 144)',  // ライトグリーン
                            'rgb(255, 255, 102)',  // 明るいイエロー
                            'rgb(221, 160, 221)',  // 柔らかいラベンダー
                            'rgb(255, 165, 0)',    // ライトオレンジ
                            'rgb(240, 128, 128)'   // ライトコーラル
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            //position: 'top',
                            display: false,  // 凡例（ラベル）を非表示にする
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return `${tooltipItem.label}: ${tooltipItem.raw}`;  // パーセンテージ表示
                                }
                            }
                        },
                        datalabels: {  // ここでテキストを設定
                            anchor: 'center',  // テキストを円の中央に配置
                            align: 'center',   // 中央に揃える
                            color: 'black',    // テキストの色
                            font: {
                                weight: 'bold',
                                size: 16           // テキストサイズ
                            },
                            formatter: function(value, ctx) {
                                // sectionStrを表示
                                const section = ctx.chart.data.labels[ctx.dataIndex]; // 対応するラベル（sectionStr）を取得
                                // 文字数が10を超える場合、折り返しのために改行を追加
                                if (section.length > 10) {
                                    const middle = Math.floor(section.length / 2);
                                    return section.substring(0, middle) + '\n' + section.substring(middle);
                                }
                                return section;  // セクション名を表示
                            },
                            rotation: function(ctx) {
                                const index = ctx.dataIndex;                // 現在のセクションのインデックス
                                const adjusted = perRotate * data[index];   //調整角度
                                rotate += adjusted;
                                // 中心から外向きに45度調整
                                return rotate - (adjusted/2);  // 各セクションの中心にするため　調整値の中央にする
                            }

                        }
                    }
                }
            });

        }

        function rouletteSetCookie(selectedData,dataCnt){

            //選択データ名を保存
            setCookie(`roulette_data${selectedData}_name`, $(`#dataName`).val(), 180, "/");
            
            for (let i = 0; i < dataCnt; i++) {
                // 入力された値を取得
                const itemName  = $(`#itemName${i}`).val();
                let itemValue = $(`#itemValue${i}`).val();
                let multiple   = $(`#multiple${i}`).val();
                if(itemName){
                    if(multiple<1) multiple=1;
                }else{
                    itemValue = "";
                    multiple = "";
                }
                // 設定されたアイテム名とポイント数を表示（またはサーバーに送信）
                //console.log(`項目名: ${itemName}, 値: ${itemValue}, 数: ${multiple}`);

                //クッキーに180日保存
                setCookie(`roulette_item${selectedData}_name${i}`, itemName, 180, "/");
                setCookie(`roulette_item${selectedData}_val${i}`, itemValue, 180, "/");
                setCookie(`roulette_item${selectedData}_cnt${i}`, multiple, 180, "/");
            }
        }
        function rouletteGetCookie(selectedData, dataCnt) {
            let cookieData = [];
            for (let i = 0; i < dataCnt; i++) {
                // クッキーからデータ取得
                const itemName = getCookie(`roulette_item${selectedData}_name${i}`) || '';
                const itemValue = getCookie(`roulette_item${selectedData}_val${i}`) || '';
                const multiple = getCookie(`roulette_item${selectedData}_cnt${i}`) || '';
                const sectionStr = itemValue ? itemName + "[" + itemValue + "]" : itemName;

                // 配列にオブジェクトとしてデータを追加
                cookieData.push({
                    itemName,itemValue,multiple,sectionStr
                });
            }

            //console.log(`cookieData: `,cookieData);
            return cookieData;
        }
        function rouletteNameGetCookie(dataCnt) {
            let cookieData = [];
            for (let i = 0; i < dataCnt; i++) {
                // クッキーからデータ取得
                const dataName = getCookie(`roulette_data${i}_name`) || '';  //選択データ名を保存
                // 配列にオブジェクトとしてデータを追加
                cookieData.push(dataName);
            }
            //console.log(`cookieData: `,cookieData);
            return cookieData;
        }

        // 入力フォームが送信されたときの処理
        $('#rouletteForm').on('submit', function (event) {
            event.preventDefault();  // フォーム送信時のページリロードを防ぐ
            const selectedData = dataSelector.value;
            dataSelector.value = selectedData;
            //クッキーを保存
            rouletteSetCookie(selectedData,dataCnt);
            loadData();

        });

        //ルーレット判定処理
        let check_flag = 0;
        $('#spinButton').on('click', function (event) {
            const spinButton = document.getElementById('spinButton');

            //SoundManager.play('和音テスト',0,200,0);
            //SoundManager.play('和音テスト',0,500,0);
            // 停止時のみ　スタート処理
            if (!isSpinning) {
                SoundManager.play('poka01');
                // 結果表示エリアを表示
                const resultDisplayElement = document.getElementById('resultDisplay');
                resultDisplayElement.style.display = 'none'; // 結果表示エリアを非表示

                spinButton.textContent = 'ストップ'; // ボタンの表示を変更
                const selectedData = dataSelector.value;
                //クッキーを保存
                rouletteSetCookie(selectedData,dataCnt);

                isSpinning = true;  // 回転開始
                const spinArea = document.getElementById('spinArea');
                rotationSpeed = rotationSpeed_tmp;  //変動してしまうため再度格納
                
                // 回転の開始
                rotationInterval = setInterval(function () {
                    // 回転の更新
                    currentRotation += rotationSpeed;
                    // 回転を適用
                    spinArea.style.transform = `rotate(${currentRotation}deg)`;
                    // 停止クリックで処理終了
                    if(check_flag) clearInterval(rotationInterval);

                }, 10); // 10FPSで回転させる（10ms毎に更新）

                // 回転時の音再生
                soundInterval = setInterval(function () {

                    SoundManager.play('rouletteSpin');
                    // 停止クリックで処理終了
                    if(check_flag) clearInterval(soundInterval);

                }, 50); // 50ms毎に更新
                
            
            //停止処理
            }else{
                //spinButton.textContent = 'スタート'; // ボタンの表示を変更
                //現在判定中でなければ
                let playProgress = 0;
                let lastSoundTime = 0;  // 最後に音を再生した時間（ミリ秒単位）
                if(!check_flag){
                    SoundManager.play('poka01');
                    // 回転の停止
                    const decelerationInterval = setInterval(function () {
                        check_flag = 1;
                        

                        if (rotationSpeed > 0.05) {
                            //減速　徐々に遅く
                            if(rotationSpeed > 5){
                                rotationSpeed *= 0.985;
                            }else if(rotationSpeed > 1){
                                rotationSpeed *= 0.990;
                            }else{
                                rotationSpeed *= 0.995;
                            }
                            currentRotation += rotationSpeed; // 回転角度を更新
                            spinArea.style.transform = `rotate(${currentRotation}deg)`; // 回転を適用


                            playProgress += rotationSpeed;
                            if(playProgress >= rotationSpeed_tmp){
                                playProgress = 0;
                                // 現在時刻を取得 (ms)
                                const now = performance.now();

                                // 前回再生してから50ms以上経過していたら
                                if (now - lastSoundTime >= 50) {
                                    playProgress = 0;  // 進捗リセット
                                    SoundManager.play('rouletteSpin');
                                    lastSoundTime = now;  // 再生時刻を更新
                                }
                            }


                        } else {
                            // 完全停止
                            clearInterval(decelerationInterval);
                            isSpinning = false; // 回転終了
                            rotationSpeed = 0; // 回転速度をリセット

                            currentRotation = currentRotation % 360; // 角度を360度の範囲に収める
                            check_rotate = 360 - currentRotation; //逆回転だから入れ替える
                            // 停止時の結果処理
                            //stopRoulette(currentRotation);
                            //console.log(`check_rotate: `,check_rotate);

                            //対象データ判定
                            const selectedData = dataSelector.value; // 変更された選択の値を取得
                            const rouletteData = rouletteGetCookie(selectedData, dataCnt);
                            rouletteData.push({ sectionStr: "もう一回", multiple: 1 });

                            const totalCnt = rouletteData.reduce((sum, data) => sum + Number(data.multiple), 0);
                            const odds = 360 / totalCnt;

                            let rotate = 0;
                            let targetStr = "";

                            //あたり判定
                            rouletteData.forEach((data, index) => {
                                rotate += odds * data.multiple;
                                if(check_rotate < rotate && targetStr == "") targetStr = data.sectionStr;
                            });

                            

                            // 結果表示エリアを表示
                            const resultDisplayElement = document.getElementById('resultDisplay');
                            resultDisplayElement.style.display = 'block'; // 結果表示エリアを表示

                            const resultTextElement = document.getElementById('resultText');
                            
                            if(targetStr == "もう一回"){
                                SoundManager.play('blip03');
                                resultTextElement.textContent = targetStr;
                            }else{
                                SoundManager.play('correct_answer3');
                                resultTextElement.textContent = $(`#dataName`).val() + ':' + targetStr;
                            }
                            
                            //alert(`結果: ${targetStr}`); // 結果をアラートで表示
                            check_flag = 0;                     //処理終了
                            spinButton.textContent = 'スタート'; // ボタンの表示を変更


                        }
                    }, 10); // 10FPSで回転させる（10ms毎に更新）
                }
            }
        });
    });
</script>


<style>

/* ルーレットのセクション */
#sections {
    position: relative;
    width: 80%;
    max-width: 500px; /* ルーレットのサイズを一定に */
    aspect-ratio: 1 / 1; /* 正方形を維持 */
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* 回転エリア */
#spinArea {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ルーレット */
#roulette {
    position: absolute;
    width: 100%;
    height: 100%;
}

/* ポインタ */
#pointer {
    position: absolute;
    width: 0;
    height: 0;
    border-left: 15px solid transparent;
    border-right: 15px solid transparent;
    border-top: 40px solid red; /* 下向き三角形 */
    
    top: -20px; /* ルーレットの外枠に密着 */
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
}

/* スタートボタン */
#spinButton {
    position: relative;
    margin-top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 15;
}

#resultDisplay {
    padding: 20px;
    background-color: #e7f3ff;
    border: 2px solid #007bff;
    border-radius: 5px;
    margin-top: 20px;
    font-size: 18px;
    text-align: center;
}
</style>
