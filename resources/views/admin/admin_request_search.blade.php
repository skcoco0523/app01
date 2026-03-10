
{{-- ユーザーリクエスト情報更新処理 --}}
<form id="user_chg_form" method="POST" action="{{ route('admin.user.request.update') }}">
    @csrf
    {{--検索条件--}}
    <input type="hidden" name="search_type"     value="{{$input['search_type'] ?? ''}}">
    <input type="hidden" name="search_status"   value="{{$input['search_status'] ?? ''}}">
    <input type="hidden" name="search_mess"     value="{{$input['search_mess'] ?? ''}}">
    <input type="hidden" name="search_reply"    value="{{$input['search_reply'] ?? ''}}">
    <input type="hidden" name="page"            value="{{request()->input('page') ?? $input['page'] ?? '' }}">
    {{--対象データ--}}
    <input type="hidden" name="id"              value="{{$select->id ?? ''}}">
    
    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-sm-6 col-lg-4">
            <label for="inputname" class="form-label">ﾕｰｻﾞｰ名</label>
            <input type="text" name="name" class="form-control" value="{{$select->name ?? ''}}" style="background-color: #f0f0f0; pointer-events: none;">
        </div>
        <div class="col-sm-6 col-lg-4">
            <label for="inputtype" class="form-label">種別</label>
            <input type="text" name="type" class="form-control" value="{{$select->type ?? ''}}" style="background-color: #f0f0f0; pointer-events: none;">

        </div>
        <div class="col-sm-6 col-lg-4">
            <label for="inputstatus" class="form-label">ステータス</label>
            <select name="status" class="form-select">
                <option value="0" {{ ($select->status ?? '') == '0' ? 'selected' : '' }}>未対応</option>
                <option value="1" {{ ($select->status ?? '') == '1' ? 'selected' : '' }}>対応済</option>
            </select>
        </div>
    </div>

    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-6">
            <label for="inputmessage" class="form-label">依頼内容</label>
            <textarea class="form-control" name="message" style="background-color: #f0f0f0; pointer-events: none;"></textarea>
        </div>
        <div class="col-6">
            <label for="inputreply" class="form-label">回答内容</label>
            <textarea class="form-control" name="reply">{{$select->reply ?? ''}}</textarea>
        </div>
    </div>
    <div class="text-end mb-3 d-flex justify-content-end align-items-center">
        <div class="form-check me-3">
            <input class="form-check-input" type="checkbox" name="notification_flag" value="1">
            <label class="form-check-label" for="notification_flag">
                {{ __('notify user') }}
            </label>
        </div>
        <input type="submit" value="更新" class="btn btn-primary">
    </div>
    
</form>

{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif


{{--リクエスト一覧--}}
@if(isset($user_request))
    {{--ﾊﾟﾗﾒｰﾀ--}}
    @php
        $page_prm = $input ?? '';
    @endphp
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $user_request,'page_prm' => $page_prm,])
    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6">
            <thead>
            <tr>
                <th scope="col" class="fw-light">#</th>
                <th scope="col" class="fw-light">ﾕｰｻﾞｰ名</th>
                <th scope="col" class="fw-light">種別</th>
                <th scope="col" class="fw-light">ステータス</th>
                <th scope="col" class="fw-light">依頼内容</th>
                <th scope="col" class="fw-light">回答内容</th>
                <th scope="col" class="fw-light">データ登録日</th>
                <th scope="col" class="fw-light">データ更新日</th>
            </tr>
            </thead>
            @foreach($user_request as $request)
                <tr>
                    <td class="fw-light">{{$request->id}}</td>
                    <td class="fw-light">{{$request->name}}</td>
                    <td class="fw-light">{{$request->type == '0' ? '要望' : '問い合わせ' }}</td>
                    <td class="fw-light">{{$request->status == '0' ? '未対応' : '対応済' }}</td>
                    <td class="fw-light">{!! nl2br($request->message) !!}</td>
                    <td class="fw-light">{!! nl2br($request->reply) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $request->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $request->updated_at) !!}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $user_request,'page_prm' => $page_prm,])

@endif



<script>
document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('user_chg_form');
    //更新フォームを非表示
    form.style.display = 'none';

    // テーブルの各行にクリックイベントリスナーを追加
    document.querySelectorAll('table tr').forEach(row => {
        row.addEventListener('click', () => {
            //更新フォームを表示
            form.style.display = 'block';
            // クリックされた行からデータを取得
            const cells         = row.querySelectorAll('td');
            const id            = cells[0].textContent;
            const name          = cells[1].textContent;
            const type          = cells[2].textContent;
            //textareaは加工して引き渡す
            const message       = cells[4].innerHTML.replace(/<br\s*\/?>/gi, "\n");
            const reply         = cells[5].innerHTML.replace(/<br\s*\/?>/gi, "\n");
            
            // 種別の選択肢を設定
            //const type = document.querySelector('select[name="type"]');
            //type.value = (cells[2].textContent.trim() === '要望') ? '0' : '1';

            // ステータスの選択肢を設定
            const status = document.querySelector('select[name="status"]');
            status.value = (cells[3].textContent.trim() === '未対応') ? '0' : '1';

            // フォームの対応するフィールドにデータを設定
            document.querySelector('input[name="id"]').value        = id;
            document.querySelector('input[name="name"]').value      = name;
            document.querySelector('input[name="type"]').value      = type;
            document.querySelector('textarea[name="message"]').value   = message;
            document.querySelector('textarea[name="reply"]').value     = reply;

        });
    });
});
</script>