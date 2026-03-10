{{-- 広告登録処理 --}}
<form id="adv_reg_form" method="POST" action="{{ route('admin.adv.store') }}">
    @csrf

    <div class="row g-3 align-items-stretch mb-3">
        <!-- 広告名 -->
        <div class="col-6 col-md-2">
            <label for="inputname" class="form-label">広告名</label>
            <input type="text" name="name" class="form-control" placeholder="name" value="{{ $input['name'] ?? '' }}">
        </div>
        <!-- タイプ -->
        <div class="col-6 col-md-2">
            <label for="inputtype" class="form-label">タイプ</label>
            <select id="inputtype" name="type" class="form-control">
                @foreach($type_list as $type)
                    <option value="{{ $type }}" {{ ($input['type'] ?? '') == $type ? 'selected' : '' }}>{{$type}}</option>
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
        <div class="col-6 col-md-2">
            <label for="inputage" class="form-label">対象年代</label>
            <select id="inputage" name="age" class="form-control">
                <option value="">指定なし</option>
                @for ($i = 10; $i <= 90; $i+=10) <option value="{{ $i }}">{{ $i }}代</option> @endfor
            </select>
        </div>
        <!-- 対象性別 -->
        <div class="col-6 col-md-2">
            <label for="inputgender" class="form-label">対象性別</label>
            <select id="inputgender" name="gender" class="form-control">
                <option value="">指定なし</option>
                <option value="0">男性</option>
                <option value="1">女性</option>
            </select>
        </div>
        <!-- 優先度 -->
        <div class="col-6 col-md-2">
            <label for="inputpriority" class="form-label">優先度(昇順)</label>
            <select id="inputpriority" name="priority" class="form-control">
                @for ($i = 1; $i <= 100; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
            </select>
        </div>
        <!-- 公開フラグ -->
        <div class="col-6 col-md-2">
            <label for="inputdisp_flag" class="form-label">表示状態</label>
            <select id="inputdisp_flag" name="disp_flag" class="form-control">
                <option value="0">非公開</option>
                <option value="1">公開</option>
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
        <input type="submit" value="登録" class="btn btn-primary">
    </div>

</form>

{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif

{{--広告登録履歴--}}
@if(isset($advertisement))
    
    <div style="overflow-x: auto;">
        <label class="form-label">最近追加された広告</label>
        <table class="table table-striped table-hover table-bordered fs-6 ">
            <thead>
            <tr>
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
            </tr>
            </thead>
            @foreach($advertisement as $adv)
                <tr>
                    <td class="fw-light">{{$adv->name}}</td>
                    <td class="fw-light">{{$adv->type}}</td>
                    <td class="fw-light">{{$adv->click_cnt}}</td>
                    <td class="fw-light">{{$adv->memo}}</td>
                    <td class="fw-light">{{$adv->sdate}}</td>
                    <td class="fw-light">{{$adv->days}}</td>
                    <td class="fw-light">{{$adv->age}}</td>
                    <td class="fw-light">{{ $adv->gender === null ? '' : ($adv->gender === 0 ? '男性' : '女性') }}</td>
                    <td class="fw-light">{{$adv->priority}}</td>
                    <td class="fw-light">{{$adv->disp_flag === 0 ? '非表示' : '表示' }}</td>
                    <td class="fw-light">
                        <a class="" href="{{ $adv->href }}">
                            <img src="{{ $adv->src }}" style="max-height: 150px; max-width: 450px;" alt="adv_image">
                        </a>
                    </td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $adv->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $adv->updated_at) !!}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    var affiliateLinkInput = document.getElementById('affiliate-link');
    var affiliatePreview = document.getElementById('affiliate-preview');

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

});
    

</script>
