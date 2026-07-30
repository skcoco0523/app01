

<div id="edit_virtualremote_signal-modal" class="notification-overlay" onclick="closeModal('edit_virtualremote_signal-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <?//処理が複雑になるため、フォームではなくAPI?>
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newvirtualremoteModalLabel">リモコン編集：<label id="button_name">&nbsp;</label></h5>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('edit_virtualremote_signal-modal')"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="remote_id">
                <input type="hidden" id="button_num">
                <input type="hidden" id="button_name">
                <div class="mb-3">
                    <label for="my_devices" class="form-label">Myデバイス</label>
                    {{-- APIで取得したデバイスを動的に表示 --}}
                    <select name="device_id" id="device_select" class="form-control">
                        <option value="">デバイスを選択</option>
                    </select>
                </div>

                <?//選択した情報?>
                <div class="mb-3"><div id="device_info_area"></div></div>
                <div class="mb-3"><div id="process_area"></div></div>

            </div>
            <div class="modal-footer row gap-3 justify-content-center">
                <button type="button" id="cancel_btn" class="col-5 btn btn-secondary" onclick="closeModal('edit_virtualremote_signal-modal')">キャンセル</button>
            </div>
        </div>
    </div>
</div>
<script>

// モーダル全体で共有する状態変数
var getDevicesFlag              = false; // デバイス取得済みフラグ
var selectRemoteId              = null;  // 選択されたリモコンIDを保持
var selectButtonName            = null;  // 選択されたボタン名を保持
var selectButtonNum             = null;  // 選択されたボタン番号を保持
var selectDeviceId              = null;  // 選択されたデバイスIDを保持
var getReceiveData              = null;  // 受信した最新データを一時保持

document.addEventListener('DOMContentLoaded', function() {
    var deviceSelect                = document.getElementById('device_select');         // デバイス選択プルダウン
    var deviceInfoArea              = document.getElementById('device_info_area');      // 選択デバイスの状態
    var processArea                 = document.getElementById('process_area');      // 選択デバイスから受信した信号
    var confirmButton               = document.getElementById('confirm_btn');

    var lastSelectedDevice; // 最後に選択されたリモコンタイプを保持
    const modal                     = document.getElementById('edit_virtualremote_signal-modal');

    // 初期スタイルを JS で設定
    Object.assign(processArea.style, {
        border: "1px solid #ccc",padding: "10px",minHeight: "150px",maxHeight: "300px",overflow: "auto",backgroundColor: "#f9f9f9"
    });

    
    //リモコンボタン編集モーダル表示時、登録済みデバイスを取得しプルダウンに追加
    modal.addEventListener('modal:open', async function () {
        
        // openModalでセットされた値をJS変数へ格納
        selectRemoteId = document.getElementById('remote_id').value;
        selectButtonName = document.getElementById('button_name').textContent;
        selectButtonNum = document.getElementById('button_num').value;

        if(getDevicesFlag) return; // 既に取得済みなら再取得しない
        try {
            getDevicesFlag = true; // 取得済みフラグを立てる
            const deviceList = await get_iot_device();

            if (deviceList && deviceList.length > 0) {
                deviceList.forEach((device, index) => {
                    const option                = document.createElement('option');
                    option.value                = device.id;
                    option.dataset.type         = device.type;
                    option.dataset.type_name    = device.type_name;
                    option.textContent          = device.name;
                    deviceSelect.appendChild(option);
                    
                });
            } else {
                deviceSelect.innerHTML = '<option value="">デバイス未登録</option>';
            }
        } catch (err) {
            console.error(err);
            alert('デバイス取得中にエラーが発生しました。');
        }

    });

    modal.addEventListener('modal:close', () => {});

    // 登録済みデバイスの選択時
    deviceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex]; // 選択された <option> 要素を取得

        if (selectedOption && selectedOption.value !== '') {
            // data-html 属性からHTMLコンテンツを取得
            const deviceType        = selectedOption.dataset.type; 
            const deviceTypeName    = selectedOption.dataset.type_name; 
            selectDeviceId          = selectedOption.value; // 選択されたデバイスIDを保持    
            
            if (deviceTypeName && deviceTypeName.trim() !== '') {
                deviceInfoArea.innerHTML = '<p style="text-align: center; color: #888;">タイプ: ' + deviceTypeName + '</p>';

                //選択デバイスとの疎通確認
                let status = true; // 疎通確認結果（仮）===================================================================================
                let processMess = '';
                if(status){
                    processMess = '<p style="color: green;">疎通確認成功</p>';
                                    
                    //config/common/device_type を参照して表示を切り替え
                    switch(deviceType){
                        case "1": //赤外線リモコン
                            processMess+= '<button type="button" id="confirm_btn" class="btn btn-danger" onclick="ir_receive_request()">';
                            processMess+= '受信待機開始';
                            processMess+= '</button>';
                            
                            break;
                        case "101": //スマートロック
                            //施錠、開錠の設定はデバイス設定で行う
                            processMess += '<p>スマートロックの設定は<br>デバイス設定で行ってください。';
                            processMess +=      '<a href="' + iotDeviceUrlBase + '/' + selectedOption.value + '" class="btn btn-link">';
                            processMess +=          'デバイス設定<i class="fa fa-cog"></i>';
                            processMess +=      '</a>';
                            processMess += '</p>';
                            processMess+= '<button type="button" id="confirm_btn" class="btn btn-danger" onclick="add_signals()">';
                            processMess+= 'デバイス登録';
                            processMess+= '</button>';
                            break;
                        default:
                            break;
                    }
                }else{
                    processMess = '<p style="color: red;">疎通確認失敗<br>デバイスを確認してください。</p>';
                }

                processArea.innerHTML = processMess;
            }
        }
    });


});


