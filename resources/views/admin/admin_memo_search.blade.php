{{-- メモ登録処理 --}}
<form id="memo_create_form" method="POST" action="{{ route('admin.memo.store') }}">
    @csrf
    {{--検索条件--}}
    <input type="hidden" name="search_title" value="{{$input['search_title'] ?? ''}}">
    <input type="hidden" name="search_content" value="{{$input['search_content'] ?? ''}}">
    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-12 col-md-6">
            <label for="inputname" class="form-label">タイトル</label>
            <input type="text" name="title" class="form-control" placeholder="title" value="{{$input['title'] ?? ''}}">
        </div>
        <div class="col-12 col-md-6">
            <label for="inputname" class="form-label">内容</label>
            <textarea name="content" class="form-control" placeholder="メモの内容" rows="5">{{$input['content'] ?? ''}}</textarea>
        </div>
    </div>

    <div class="text-end mb-3">
        <input type="submit" value="登録" class="btn btn-primary">
    </div>
    
</form>
{{-- メモ編集処理 --}}
<form id="memo_change_form" method="POST" action="{{ route('admin.memo.update') }}">
    @csrf
    {{--検索条件--}}
    <input type="hidden" name="search_title" value="{{$input['search_title'] ?? ''}}">
    <input type="hidden" name="search_content" value="{{$input['search_content'] ?? ''}}">
    {{--対象データ--}}
    <input type="hidden" name="id" value="{{$select->id ?? ''}}">
    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-12 col-md-6">
            <label for="inputname" class="form-label">タイトル</label>
            <input type="text" name="title" class="form-control" placeholder="title" value="{{$select->title ?? ''}}">
        </div>
        <div class="col-12 col-md-6">
            <label for="inputname" class="form-label">内容</label>
            <textarea name="content" class="form-control" placeholder="メモの内容" rows="5">{{$select->content ?? ''}}</textarea>
        </div>
    </div>

    <div class="text-end mb-3">
        <input type="submit" value="更新" class="btn btn-primary">
    </div>
    
</form>
{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif

{{--IoTデバイス一覧--}}
@if(isset($admin_memo_list))

    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6 ">
            <thead>
            <tr>
                <th scope="col" class="fw-light">タイトル</th>
                <th scope="col" class="fw-light">内容</th>
                <th scope="col" class="fw-light">更新日時</th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
            </tr>
            </thead>
            @foreach($admin_memo_list as $memo)
                    <tr
                        data-id="{{ $memo->id }}"
                        data-title="{{ $memo->title }}"
                        data-content="{{ $memo->content }}" {{-- contentも元の生データを渡す --}}
                    >
                    <td class="fw-light">{{$memo->title}}</td>
                    <td class="fw-light">{!! nl2br(e($memo->content)) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $memo->updated_at) !!}</td>
                    <td class="fw-light">
                        <input type="button" value="編集" class="btn btn-primary edit-btn">
                    </td>
                    <td class="fw-light">
                        <form method="POST" action="{{ route('admin.memo.destroy') }}">
                            @csrf
                            {{--検索条件--}}
                            <input type="hidden" name="search_title" value="{{$input['search_title'] ?? ''}}">
                            <input type="hidden" name="search_content" value="{{$input['search_content'] ?? ''}}">
                            {{--対象データ--}}
                            <input type="hidden" name="id" value="{{$memo->id}}">
                            <input type="submit" value="削除" class="btn btn-danger">
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    
@endif

<script>

    document.addEventListener('DOMContentLoaded', function () {

        const chg_form = document.getElementById('memo_change_form');
        //更新フォームを非表示
        chg_form.style.display = 'none';

        // 各行の編集ボタンにイベントリスナーを追加
        document.querySelectorAll('.edit-btn').forEach(button => {

            button.addEventListener('click', function () {
                
            
                const reg_form = document.getElementById('memo_create_form');
                //登録フォームを非表示
                reg_form.style.display = 'none';

                // フォームを表示
                chg_form.style.display = 'block';

                // ボタンの親要素（行）を取得
                const row = this.closest('tr');

                // --- ここが修正点 ---
                // データをdata属性から取得する
                const id = row.dataset.id;
                const title = row.dataset.title;
                const content = row.dataset.content;
                console.log(id);
                console.log(title);
                console.log(content);
                // フォームの対応するフィールドにデータを設定
                chg_form.querySelector('input[name="id"]').value            = id;
                chg_form.querySelector('input[name="title"]').value         = title;
                chg_form.querySelector('textarea[name="content"]').value   = content;
            });
        });
        
    });
</script>