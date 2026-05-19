<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">広告共通設定</h5>
    </div>
    <div class="card-body">
        @if(isset($msg))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $msg }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr class="row g-0">
                        <th class="col-4">設定名</th>
                        <th class="col-3">ﾊﾟﾗﾒｰﾀ</th>
                        <th class="col-1">タイプ</th>
                        <th class="col-1">値1</th>
                        <th class="col-1">値2</th>
                        <th class="col-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($configs as $config)
                        <tr class="row g-0">
                            <td class="col-4 small text-muted">{{ $config->description }}</td>
                            <td class="col-3"><code>{{ $config->config_name }}</code></td>
                            <td class="col-1 small text-muted">{{ $config->type }}</td>

                            <td class="col-3 p-0">
                                <form action="{{ route('admin.adv.config.update') }}" method="POST" class="row g-0 h-100">
                                    @csrf
                                    <input type="hidden" name="config_name" value="{{ $config->config_name }}">

                                    @if($config->type == 'int' || $config->type == 'range')
                                        <div class="col-4 p-1"><input type="number" inputmode="numeric" min="-99999" max="99999" name="value1" value="{{ $config->value1 }}" class="form-control form-control-sm"></div>
                                        <div class="col-4 p-1"><input type="number" inputmode="numeric" min="-99999" max="99999" name="value2" value="{{ $config->value2 }}" class="form-control form-control-sm"></div>
                                    @elseif($config->type == 'string')
                                        <div class="col-4 p-1"><input type="text" name="value1" value="{{ $config->value1 }}" class="form-control form-control-sm"></div>
                                        <div class="col-4 p-1"><input type="text" name="value2" value="{{ $config->value2 }}" class="form-control form-control-sm"></div>
                                    @elseif($config->type == 'bool')
                                        <div class="col-4 p-1">
                                            <input type="hidden" name="value1" value="0">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="value1_{{ $config->config_name }}" name="value1" value="1" {{ $config->value1 ? 'checked' : '' }}>
                                                <label class="form-check-label" for="value1_{{ $config->config_name }}">有効</label>
                                            </div>
                                        </div>
                                        <div class="col-4 p-1"></div>
                                    @endif

                                    <div class="col-4 p-1">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">保存</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>