/**
 * スマートリモコン共通基盤クラス
 */
class SmartRemote {
    constructor(remoteId, csrfToken, irSendUrl, deviceId = null) {
        this.remoteId = remoteId;
        this.csrfToken = csrfToken;
        this.irSendUrl = irSendUrl;
        this.deviceId = deviceId; // ★ deviceId を保持
        this.isEditingMode = false;
    }

    // デバイスIDを動的にセット/更新するメソッド
    setDeviceId(deviceId) {
        this.deviceId = deviceId;
    }

    setEditMode(isEdit) {
        this.isEditingMode = isEdit;
    }

    sendSignal(buttonNum) {
        console.log(`SmartRemote.sendSignal() buttonNum: ${buttonNum}, deviceId: ${this.deviceId}`);

        const postData = {
            _token: this.csrfToken,
            remote_id: this.remoteId,
            button_num: buttonNum,
            test_flag: 0
        };

        // this.deviceId が存在する場合は POST パラメータに追加
        if (this.deviceId) {
            postData.device_id = this.deviceId;
        }

        return $.ajax({
            type: "POST",
            url: this.irSendUrl,
            data: postData
        });
    }

    sendLibrary(protocol, hex, bits, options = {}) {
        // ライブラリ型送信でも this.deviceId を補完
        const postData = Object.assign({
            _token: this.csrfToken,
            remote_id: this.remoteId,
            library_flag: 1,
            protocol: protocol,
            hex: hex,
            bits: bits,
            device_id: options.device_id || this.deviceId // ★ this.deviceId をフォールバック利用
        }, options);

        return $.ajax({
            type: "POST",
            url: this.irSendUrl,
            data: postData
        });
    }
}

// グローバルにインスタンスを保持するための入れ物
window.smartRemoteInstance = null;
