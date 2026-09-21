{{-- リモコン情報更新処理 --}}
<form id="remoteblade_chg_form" method="POST" action="{{ route('admin.virtualremote.blade.update') }}">
    @csrf
    <div class="row g-3 align-items-stretch mb-3">
        {{-- 検索条件 --}}
        <input type="hidden" name="search_kind" value="{{ $input['search_kind'] ?? '' }}">
        <input type="hidden" name="search_name" value="{{ $input['search_name'] ?? '' }}">
        <input type="hidden" name="search_test_flag" value="{{ $input['search_test_flag'] ?? '' }}">
        <input type="hidden" name="page" value="{{ request()->input('page') ?? $input['page'] ?? '' }}">
        {{-- 対象データ --}}
        <input type="hidden" name="id" value="{{ $select->id ?? '' }}">

        <div class="col-6 col-md-3">
            <label class="form-label">種別</label>
            <select name="remote_kind" class="form-control">
                <option value="" {{ ($input['remote_kind'] ?? '') == '' ? 'selected' : '' }}></option>
                @foreach (config('common.virtual_remote') as $id =>$item)
                    <option value="{{ $id }}" {{ ($input['remote_kind'] ?? '') == (string)$id ? 'selected' : '' }}>
                        {{ $item['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div class="col-6 col-md-3">
            <label for="blade_name" class="form-label">ファイル名</label>
            <input type="text" name="blade_name" class="form-control" placeholder="XXX.blade" value="{{ $select->blade_name ?? ($input['blade_name'] ?? '') }}">
        </div>

        <div class="col-6 col-md-3">
            <label for="library_flag" class="form-label">送信タイプ</label>
            <select name="library_flag" class="form-control">
                <option value="0" {{ ($input['library_flag'] ?? 0) == 0 ? 'selected' : '' }}>学習型 (RAW)</option>
                <option value="1" {{ ($input['library_flag'] ?? 0) == 1 ? 'selected' : '' }}>ライブラリ型</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="protocol" class="form-label">プロトコル名</label>
            <input type="text" name="protocol" class="form-control" placeholder="例: PANASONIC_AC" value="{{ $select->protocol ?? ($input['protocol'] ?? '') }}">
        </div>

        <div class="col-6 col-md-3">
            <label for="test_flag" class="form-label">テストフラグ</label>
            <select name="test_flag" class="form-control">
                <option value="0" {{ ($input['test_flag'] ?? '') == 0 ? 'selected' : '' }}>本番</option>
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

{{--リモコン一覧--}}
@if(isset($virtualremoteblade_list))
    @php
        $page_prm =$input ?? '';
    @endphp

    @include('admin.layouts.pagination', ['paginator' => $virtualremoteblade_list, 'page_prm' =>$page_prm])
    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6">
            <thead>
            <tr>
                <th scope="col" class="fw-light">#</th>
                <th scope="col" class="fw-light">種別</th>
                <th scope="col" class="fw-light">ファイル名</th>
                <th scope="col" class="fw-light">送信タイプ</th>
                <th scope="col" class="fw-light">プロトコル</th>
                <th scope="col" class="fw-light">テストフラグ</th>
                <th scope="col" class="fw-light">データ登録日</th>
                <th scope="col" class="fw-light">データ更新日</th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
                <th scope="col" class="fw-light"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($virtualremoteblade_list as $blade)
                <tr>
                    <td class="fw-light">{{ $blade->id }}</td>
                    {{-- ★修正1: data-kind 属性を追加して ID を保持 --}}
                    <td class="fw-light" data-kind="{{ $blade->kind }}">
                        @php
                            $kind_name = config('common.virtual_remote')[$blade->kind]['name'] ?? '未登録の種別';
                        @endphp
                        {{ $kind_name }}
                    </td>
                    <td class="fw-light">{{ $blade->blade_name }}</td>
                    <td class="fw-light" data-library-flag="{{ $blade->library_flag }}">
                        <span class="badge {{ $blade->library_flag ? 'bg-info' : 'bg-secondary' }}">
                            {{ $blade->library_flag ? 'ライブラリ型' : '学習型(RAW)' }}
                        </span>
                    </td>
                    <td class="fw-light" data-protocol="{{ $blade->protocol }}">
                        {{ $blade->protocol ?? '-' }}
                    </td>
                    <td class="fw-light" data-test-flag="{{ $blade->test_flag }}">
                        {{ $blade->test_flag ? 'テスト' : '本番' }}
                    </td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $blade->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $blade->updated_at) !!}</td>
                    <td class="fw-light">
                        <input type="button" value="編集" class="btn btn-primary edit-btn">
                    </td>
                    <td class="fw-light">
                        <form method="POST" action="{{ route('admin.virtualremote.blade.destroy') }}">
                            @csrf
                            <input type="hidden" name="search_kind" value="{{ $input['search_kind'] ?? '' }}">
                            <input type="hidden" name="search_name" value="{{ $input['search_name'] ?? '' }}">
                            <input type="hidden" name="search_test_flag" value="{{ $input['search_test_flag'] ?? '' }}">
                            <input type="hidden" name="page" value="{{ request()->input('page') ?? $input['page'] ?? '' }}">
                            <input type="hidden" name="id" value="{{ $blade->id }}">
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
    @include('admin.layouts.pagination', ['paginator' => $virtualremoteblade_list, 'page_prm' =>$page_prm])
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('remoteblade_chg_form');
        form.style.display = 'none';

        // 各行の編集ボタンにイベントリスナーを追加
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                form.style.display = 'block';

                const row        = this.closest('tr');
                const cells      = row.querySelectorAll('td');
                
                const id         = cells[0].textContent.trim();
                // ★修正2: data-kind 属性から ID (0, 1...) を直接取得
                const remote_kind = cells[1].dataset.kind ?? '';
                const blade_name = cells[2].textContent.trim();
                
                // data属性経由で数値フラグ等を安全に取得
                const library_flag = cells[3].dataset.libraryFlag ?? 0;
                const protocol     = cells[4].dataset.protocol ?? '';
                const test_flag    = cells[5].dataset.testFlag ?? 0;

                // フォームの対応フィールドへセット
                form.querySelector('input[name="id"]').value            = id;
                form.querySelector('select[name="remote_kind"]').value  = remote_kind;
                form.querySelector('input[name="blade_name"]').value    = blade_name;
                form.querySelector('select[name="library_flag"]').value = library_flag;
                form.querySelector('input[name="protocol"]').value      = protocol;
                form.querySelector('select[name="test_flag"]').value    = test_flag;
            });
        });
    });

    function openRemotePreviewWindow(remotebladeId) {
        const url = `{{ route('admin.virtualremote.blade.preview') }}?remoteblade_id=${remotebladeId}`;
        window.open(url, 'RemotePreviewWindow', 'width=400,height=600,scrollbars=yes,resizable=yes');
    }
</script>