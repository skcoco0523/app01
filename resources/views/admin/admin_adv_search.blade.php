
{{-- 広告カテゴリ更新処理 --}}
<form id="adv_change_form" method="POST" action="{{ route('admin.adv.update') }}">
    @csrf
    {{--検索条件--}}
    <input type="hidden" name="search_name" value="{{$input['name'] ?? ''}}">
    <input type="hidden" name="page" value="{{request()->input('page') ?? $input['page'] ?? '' }}">
    {{--対象データ--}}
    <input type="hidden" id="id" name="id" value="{{$category->id ?? ''}}">

    <div class="row g-3 align-items-stretch mb-3">
        <!-- カテゴリ名 -->
        <div class="col-6 col-md-4">
            <label for="inputname" class="form-label">カテゴリ名</label>
            <input type="text" name="name" class="form-control" placeholder="カテゴリ名" value="{{$category->name ?? ''}}">
        </div>
        <!-- 検索キーワード -->
        <div class="col-6 col-md-4">
            <label for="inputkeywords" class="form-label">検索キーワード</label>
            <input type="text" name="search_keywords" class="form-control" placeholder="キーワード" value="{{$category->search_keywords ?? ''}}">
        </div>
        <!-- 有効フラグ -->
        <div class="col-6 col-md-2">
            <label for="inputenable" class="form-label">有効/無効</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="enable_flag" id="inputenable" {{ ($category->enable_flag ?? 1) ? 'checked' : '' }}>
            </div>
        </div>
        <div class="col-6 col-md-2 d-flex align-items-end">
            <input type="submit" value="更新" class="btn btn-primary w-100">
        </div>
    </div>
</form>

{{--メッセージ--}}
@if(isset($msg))
    <div class="alert alert-info">
        {!! nl2br(e($msg)) !!}
    </div>
@endif

{{--カテゴリ一覧--}}
@if(isset($categories))
    {{--ﾊﾟﾗﾒｰﾀ--}}
    @php
        $page_prm = $input ?? '';
    @endphp
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $categories,'page_prm' => $page_prm,])
    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6 ">
            <thead>
            <tr>
                <th scope="col" class="fw-light">#</th>
                <th scope="col" class="fw-light">カテゴリ名</th>
                <th scope="col" class="fw-light">検索キーワード</th>
                <th scope="col" class="fw-light">状態</th>
                <th scope="col" class="fw-light">登録日</th>
                <th scope="col" class="fw-light">更新日</th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($categories as $cat)
                <tr>
                    <td class="fw-light">{{$cat->id}}</td>
                    <td class="fw-light">{{$cat->name}}</td>
                    <td class="fw-light">{{$cat->search_keywords}}</td>
                    <td class="fw-light">{{$cat->enable_flag ? '有効' : '無効' }}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $cat->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $cat->updated_at) !!}</td>
                    <td class="fw-light">
                        <input type="button" value="編集" class="btn btn-primary edit-btn">
                    </td>
                    <td class="fw-light">
                        <form method="POST" action="{{ route('admin.adv.destroy') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{$cat->id}}">
                            <input type="submit" value="削除" class="btn btn-danger" onclick="return confirm('削除してもよろしいですか？');">
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $categories,'page_prm' => $page_prm,])
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('adv_change_form');
    //更新フォームを非表示
    form.style.display = 'none';

    // 各行の編集ボタンにイベントリスナーを追加
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            // フォームを表示
            form.style.display = 'block';

            // ボタンの親要素（行）を取得
            const row       = this.closest('tr');
            const cells     = row.querySelectorAll('td');

            const id        = cells[0].textContent;
            const name      = cells[1].textContent.trim();
            const keywords  = cells[2].textContent.trim();
            const state     = cells[3].textContent.trim();

            // フォームの対応するフィールドにデータを設定
            form.querySelector('input[name="id"]').value = id;
            form.querySelector('input[name="name"]').value = name;
            form.querySelector('input[name="search_keywords"]').value = keywords;
            form.querySelector('input[name="enable_flag"]').checked = (state === '有効');
        });
    });
});
</script>
