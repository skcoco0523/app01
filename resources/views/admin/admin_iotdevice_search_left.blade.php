{{--検索--}}
<form method="GET" action="{{ route('admin.iotdevice.index') }}">

    検索条件
    <div class="row g-3 align-items-end">
        <div class="col-4 col-md-12">
            ・MACアドレス
            <input type="text" name="search_addr" class="form-control" value="{{$input['search_addr'] ?? ''}}">
        </div>
        <div class="col-4 col-md-12">
            ・所有者ID
            <input type="text" name="search_owner_id" class="form-control" value="{{$input['search_owner_id'] ?? ''}}">
        </div>
        <div class="col-4 col-md-12">
            ・デバイスタイプ
            <select name="search_type" class="form-control">
                <option value=""  {{ ($input['search_type'] ?? '') == ''  ? 'selected' : '' }}></option>
                @foreach (config('common.device_info') as $key => $value)
                    <option value="{{ $key }}" {{ ($input['search_type'] ?? '') == $key ? 'selected' : '' }}>{{ $value['type_name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・バージョン
            <select name="search_ver" class="form-control">
                <option value=""  {{ ($input['search_ver'] ?? '') == ''  ? 'selected' : '' }}></option>
                @for ($i=1; $i<=10; $i++ )
                    <option value="{{ $i }}" {{ ($input['search_ver'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・PINcode
            <input type="number" name="search_pincode" class="form-control" value="{{$input['search_pincode'] ?? ''}}">
        </div>
        
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>
