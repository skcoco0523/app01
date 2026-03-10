

<div id="edit_virtualremote_signal-modal" class="notification-overlay" onclick="closeModal('edit_virtualremote_signal-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <?//処理が複雑になるため、フォームではなくAPI?>
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newvirtualremoteModalLabel">リモコン編集：<label id="button_name">&nbsp;</label></h5>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('edit_virtualremote_signal-modal')"></button>
            </div>
            <div class="modal-body">
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
    
document.addEventListener('DOMContentLoaded', function() {
    var deviceSelect                = document.getElementById('device_select');         // デバイス選択プルダウン
    var deviceInfoArea              = document.getElementById('device_info_area');      // 選択デバイスの状態
    var processArea                 = document.getElementById('process_area');      // 選択デバイスから受信した信号
    var confirmButton               = document.getElementById('confirm_btn');

    var lastSelectedDevice; // 最後に選択されたリモコンタイプを保持
    var get_devices_flag            = false; // デバイス取得済みフラグ
    const modal                     = document.getElementById('edit_virtualremote_signal-modal');

    // 初期スタイルを JS で設定
    Object.assign(processArea.style, {
        border: "1px solid #ccc",padding: "10px",minHeight: "150px",maxHeight: "300px",overflow: "auto",backgroundColor: "#f9f9f9"
    });

    
    //リモコンボタン編集モーダル表示時、登録済みデバイスを取得しプルダウンに追加
    modal.addEventListener('modal:open', async function () {
        if(get_devices_flag) return; // 既に取得済みなら再取得しない
        try {
            get_devices_flag = true; // 取得済みフラグを立てる
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
            
            if (deviceTypeName && deviceTypeName.trim() !== '') {
                deviceInfoArea.innerHTML = '<p style="text-align: center; color: #888;">タイプ: ' + deviceTypeName + '</p>';

                //選択デバイスとの疎通確認
                let status = true; // 疎通確認結果（仮）===================================================================================
                let processMess = '';
                if(status){
                    processMess = '<p style="color: green;">疎通確認成功</p>';
                                    
                    //config/common/device_type を参照して表示を切り替え
                    switch(deviceType){
                        case "0": //赤外線リモコン
                            processMess+= '<button type="button" id="confirm_btn" class="btn btn-danger" onclick="send_ir_learn_request()">';
                            processMess+= '受信待機開始';
                            processMess+= '</button>';
                            
                            break;
                        case "1": //スマートロック
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

// 赤外線学習リクエスト
async function send_ir_learn_request() {
    console.log("send_ir_learn_request called");
    /*
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
    */
};
// 信号追加
async function add_signals() {
    console.log("add_signals called");
    /*
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
    */
};


</script>