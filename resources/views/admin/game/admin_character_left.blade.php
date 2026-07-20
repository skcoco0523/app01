{{-- ゲーム・キャラクター検索条件（左側） --}}
<form method="GET" action="{{ route('admin.game.character.index') }}">
    <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-filter-square me-1"></i> 検索・絞り込み</h6>
    
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-bold text-primary">選択中のゲーム作品</label>
            <select name="game_key" class="form-select form-select-sm fw-bold border-primary">
                {{-- 🌟 コントローラーから届く $games を純粋にループするだけ --}}
                @forelse($games as $g)
                    <option value="{{ $g->game_key }}" {{ ($gameKey ?? '') === $g->game_key ? 'selected' : '' }}>
                        🎮 {{ $g->title }}
                    </option>
                @empty
                    <option value="">-- ゲーム作品が未登録です --</option>
                @endforelse
            </select>
        </div>

        <hr class="my-2 text-muted">

        <div class="col-12">
            <label class="form-label small">キャラクター名</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="名前で検索..." value="{{ request('search') }}">
        </div>

        <div class="col-12">
            <label class="form-label small">キャラクタータイプ</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">全種類</option>
                <option value="player" {{ request('type') === 'player' ? 'selected' : '' }}>プレイヤー (player)</option>
                <option value="enemy" {{ request('type') === 'enemy' ? 'selected' : '' }}>敵・モンスター (enemy)</option>
                <option value="npc" {{ request('type') === 'npc' ? 'selected' : '' }}>住民・NPC (npc)</option>
            </select>
        </div>

        <div class="col-12 pt-2">
            <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">
                <i class="bi bi-search me-1"></i> 条件で検索
            </button>
        </div>
    </div>
</form>