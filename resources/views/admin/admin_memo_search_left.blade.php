{{--検索--}}
<form method="GET" action="{{ route('admin.memo.index') }}">

    検索条件
    <div class="row g-3 align-items-end">
        <div class="col-6 col-md-12">
            ・タイトル
            <input type="text" name="search_title" class="form-control" value="{{$input['search_title'] ?? ''}}">
        </div>
        <div class="col-6 col-md-12">
            ・内容
            <input type="text" name="search_content" class="form-control" value="{{$input['search_content'] ?? ''}}">
        </div>
        
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>
