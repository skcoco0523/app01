{{-- 🗺️ マップ管理：検索・操作（左側） --}}
<div class="sticky-top" style="top: 20px;">
    
    {{-- 1. ゲーム選択 ＆ 検索 --}}
    <form method="GET" action="{{ route('admin.game.map.index') }}">
        <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-filter-square me-1"></i> 検索・絞り込み</h6>
        <div class="card shadow-sm mb-4 border-info">
            <div class="card-body bg-light p-3">
                <label class="form-label small fw-bold text-primary">対象のゲーム作品</label>
                <select name="game_key" class="form-select form-select-sm fw-bold border-info mb-3" onchange="this.form.submit()">
                    @foreach($games as $g)
                        <option value="{{ $g->game_key }}" {{ $gameKey == $g->game_key ? 'selected' : '' }}>
                            🎮 {{ $g->title }}
                        </option>
                    @endforeach
                </select>
                <div class="d-grid border-top pt-2">
                    <button type="submit" class="btn btn-sm btn-info text-white fw-bold">
                        <i class="bi bi-search me-1"></i> 表示を切り替え
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- 2. パブリッシュ --}}
    <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-cloud-upload me-1"></i> 公開反映</h6>
    <div class="card shadow-sm border-warning mb-4">
        <div class="card-body p-3 bg-light">
            <p class="small text-muted mb-3" style="font-size: 11px;">
                DBの最新設定を静的JSONとして書き出し、ゲームエンジンから読み込める状態にします。
            </p>
            <div class="d-grid">
                <a href="{{ route('admin.game.publish', ['gameKey' => $gameKey]) }}" 
                   class="btn btn-sm btn-warning fw-bold text-dark shadow-sm"
                   onclick="return confirm('現在DBに保存されている全データを公開用JSONとして上書き出力します。よろしいですか？');">
                    <i class="bi bi-megaphone-fill me-1"></i> 全データパブリッシュ
                </a>
            </div>
        </div>
    </div>

    <div class="alert alert-info py-2 px-3 small shadow-sm">
        <i class="bi bi-info-circle-fill me-1"></i> <b>マップ管理</b><br>
        右側のエディタで地形や背景レイヤーを構成し、保存してください。作成したマップはステージ管理から紐付けることができます。
    </div>
</div>
