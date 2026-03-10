{{--検索--}}
<form method="GET" action="{{ route('admin.user.index') }}">

    検索条件
    <div class="row g-3 align-items-end">
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
                <option value=""  {{ ($input['search_mail_flag'] ?? '') == ''  ? 'selected' : '' }}></option>
                <option value="0" {{ ($input['search_mail_flag'] ?? '') == '0' ? 'selected' : '' }}>許可</option>
                <option value="1" {{ ($input['search_mail_flag'] ?? '') == '1' ? 'selected' : '' }}>拒否</option>
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・種別
            <select name="search_admin_flag" class="form-control">
                <option value=""  {{ ($input['search_admin_flag'] ?? '') == ''  ? 'selected' : '' }}></option>
                <option value="0" {{ ($input['search_admin_flag'] ?? '') == '0' ? 'selected' : '' }}>ユーザー</option>
                <option value="1" {{ ($input['search_admin_flag'] ?? '') == '1' ? 'selected' : '' }}>管理者</option>
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・デバイス追加権限
            <select name="search_dev_reg_lock_flag" class="form-control">
                <option value=""  {{ ($input['search_dev_reg_lock_flag'] ?? '') == ''  ? 'selected' : '' }}></option>
                <option value="0" {{ ($input['search_dev_reg_lock_flag'] ?? '') == '0' ? 'selected' : '' }}>許可</option>
                <option value="1" {{ ($input['search_dev_reg_lock_flag'] ?? '') == '1' ? 'selected' : '' }}>拒否</option>
            </select>
        </div>
        
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>