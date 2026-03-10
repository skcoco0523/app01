
{{-- 広告情報更新処理 --}}
    <form id="adv_change_form" method="POST" action="{{ route('admin.adv.update') }}">
        @csrf
        {{--検索条件--}}
        <input type="hidden" name="search_name"         value="{{$input['search_name'] ?? ''}}">
        <input type="hidden" name="search_type"         value="{{$input['search_type'] ?? ''}}">
        <input type="hidden" name="search_click_cnt_s"  value="{{$input['search_click_cnt_s'] ?? ''}}">
        <input type="hidden" name="search_click_cnt_e"  value="{{$input['search_click_cnt_e'] ?? ''}}">
        <input type="hidden" name="search_month"        value="{{$input['search_month'] ?? ''}}">
        <input type="hidden" name="search_day"          value="{{$input['search_day'] ?? ''}}">
        <input type="hidden" name="search_days"         value="{{$input['search_days'] ?? ''}}">
        <input type="hidden" name="search_age"          value="{{$input['search_age'] ?? ''}}">
        <input type="hidden" name="search_gender"       value="{{$input['search_gender'] ?? ''}}">
        <input type="hidden" name="search_disp_flag"    value="{{$input['search_disp_flag'] ?? ''}}">
        <input type="hidden" name="page"                value="{{request()->input('page') ?? $input['page'] ?? '' }}">
        {{--対象データ--}}
        <input type="hidden" id="id" name="id" value="{{$select->id ?? ($input['id'] ?? '')}}">
        <input type="hidden" name="aff_id" value="{{$select->aff_id ?? ($input['aff_id'] ?? '')}}">

        <div class="row g-3 align-items-stretch mb-3">
            <!-- 広告名 -->
            <div class="col-6 col-md-2">
                <label for="inputname" class="form-label">広告名</label>
                <input type="text" name="name" class="form-control" placeholder="name" value="{{$select->name ?? ''}}">
            </div>
            <!-- タイプ -->
            <div class="col-6 col-md-2">
                <label for="inputtype" class="form-label">タイプ</label>
                <select id="inputtype" name="type" class="form-control">
                    @foreach($type_list as $type)
                        <option value="{{ $type }}" {{ ($select->type ?? '') == $type ? 'selected' : '' }}>{{$type}}</option>
                    @endforeach
                </select>
            </div>
            <!-- 掲載期間 -->
            <div class="col-4 col-md-2">
                <label for="inputdate" class="form-label">掲載期間(月)</label>
                <select name="month" class="form-control">
                    <option value="">指定なし</option>
                    @for ($i = 1; $i <= 12; $i++) <option value="{{ $i }}">{{ $i }}月</option> @endfor
                </select>
            </div>
            <div class="col-4 col-md-2">
                <label for="inputdate" class="form-label">掲載期間(日)</label>
                <select name="day" class="form-control me-3">
                    <option value="">指定なし</option>
                    @for ($i = 1; $i <= 31; $i++) <option value="{{ $i }}">{{ $i }}日</option> @endfor
                </select>
            </div>
            <div class="col-4 col-md-2">
                <label for="inputdate" class="form-label">掲載期間(日数)</label>
                <select name="days" class="form-control">
                    <option value="">指定なし</option>
                    @for ($i = 1; $i <= 99; $i++) <option value="{{ $i }}">{{ $i }}日間</option> @endfor
                </select>
            </div>

            <div class="col-0 col-md-2">
            </div>


            <!-- 対象年代 -->
            <div class="col-4 col-md-2">
                <label for="inputage" class="form-label">対象年代</label>
                <select id="inputage" name="age" class="form-control">
                    <option value="">指定なし</option>
                    @for ($i = 10; $i <= 90; $i+=10) <option value="{{ $i }}">{{ $i }}代</option> @endfor
                </select>
            </div>
            <!-- 対象性別 -->
            <div class="col-4 col-md-2">
                <label for="inputgender" class="form-label">対象性別</label>
                <select id="inputgender" name="gender" class="form-control">
                    <option value="">指定なし</option>
                    <option value="0">男性</option>
                    <option value="1">女性</option>
                </select>
            </div>
            <!-- 優先度 -->
            <div class="col-4 col-md-2">
                <label for="inputpriority" class="form-label">優先度(昇順)</label>
                <select id="inputpriority" name="priority" class="form-control">
                    @for ($i = 1; $i <= 100; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                </select>
            </div>
            <!-- 公開フラグ -->
            <div class="col-4 col-md-2">
                <label for="inputdisp_flag" class="form-label">表示状態</label>
                <select id="inputdisp_flag" name="disp_flag" class="form-control">
                    <option value="0">非表示</option>
                    <option value="1">表示</option>
                </select>
            </div>

        </div>

        <div class="row g-3 align-items-stretch mb-3">
            <div class="col-12 col-md-6">
                <label for="inputmemo" class="form-label">メモ</label>
                <textarea class="form-control" name="memo">{{$input['memo'] ?? ''}}</textarea>
            </div>
            <div class="col-12 col-md-6">
                <label for="affiliate-link" class="form-label">リンク：</label>
                <a href="https://pub.a8.net/a8v2/media/memberAction.do" target="_blank" rel="noopener noreferrer" class="form-label me-3">A8.net</a>
                <a href="https://affiliate.rakuten.co.jp/?l-id=af_header_logo" target="_blank" rel="noopener noreferrer" class="form-label">楽天アフィリエイト</a>
                <textarea class="form-control" id="affiliate-link" name="aff_link">{{$input['aff_link'] ?? ''}}</textarea>
                
                <div class="form-group mt-3">
                    <label class="form-label">イメージ(上記リンクのイメージが表示)</label>
                </div>
                <div id="affiliate-preview">
                    {{--入力されたリンクが表示される部分--}}
                </div>
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

{{--広告一覧--}}
@if(isset($advertisement))
    {{--ﾊﾟﾗﾒｰﾀ--}}
    @php
        $page_prm = $input ?? '';
    @endphp
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $advertisement,'page_prm' => $page_prm,])
    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6 ">
            <thead>
            <tr>
                <th scope="col" class="fw-light">#</th>
                <th scope="col" class="fw-light">広告名</th>
                <th scope="col" class="fw-light">タイプ</th>
                <th scope="col" class="fw-light">クリック数</th>
                <th scope="col" class="fw-light">メモ</th>
                <th scope="col" class="fw-light">掲載開始日</th>
                <th scope="col" class="fw-light">掲載日数</th>
                <th scope="col" class="fw-light">対象年齢</th>
                <th scope="col" class="fw-light">対象性別</th>
                <th scope="col" class="fw-light">優先度</th>
                <th scope="col" class="fw-light">表示有無</th>
                <th scope="col" class="fw-light">ｲﾒｰｼﾞ&ﾘﾝｸ</th>
                <th scope="col" class="fw-light">ﾃﾞｰﾀ登録日</th>
                <th scope="col" class="fw-light">ﾃﾞｰﾀ更新日</th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
            </tr>
            </thead>
            @foreach($advertisement as $adv)
                <tr>
                    <td class="fw-light">{{$adv->id}}</td>
                    <td class="fw-light">{{$adv->name}}</td>
                    <td class="fw-light">{{$adv->type}}</td>
                    <td class="fw-light">{{$adv->click_cnt}}</td>
                    <td class="fw-light">{!! nl2br($adv->memo) !!}</td>
                    <td class="fw-light">{{$adv->sdate}}</td>
                    <td class="fw-light">{{$adv->days}}</td>
                    <td class="fw-light">{{$adv->age}}</td>
                    <td class="fw-light">{{$adv->gender === null ? '' : ($adv->gender === 0 ? '男性' : '女性') }}</td>
                    <td class="fw-light">{{$adv->priority}}</td>
                    <td class="fw-light">{{$adv->disp_flag === 0 ? '非表示' : '表示' }}</td>
                    <td class="fw-light">
                        <a class="" href="{{ $adv->href }}">
                            <img src="{{ $adv->src }}" style="max-height: 150px; max-width: 450px;" alt="adv_image">
                        </a>
                    </td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $adv->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $adv->updated_at) !!}</td>
                    <td class="fw-light">
                        <input type="button" value="編集" class="btn btn-primary edit-btn">
                    </td>
                    <td class="fw-light">
                        <form method="POST" action="{{ route('admin.adv.destroy') }}">
                            @csrf
                            {{--検索条件--}}
                            <input type="hidden" name="search_name" value="{{$input['search_name'] ?? ''}}">
                            <input type="hidden" name="search_type" value="{{$input['search_type'] ?? ''}}">
                            <input type="hidden" name="search_adv" value="{{$input['search_adv'] ?? ''}}">
                            <input type="hidden" name="search_adv" value="{{$input['search_adv'] ?? ''}}">

                            <input type="hidden" name="page" value="{{request()->input('page') ?? $input['page'] ?? '' }}">
                            {{--対象データ--}}
                            <input type="hidden" name="id" value="{{$adv->id}}">
                            <input type="hidden" name="aff_id" value="{{$adv->aff_id}}">
                            <input type="submit" value="削除" class="btn btn-danger" onclick="alb_detail_fnc(this.form);">
                        </form>
                    </td>
                </tr>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $advertisement,'page_prm' => $page_prm,])
