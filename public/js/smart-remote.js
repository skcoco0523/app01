/**
 * スマートリモコン共通基盤クラス
 */
class SmartRemote {
    constructor(remoteId, csrfToken, irSendUrl) {
        this.remoteId = remoteId;
        this.csrfToken = csrfToken;
        this.irSendUrl = irSendUrl;
        this.isEditingMode = false;
    }

    setEditMode(isEditing) {
        this.isEditingMode = isEditing;
    }

    /**
     * 学習済み信号（RAW）を送信
     */
    sendSignal(buttonNum) {
        console.log(`SmartRemote.sendSignal() buttonNum: ${buttonNum}`);
        return $.ajax({
            type: "POST",
            url: this.irSendUrl,
            data: {
                _token: this.csrfToken,
                remote_id: this.remoteId,
                button_num: buttonNum,
                test_flag: 0
            }
        });
    }

    /**
     * ライブラリ信号を送信
     */
    sendLibrary(protocol, hex, bits, options = {}) {
        console.log(`SmartRemote.sendLibrary() protocol: ${protocol}`, options);
        const sendData = {
            _token: this.csrfToken,
            remote_id: this.remoteId,
            protocol: protocol,
            hex: hex,
            bits: bits,
            library_flag: 1,
            ...options
        };

        return $.ajax({
            type: "POST",
            url: this.irSendUrl,
            data: sendData
        });
    }
}

// グローバルにインスタンスを保持するための入れ物
window.smartRemoteInstance = null;
