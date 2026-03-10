{{--検索--}}
<form method="GET" action="{{ route('admin.virtualremote.blade.index') }}">

    検索条件
    <div class="row g-3 align-items-end">
        <div class="col-4 col-md-12">
            ・種別
            <select name="search_kind" class="form-control">
                <option value=""  {{ ($input['search_kind'] ?? '') == ''  ? 'selected' : '' }}></option>
                @foreach (config('common.remote_kind') as $key => $value)
                    <option value="{{ $value }}" {{ ($input['search_kind'] ?? '') == (string)$value ? 'selected' : '' }}>{{ $key }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・ファイル名
            <input type="text" name="search_name" class="form-control" value="{{$input['search_name'] ?? ''}}">
        </div>
        <div class="col-4 col-md-12">
            ・テストフラグ
            <select name="search_test_flag" class="form-control">
                <option value=""  {{ ($input['search_test_flag'] ?? '') == ''  ? 'search_test_flag' : '' }}></option>
                <option value="1" {{ ($input['search_test_flag'] ?? '') == 1 ? 'selected' : '' }}>テスト</option>
            </select>
        </div>
        
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>