@endif

<script>

function alb_detail_fnc(form) {
    var rtn = confirm('削除してもよろしいですか？');
    if (rtn === true) {
        form.submit(); // OKを押した場合、フォームを送信
    }
}
    
document.addEventListener('DOMContentLoaded', function () {

    var affiliateLinkInput = document.getElementById('affiliate-link');
    var affiliatePreview = document.getElementById('affiliate-preview');

    const form = document.getElementById('adv_change_form');
    //更新フォームを非表示
    form.style.display = 'none';

    affiliateLinkInput.addEventListener('input', function () {
        var link = affiliateLinkInput.value.trim();
        if (link !== '') {
            // 入力されたリンクをそのまま表示する
            affiliatePreview.innerHTML = link;
        } else {
            // クリア
            affiliatePreview.innerHTML = '';
        }
    });

    // 各行の編集ボタンにイベントリスナーを追加
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            // フォームを表示
            form.style.display = 'block';

            // ボタンの親要素（行）を取得
            const row       = this.closest('tr');
            const cells     = row.querySelectorAll('td');

            const id        = cells[0].textContent;
            const name      = cells[1].textContent;
            const type      = cells[2].textContent;
            const memo      = cells[4].innerHTML.replace(/<br\s*\/?>/gi, "\n");
            const sdate     = cells[5].textContent;
            const month     = parseInt(sdate.substring(0, 2), 10); // 最初の2文字（MM）を整数にキャスト
            const day       = parseInt(sdate.substring(3, 5), 10);   // 後ろの2文字（DD）を整数にキャスト

            const days      = cells[6].textContent;
            const age       = cells[7].textContent;
            const gender    = cells[8].textContent.trim();
            const priority  = cells[9].textContent;
            const disp_flag = cells[10].textContent.trim();


            const aff_id_input = row.querySelector('input[name="aff_id"]');
            const aff_id_value = aff_id_input.value;

            // フォームの対応するフィールドにデータを設定
            document.querySelector('input[name="id"]').value        = id;
            document.querySelector('input[name="name"]').value      = name;
            document.querySelector('select[name="type"]').value     = type;
            document.querySelector('select[name="month"]').value    = month;
            document.querySelector('select[name="day"]').value      = day;
            document.querySelector('select[name="days"]').value     = days;
            document.querySelector('select[name="age"]').value      = age;
            document.querySelector('select[name="priority"]').value = priority;

            const selectGender = document.querySelector('select[name="gender"]');
            if (gender === '男性')          selectGender.value = '0';
            else if (gender === '女性')     selectGender.value = '1';
            else selectGender.value = '';   //nullありのためクリア

            const selectDispflag = document.querySelector('select[name="disp_flag"]');
            if (disp_flag === '非表示')         selectDispflag.value = '0';
            else if (disp_flag === '表示')      selectDispflag.value = '1';

            document.querySelector('textarea[name="memo"]').value = memo;
            document.querySelector('input[name="aff_id"]').value = aff_id_value; // aff_idの値を設定
        });
    });

});
</script>

