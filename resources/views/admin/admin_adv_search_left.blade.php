
{{--検索--}}
<form method="GET" action="{{ route('admin.adv.index') }}">

    検索条件
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-12">
            ・カテゴリ名
            <input type="text" name="name" class="form-control" value="{{$input['name'] ?? ''}}">
        </div>

        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>
