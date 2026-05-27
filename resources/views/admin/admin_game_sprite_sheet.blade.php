<div class="row">
    <div class="col-md-3">
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">画像一覧</h5>
                <span class="badge bg-secondary">public/img/sprite_sheet</span>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @forelse($images as $img)
                        <a href="{{ route('admin.game.sprite_sheet', ['file' => $img, 'mode' => request('mode', 'atlas')]) }}" 
                           class="list-group-item list-group-item-action d-flex align-items-center justify-content-between {{ $activeFile === $img ? 'active' : '' }}">
                            <div class="text-truncate me-2">
                                <i class="bi bi-image me-1"></i>
                                <code>{{ $img }}</code>
                            </div>
                            <img src="{{ asset('img/sprite_sheet/' . $img) }}" style="max-height: 40px; width: auto; max-width: 60px;" class="border rounded bg-light">
                        </a>
                    @empty
                        <div class="text-center py-4 text-muted">PNG画像が見つかりません。</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($activeFile && request('mode') === 'motion')
            <div class="card mb-3 shadow-sm animate__animated animate__fadeIn">
                <div class="card-header bg-primary text-white small fw-bold">
                    <i class="bi bi-plus-circle-fill me-1"></i> 1. 新規パーツを追加
                </div>
                <div class="card-body bg-light p-2" id="motion-palette-list" style="max-height: 250px; overflow-y: auto;">
                    </div>
            </div>

            <div class="card mb-4 shadow-sm animate__animated animate__fadeIn">
                <div class="card-header bg-success text-white small fw-bold">
                    <i class="bi bi-diagram-3-fill me-1"></i> 2. 配置済みの部位 (選択・削除)
                </div>
                <div class="card-body bg-light p-2" id="motion-used-list" style="max-height: 250px; overflow-y: auto;">
                    </div>
            </div>
        @endif
    </div>

    <div class="col-md-9">
        @if($activeFile)
            <div class="d-flex gap-2 mb-3 bg-white p-2 rounded border shadow-sm">
                <a href="{{ route('admin.game.sprite_sheet', ['file' => $activeFile, 'mode' => 'atlas']) }}" 
                   class="btn btn-sm fw-bold {{ request('mode', 'atlas') === 'atlas' ? 'btn-primary' : 'btn-light text-secondary' }} flex-fill py-2">
                    <i class="bi bi-crop me-1"></i> ① 切り出し座標設定 (_atlas.json)
                </a>
                <a href="{{ route('admin.game.sprite_sheet', ['file' => $activeFile, 'mode' => 'motion']) }}" 
                   class="btn btn-sm fw-bold {{ request('mode') === 'motion' ? 'btn-success text-white' : 'btn-light text-secondary' }} flex-fill py-2">
                    <i class="bi bi-animation me-1"></i> ② 配置＆モーション設定 (_motion.json)
                </a>
            </div>

            @if(request('mode', 'atlas') === 'atlas')
                @include('admin.game.sprite_sheet_atlas')
            @else
                @include('admin.game.sprite_sheet_motion')
            @endif
        @else
            <div class="card py-5 text-center text-muted border-dashed">
                <div class="fs-1"><i class="bi bi-arrow-left-circle"></i></div>
                <p class="mt-2">左側の一覧から、編集する画像ファイルを選択してください。</p>
            </div>
        @endif
    </div>
</div>