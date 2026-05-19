
{{-- 広告カテゴリ登録処理 --}}
<form id="adv_reg_form" method="POST" action="{{ route('admin.adv.store') }}">
    @csrf

    <div class="row g-3 align-items-stretch mb-3">
        <!-- カテゴリ名 -->
        <div class="col-12 col-md-4">
            <label for="inputname" class="form-label">カテゴリ名</label>
            <input type="text" name="name" class="form-control" placeholder="カテゴリ名" value="{{ $input['name'] ?? '' }}">
        </div>
        <!-- 検索キーワード -->
        <div class="col-12 col-md-6">
            <label for="inputkeywords" class="form-label">検索キーワード</label>
            <input type="text" name="search_keywords" class="form-control" placeholder="楽天API検索キーワード" value="{{ $input['search_keywords'] ?? '' }}">
        </div>
        <!-- 有効フラグ -->
        <div class="col-12 col-md-2">
            <label for="inputenable" class="form-label">有効</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="enable_flag" id="inputenable" checked>
            </div>
        </div>
    </div>

    <div class="text-end mb-3">
        <input type="submit" value="カテゴリ登録" class="btn btn-primary">
    </div>

</form>

{{--メッセージ--}}
@if(isset($msg))
    <div class="alert alert-info">
        {!! nl2br(e($msg)) !!}
    </div>
@endif

{{--カテゴリ登録履歴（簡易表示）--}}
@if(isset($categories))
    <div style="overflow-x: auto;">
        <label class="form-label">最近追加されたカテゴリ</label>
        <table class="table table-striped table-hover table-bordered fs-6 ">
            <thead>
            <tr>
                <th scope="col" class="fw-light">カテゴリ名</th>
                <th scope="col" class="fw-light">キーワード</th>
                <th scope="col" class="fw-light">状態</th>
            </tr>
            </thead>
            <tbody>
            @foreach($categories->take(5) as $cat)
                <tr>
                    <td class="fw-light">{{$cat->name}}</td>
                    <td class="fw-light">{{$cat->search_keywords}}</td>
                    <td class="fw-light">{{$cat->enable_flag ? '有効' : '無効'}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
