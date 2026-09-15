<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">ポイント設定</h5>
    </div>
    <div class="card-body">
        @if(isset($msg))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $msg }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="container-fluid p-0">
    <!-- ヘッダー行 -->
    <div class="row g-2 fw-bold bg-light py-2 px-1 border-bottom align-items-center text-muted small">
        <div class="col-md-3">設定名</div>
        <div class="col-md-2">パラメータ</div>
        <div class="col-md-1">タイプ</div>
        @if($config_type=='pack')
            <div class="col-md-2">価格</div>
            <div class="col-md-2">付与pt</div>

        @elseif($config_type=='free')
            <div class="col-md-4">付与ptまたは実行フラグ</div>

        @elseif($config_type=='amount')
            <div class="col-md-2">必要pt</div>
            <div class="col-md-2">1日の無料回数</div>
        @endif
    </div>

    <!-- データ行 -->
    @foreach($configs as $config)
        <div class="row g-2 align-items-center border-bottom py-2 m-0">
            <form action="{{ route('admin.point.config.update') }}" method="POST" class="row g-2 align-items-center w-100 m-0 p-0">
                @csrf
                <!-- 識別用・送信用の hidden フィールドを追加 -->
                <input type="hidden" name="config_type" value="{{ $config_type }}">
                <input type="hidden" name="config_name" value="{{ $config->config_name }}">
                <input type="hidden" name="type" value="{{ $config->type }}">

                <div class="col-md-3">
                    <input type="text" name="description" value="{{ $config->description }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <code>{{ $config->config_name }}</code>
                </div>
                <div class="col-md-1 small text-muted">
                    {{ $config->type }}
                </div>

                <div class="col-md-4">
                    <div class="row g-1">
                        @if($config->type == 'int' || $config->type == 'range')
                            <div class="col-6">
                                <input type="number" inputmode="numeric" min="-99999" max="99999" name="value1" value="{{ $config->value1 }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <input type="number" inputmode="numeric" min="-99999" max="99999" name="value2" value="{{ $config->value2 }}" class="form-control form-control-sm">
                            </div>
                        @elseif($config->type == 'string')
                            <div class="col-6">
                                <input type="text" name="value1" value="{{ $config->value1 }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <input type="text" name="value2" value="{{ $config->value2 }}" class="form-control form-control-sm">
                            </div>
                        @elseif($config->type == 'bool')
                            <div class="col-12">
                                <input type="hidden" name="value1" value="0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="value1_{{ $config->config_name }}" name="value1" value="1" {{ $config->value1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="value1_{{ $config->config_name }}">有効</label>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">保存</button>
                </div>
            </form>
        </div>
    @endforeach
</div>
    </div>
</div>