//==================================================================
//API
//==================================================================
// 登録済みデバイス取得
async function get_iot_device() {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "get",
            url: getIotDevicesUrl,
            headers: {},
            //data: {user_id: user_id },
            data: {},
        })
        .done(data => {
            if (data && data.length > 0)    resolve(data);  // 成功時はresolveで結果を返す
            else                            resolve([]);  // データがない場合
        })
        .fail((xhr, status, error) => {
            console.error('Error fetching advertisement:', error);
            reject(error);  // 失敗時はrejectでエラーを返す
        });
    });
};

// 赤外線学習リクエスト デバイスに受信状態になるように指示する
async function ir_receive_request() {
    const deviceSelect = document.getElementById('device_select');
    const deviceId = deviceSelect.value;
    const processArea = document.getElementById('process_area');

    if (!deviceId) {
        alert('デバイスを選択してください。');
        return;
    }

    processArea.innerHTML = '<p style="color: blue;">デバイスにリクエストを送信中...</p>';

    try {
        const response = await $.ajax({
            type: "get",
            url: irReceiveRequestUrl,
            data: { device_id: deviceId }
        });

        if (response.success) {
            processArea.innerHTML = '<p style="color: orange;">デバイスに受信待機リクエスト中...</p>';
            // ステータスのポーリング開始
            start_status_polling(deviceId);
        } else {
            processArea.innerHTML = '<p style="color: red;">エラー: ' + response.msg + '</p>';
        }
    } catch (err) {
        console.error(err);
        processArea.innerHTML = '<p style="color: red;">通信エラーが発生しました。</p>';
    }
}

