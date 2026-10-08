// モーダルの重ね順（z-index）管理用変数
let currentModalZIndex = 1050;

window.openModal = function openModal(modal_id, params = {}) {
    var modal = document.getElementById(modal_id);

    if (!modal) {
        console.warn(`Modal with ID "${modal_id}" not found`);
        return;
    }

    // ▼ 追加: 後から開いたモーダルを常に前面に表示させるため z-index を加算
    currentModalZIndex += 10;
    modal.style.zIndex = currentModalZIndex;

    // フォームIDとコールバック関数の保持
    modal._formId = params.form_id || null;
    modal._onConfirm = params.onConfirm || null;

    // params のキーに対応する要素に値をセット
    Object.keys(params).forEach(function(key) {
        const elements = modal.querySelectorAll("#" + key);

        elements.forEach(function(el) {
            if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
                const value = params[key];

                // オブジェクト・配列はJSON文字列として保持する
                if (typeof value === "object" && value !== null) {
                    el.value = JSON.stringify(value);
                } else {
                    el.value = value;
                }
            } else if (
                el.tagName === "H5" ||
                el.tagName === "LABEL" ||
                el.tagName === "BUTTON" ||
                el.tagName === "SPAN" ||
                el.tagName === "DIV"
            ) {
                el.textContent = params[key];
            }
        });
    });
    
    // 共通モーダル使用時の処理
    const userChkArea   = modal.querySelector('#user_chk_area');
    const userChkBox    = modal.querySelector('#user_chk_box');
    const cancelBtn     = modal.querySelector('#cancel_btn');
    const confirmBtn    = modal.querySelector('#confirm_btn');

    if(modal_id === 'common-modal'){
        cancelBtn.style.display  = ('cancel_btn'  in params) ? '' : 'none';
        confirmBtn.style.display = ('confirm_btn' in params) ? '' : 'none';

        if (params.user_chk === true) {
            if (userChkArea) userChkArea.style.display = 'block';
            
            userChkBox.checked = false;
            confirmBtn.disabled = true;

            userChkBox.onchange = () => {
                if(userChkBox.checked)  confirmBtn.disabled = false;
                else                    confirmBtn.disabled = true;
            };

        } else {
            if (userChkArea) userChkArea.style.display = 'none';
            confirmBtn.disabled = false;
        }
    }

    modal.dispatchEvent(new Event('modal:open'));
    modal.style.display = 'block';
}

window.closeModal = function closeModal(modal_id) {
    const modal = document.getElementById(modal_id);
    if (!modal) return;
    modal.dispatchEvent(new Event('modal:close'));
    modal.style.display = 'none';
}

window.modalConfirm = function modalConfirm(modal_id) {
    const modal = document.getElementById(modal_id);
    if (!modal) return console.warn(`Modal with ID "${modal_id}" not found`);

    if (typeof modal._onConfirm === 'function') {
        modal._onConfirm();
    } else if (modal._formId) {
        const form = document.getElementById(modal._formId);
        if (form) form.submit();
    } else {
        console.warn(`Neither form_id nor onConfirm is set in modal`);
    }

    closeModal(modal_id);
}