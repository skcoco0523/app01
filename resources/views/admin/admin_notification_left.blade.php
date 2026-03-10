{{--検索--}}
<form method="GET" action="{{ route('admin.notification.index') }}">

    {{ $input['send_type'] == 'mail' ? 'メール' : 'プッシュ' }}通知対象
        <input type="hidden" name="send_type" value="{{$input['send_type'] ?? ''}}">
    <div class="row g-3 align-items-end">
        
        <div class="col-4 col-md-12">
            ・送信先
            <select id="send_target" name="send_target" class="form-control">
                <option value="0" {{ ($input['send_target'] ?? '') == '0' ? 'selected' : '' }}>全ユーザー</option>
                <option value="1" {{ ($input['send_target'] ?? '') == '1' ? 'selected' : '' }}>全管理者</option>
                <option value="2" {{ ($input['send_target'] ?? '') == '2' ? 'selected' : '' }}>指定ユーザー</option>
            </select>
        </div>

        <div id="search-field">
            <div class="col-4 col-md-12">
                ・ユーザー名
                <input type="text" name="search_name" class="form-control" value="{{$input['search_name'] ?? ''}}">
            </div>
            <div class="col-4 col-md-12">
                ・アドレス
                <input type="text" name="search_email" class="form-control" value="{{$input['search_email'] ?? ''}}">
            </div>
            <div class="col-4 col-md-12">
                ・フレンドコード
                <input type="text" name="search_friendcode" class="form-control" value="{{$input['search_friendcode'] ?? ''}}">
            </div>
            <div class="col-4 col-md-12">
                ・性別
                <select name="search_gender" class="form-control">
                    <option value=""  {{ ($input['search_gender'] ?? '') == ''  ? 'selected' : '' }}></option>
                    <option value="0" {{ ($input['search_gender'] ?? '') == '0' ? 'selected' : '' }}>男性</option>
                    <option value="1" {{ ($input['search_gender'] ?? '') == '1' ? 'selected' : '' }}>女性</option>
                </select>
            </div>
            <div class="col-4 col-md-12">
                ・公開
                <select name="search_release_flag" class="form-control">
                    <option value=""  {{ ($input['search_release_flag'] ?? '') == ''  ? 'selected' : '' }}></option>
                    <option value="0" {{ ($input['search_release_flag'] ?? '') == '0' ? 'selected' : '' }}>許可</option>
                    <option value="1" {{ ($input['search_release_flag'] ?? '') == '1' ? 'selected' : '' }}>拒否</option>
                </select>
            </div>
            <div class="col-4 col-md-12">
                ・ﾒｰﾙ送信
                <select name="search_mail_flag" class="form-control">
                    <option value="0" {{ ($input['search_mail_flag'] ?? '') == '0' ? 'selected' : '' }}>許可(指定不可)</option>
                </select>
            </div>
        </div>
        
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const search_field = document.getElementById('search-field');
    const send_target = document.getElementById("send_target");
    
    function toggleFields() {
        if(send_target.value === "0") {
            //送信先：全ユーザー
            search_field.style.display = 'none'; //検索不可
            search_field.querySelectorAll("input").forEach(input => {   //検索条件リセット
                input.value = "";
            });

        }else if(send_target.value === "1") {
            //送信先：全管理者
            search_field.style.display = 'none'; //検索不可
            search_field.querySelectorAll("input").forEach(input => {   //検索条件リセット
                input.value = "";
            });

        }else if(send_target.value === "2") {
            //送信先：指定ユーザー
            search_field.style.display = 'block'; //検索可能
        }
    }

    // 初期表示時
    toggleFields();

    // 値変更時
    send_target.addEventListener("change", toggleFields);
});
</script>