let statusPollingInterval;
function start_status_polling(deviceId) {
    if (statusPollingInterval) clearInterval(statusPollingInterval);
    
    let retryCount = 0;
    const maxRetries = 30; // 30秒間

    statusPollingInterval = setInterval(async () => {
        retryCount++;
        if (retryCount > maxRetries) {
            clearInterval(statusPollingInterval);
            document.getElementById('process_area').innerHTML = '<p style="color: red;">信号を受信しない状態が続いたため、受信状態を解除しました。</p>';
            return;
        }

        try {
            const response = await $.ajax({
                type: "get",
                url: getIotDeviceStatusUrl,
                data: { device_id: deviceId }
            });

            if (response.success) {
                const status = response.status;
                const processArea = document.getElementById('process_area');

                if (status == 3) { // Standby
                    processArea.innerHTML = '<p style="color: green; font-weight: bold;">準備完了！リモコンのボタンを押してください。</p>';
                } else if (status == 4) { // Received
                    if(response.receive_data){
                        processArea.innerHTML = '<p style="color: blue;">信号を受信しました！</p>';
                        getReceiveData = response.receive_data; // データをJS変数に保存
                        
                        processArea.innerHTML +='<div class="d-flex gap-2 justify-content-center">';
                        processArea.innerHTML += '<button type="button" class="btn btn-warning" onclick="test_send_signal()">テスト送信</button>';
                        processArea.innerHTML += '<button type="button" class="btn btn-primary" onclick="add_signals()">登録する</button>';
                        processArea.innerHTML += '</div>';
                    }else{
                        processArea.innerHTML = '<p style="color: red;">信号を受信しましたが、データが取得できませんでした。</p>';
                    }
                    
                    clearInterval(statusPollingInterval); // 受信完了したのでポーリング停止
                } else if (status == 5) { // Timeout
                    clearInterval(statusPollingInterval);
                    processArea.innerHTML = '<p style="color: red;">タイムアウトしました。信号が受信されませんでした。</p>';
                } else if (status == 1 && retryCount > 2) { // 途中で Online に戻ったら完了とみなす
                    clearInterval(statusPollingInterval);
                    processArea.innerHTML = '<p style="color: green;">信号の登録が完了しました。</p>';
                    // 必要に応じてここで再読み込みや表示更新
                }
            }
        } catch (err) {
            console.error('Polling error:', err);
        }
    }, 1000);
}
// テスト送信
async function test_send_signal() {
    const deviceSelect = document.getElementById('device_select');
    const deviceId = deviceSelect.value;
    if (!deviceId) return;

    try {
        const response = await $.ajax({
            type: "POST",
            url: irSendSignalUrl,
            data: {
                _token: '{{ csrf_token() }}',
                device_id: deviceId,
                test_flag: 1
            }
        });
        if (response.success) {
            //alert('テスト送信しました。');
        } else {
            alert('送信失敗: ' + response.msg);
        }
    } catch (err) {
        console.error(err);
        alert('通信エラーが発生しました。');
    }
}

    // 信号追加
    async function add_signals() {
        const processArea = document.getElementById('process_area');

        console.log("add_signals start");
        console.log("getReceiveData", getReceiveData);
        console.log("selectDeviceId", selectDeviceId);
        console.log("selectRemoteId", selectRemoteId);
        console.log("selectButtonNum", selectButtonNum);
        console.log("selectButtonName", selectButtonName);

        if (!selectDeviceId || !selectRemoteId || !selectButtonNum || !getReceiveData) {
            alert('情報が不足しています。');
            return;
        }
    
        try {
            const response = await $.ajax({
                type: "POST",
                url: irSignalSaveUrl,
                data: {
                    _token: '{{ csrf_token() }}',
                    device_id: selectDeviceId,
                    remote_id: selectRemoteId,
                    button_num: selectButtonNum,
                    signal_name: selectButtonName,
                    signal_data: getReceiveData,
                    category_name: 'リモコン' // 仮
                }
            });

        if (response.success) {
            processArea.innerHTML = '<p style="color: green;">信号の登録が完了しました！</p>';
            setTimeout(() => {
                closeModal('edit_virtualremote_signal-modal');
                // 必要に応じて画面リロードなど
            }, 2000);
        } else {
            alert('登録に失敗しました: ' + response.msg);
        }
    } catch (err) {
        console.error(err);
        alert('通信エラーが発生しました。');
    }
};


</script>