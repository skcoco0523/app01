window.showNotification = function showNotification(message_org, type, sec) {
    const notification = document.getElementById('notification');
    //アイコン情報：https://fontawesome.com/

    let message = message_org.replace(/\n/g, '<br>');
    notification.innerHTML = ``;
    //notification.innerHTML+= `<div class="icon-55">`;
    switch(type){
        case "device_add":    //デバイス追加
            notification.innerHTML += `<i class="icon-50 fa-solid fa-computer fa-bounce red "></i>`;
            break;
        case "device_del":    //デバイス削除
            notification.innerHTML += `<i class="icon-50 fa-solid fa-computer fa-shake red "></i>`;
            break;
        case "device_chg":    //デバイス変更
            notification.innerHTML += `<i class="icon-50 fa-solid fa-computer fa-fade red "></i>`;
            break;

        case "remote_add":    //リモコン追加
            notification.innerHTML += `<i class="icon-50 fa-solid fa-mobile fa-bounce red "></i>`;
            break;
        case "remote_del":    //リモコン削除
            notification.innerHTML += `<i class="icon-50 fa-solid fa-mobile fa-shake red "></i>`;
            break;
        case "remote_chg":    //リモコン変更
            notification.innerHTML += `<i class="icon-50 fa-solid fa-mobile fa-fade red "></i>`;
            break;

        case "note_add":    //メモ登録
            notification.innerHTML += `<i class="icon-50 fa-solid fa-note-sticky fa-bounce red "></i>`;
            break;
        case "note_del":    //メモ削除
            notification.innerHTML += `<i class="icon-50 fa-solid fa-note-sticky fa-shake red "></i>`;
            break;
        case "note_chg":    //メモ変更
            notification.innerHTML += `<i class="icon-50 fa-solid fa-note-sticky fa-fade red "></i>`;
            break;

        case "profile":    //プロフィール変更
            notification.innerHTML += `<i class="icon-50 fa-solid fa-address-card fa-fade red "></i>`;
            break; 
        case "send":    //送信
            notification.innerHTML += `<i class="icon-50 fa-solid fa-envelope fa-fade red "></i>`;
            break; 
        case "friend":    //フレンド関連
            notification.innerHTML += `<i class="icon-50 fa-solid fa-user-group fa-fade red "></i>`;
            break;
        case "mypl_create":    //マイプレイリスト作成
            notification.innerHTML += `<i class="icon-50 fa-solid fa-list-ul fa-bounce red "></i>`;
            break;
        case "mypl_chg":    //マイプレイリスト変更
            notification.innerHTML += `<i class="icon-50 fa-solid fa-list-ul fa-fade red "></i>`;
            break; 
        case "mypl_del":    //削除
            notification.innerHTML += `<i class="icon-50 fa-solid fa-list-ul fa-shake red "></i>`;
            break;
        case "ww_test_ok":    //音声指紋テストOK
            notification.innerHTML += `<i class="icon-50 fa-solid fa-lock-open fa-bounce red "></i>`;
            break;
        case "ww_test_ng":   //音声指紋テストNG
            notification.innerHTML += `<i class="icon-50 fa-solid fa-lock fa-shake red "></i>`;
            break;


        case "error":    //エラー
            notification.innerHTML += `<i class="icon-50 fa-solid fa-triangle-exclamation fa-shake red "></i>`;
            break;
        
        default:
            
            break;
    }
    //notification.innerHTML += `</div>`;
    notification.innerHTML += `<p>${message}</p>`;

    notification.style.display = 'block';

    if (type === "loading") {
        // 半分の時刻が経過したらスピナーを非表示にしてメッセージを表示
        setTimeout(() => {
            document.getElementById('spinnerSection').style.display = 'none';
            document.getElementById('messageSection').style.display = 'block';
        }, sec / 2);
    }

    // 指定された秒数後に通知を非表示にする
    setTimeout(hideNotification, sec);
}

window.hideNotification = function hideNotification() {
    document.getElementById('notification').style.display = 'none';
}
