
{{-- IoTデバイス登録処理 --}}
<form id="mus_reg_form" method="POST" action="{{ route('admin.virtualremote.blade.store') }}">
    @csrf
    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-6 col-md-4">
            <label for="inputbirth" class="form-label">種別</label>
            <select name="remote_kind" class="form-control">
                <option value=""  {{ ($input['remote_kind'] ?? '') == ''  ? 'selected' : '' }}></option>
                @foreach (config('common.remote_kind') as $key => $value)
                    <option value="{{ $value }}" {{ ($input['remote_kind'] ?? '') == (string)$value ? 'selected' : '' }}>{{ $key }}</option>
                @endforeach
                
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label for="inputname" class="form-label">ファイル名</label>
            <input type="text" name="blade_name" class="form-control" placeholder="XXX.blade" value="{{$input['blade_name'] ?? '.blade'}}">
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

{{--デバイス登録履歴--}}
@if(isset($virtualremoteblade_list))
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
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    
});


</script>
