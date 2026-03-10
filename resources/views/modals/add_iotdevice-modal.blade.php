

<div id="add_iotdevice-modal" class="notification-overlay" onclick="closeModal('add_iotdevice-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content">
            <!-- 新規デバイス登録フォーム -->
            <form action="{{ route('iotdevice.activate') }}" method="POST">
                @csrf
                <input type="hidden" name="check_flag" value="false" >
                <div class="modal-header">
                    <h5 class="modal-title" id="newIotDeviceModalLabel">新規デバイス登録</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('add_iotdevice-modal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="iotdevice_name" class="form-label">デバイス名</label>
                        <input type="text" class="form-control" id="iotdevice_name" name="iotdevice_name" placeholder="デバイス名を入力" required>
                    </div>
                    <div class="mb-3">
                        <label for="pincode" class="form-label">PINcode</label>
                        <input type="nunber" class="form-control" id="pincode" name="pincode" placeholder="PINcodeを入力" required>
                    </div>
                    
                </div>
                <div class="modal-footer row gap-3 justify-content-center">

                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('add_iotdevice-modal')">キャンセル</button>
                    <button id="device_add_confirm_button" type="submit" class="col-5 btn btn-danger disabled">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    
document.addEventListener('DOMContentLoaded', function() {
    var pincodeInput = document.getElementById('pincode');
    var iotdeviceNameInput = document.getElementById('iotdevice_name');
    var confirmButton = document.getElementById('device_add_confirm_button');
    function checkInput() {
        // 入力がある場合は保存ボタンを有効にする
        if (pincodeInput.value.trim() !== '' && iotdeviceNameInput.value.trim() !== '') 
            confirmButton.classList.remove('disabled');
        else
            confirmButton.classList.add('disabled');
    }
    // 入力が変更されたときにチェックする
    pincodeInput.addEventListener('input', checkInput);
    iotdeviceNameInput.addEventListener('input', checkInput);
    // 初期状態をチェックする
    checkInput();
});
</script>