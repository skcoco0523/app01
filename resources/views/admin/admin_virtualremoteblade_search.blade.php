
{{-- 仮想リモートブレード情報更新処理 --}}
<form id="remoteblade_chg_form" method="POST" action="{{ route('admin.virtualremote.blade.update') }}">
    @csrf
    <div class="row g-3 align-items-stretch mb-3">
        {{--検索条件--}}
        <input type="hidden" name="search_kind" value="{{$input['search_kind'] ?? ''}}">
        <input type="hidden" name="search_name" value="{{$input['search_name'] ?? ''}}">
        <input type="hidden" name="search_test_flag" value="{{$input['search_test_flag'] ?? ''}}">
        <input type="hidden" name="page" value="{{request()->input('page') ?? $input['page'] ?? '' }}">
        {{--対象データ--}}
        <input type="hidden" name="id" value="{{$select->id ?? ''}}">

        <div class="col-6 col-md-4">
            種別
            <select name="remote_kind" class="form-control">
                <option value=""  {{ ($input['remote_kind'] ?? '') == ''  ? 'selected' : '' }}></option>
                @foreach (config('common.remote_kind') as $key => $value)
                    <option value="{{ $value }}" {{ ($input['remote_kind'] ?? '') == (string)$value ? 'selected' : '' }}>{{ $key }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="col-6 col-md-4">
            <label for="inputname" class="form-label">ファイル名</label>
            <input type="text" name="blade_name" class="form-control" placeholder="XXX.blade" value="{{ $select->blade_name ?? ($input['blade_name'] ?? '') }}">
        </div>

        <div class="col-6 col-md-4">
            <label for="inputtest_flag" class="form-label">テストフラグ</label>
            <select name="test_flag" class="form-control">
                <option value="0" {{ ($input['test_flag'] ?? '') == 0 ? 'selected' : '' }}></option>
                <option value="1" {{ ($input['test_flag'] ?? '') == 1 ? 'selected' : '' }}>テスト</option>
            </select>
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
@if(isset($virtualremoteblade_list))
    {{--ﾊﾟﾗﾒｰﾀ--}}
    @php
        $page_prm = $input ?? '';
    @endphp
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $virtualremoteblade_list,'page_prm' => $page_prm,])
    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6 ">
            <thead>
            <tr>
                <th scope="col" class="fw-light">#</th>
                <th scope="col" class="fw-light">種別</th>
                <th scope="col" class="fw-light">ファイル名</th>
                <th scope="col" class="fw-light">テストフラグ</th>
                <th scope="col" class="fw-light">データ登録日</th>
                <th scope="col" class="fw-light">データ更新日</th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
            </tr>
            </thead>
            @foreach($virtualremoteblade_list as $blade)
                <tr>
                    <td class="fw-light">{{$blade->id}}</td>
                    <td class="fw-light">
                        @php
                            $kind_name = '未登録の種別'; // デフォルト値
                            foreach (config('common.remote_kind') as $key => $value) {
                                if ($value === $blade->kind) { $kind_name = $key;}
                            }
                        @endphp
                        {{ $kind_name }}
                    </td>
                    <td class="fw-light">{{$blade->blade_name}}</td>
                    <td class="fw-light">{{$blade->test_flag}}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $blade->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $blade->updated_at) !!}</td>
                    <td class="fw-light">
                        <input type="button" value="編集" class="btn btn-primary edit-btn">
                    </td>
                    <td class="fw-light">
                        <form method="POST" action="{{ route('admin.virtualremote.blade.destroy') }}">
                            @csrf
                            {{--検索条件--}}
                            <input type="hidden" name="search_kind" value="{{$input['search_kind'] ?? ''}}">
                            <input type="hidden" name="search_name" value="{{$input['search_name'] ?? ''}}">
                            <input type="hidden" name="search_test_flag" value="{{$input['search_test_flag'] ?? ''}}">
                            <input type="hidden" name="page" value="{{request()->input('page') ?? $input['page'] ?? '' }}">
                            {{--対象データ--}}
                            <input type="hidden" name="id" value="{{$blade->id}}">
                            <input type="submit" value="削除" class="btn btn-danger">
                        </form>
                    </td>
                    <td class="fw-light">
                        <input type="button" value="ﾌﾟﾚﾋﾞｭｰ" class="btn btn-info preview-btn"
                            onclick="openRemotePreviewWindow('{{ $blade->id }}')">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $virtualremoteblade_list,'page_prm' => $page_prm,])
@endif

<script>

    document.addEventListener('DOMContentLoaded', function () {

        var affiliateLinkInput = document.getElementById('affiliate-link');
        var affiliatePreview = document.getElementById('affiliate-preview');
        const remoteKindMap = @json(config('common.remote_kind'));

        const form = document.getElementById('remoteblade_chg_form');
        //更新フォームを非表示
        form.style.display = 'none';

        // 各行の編集ボタンにイベントリスナーを追加
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                // フォームを表示
                form.style.display = 'block';

                // ボタンの親要素（行）を取得
                const row           = this.closest('tr');
                const cells         = row.querySelectorAll('td');
                
                const id            = cells[0].textContent.trim();
                const remote_kind   = cells[1].textContent.trim();
                const remote_kind_value = remoteKindMap[remote_kind] ?? '';
                const blade_name    = cells[2].textContent;
                const test_flag     = cells[3].textContent.trim();
                // フォームの対応するフィールドにデータを設定
                document.querySelector('input[name="id"]').value            = id;
                document.querySelector('select[name="remote_kind"]').value  = remote_kind_value;
                document.querySelector('input[name="blade_name"]').value    = blade_name;
                document.querySelector('select[name="test_flag"]').value    = test_flag;
            });
        });
        
        
    });

    function openRemotePreviewWindow(remotebladeId) {
        //if (!bladeName) {
            //ialert('Blade名が指定されていません。');
            //ireturn;
        //i}

        // プレビュー表示用のURLを生成
        const url = `{{ route('admin.virtualremote.blade.preview') }}?remoteblade_id=${remotebladeId}`;
        console.log(url);
        
        // 新しいウィンドウを開く
        // 'RemotePreviewWindow' はウィンドウ名。同じ名前のウィンドウがあればそこに開く。
        // 'width=400,height=600' はウィンドウのサイズ指定。
        window.open(url, 'RemotePreviewWindow', 'width=400,height=600,scrollbars=yes,resizable=yes');
    }

</script>