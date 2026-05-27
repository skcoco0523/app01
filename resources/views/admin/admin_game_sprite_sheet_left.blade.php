<div class="p-3">
    <form action="{{ route('admin.game.sprite_sheet') }}" method="GET">
        <h6 class="mb-3">フィルタ</h6>
        <div class="mb-3">
            <label class="form-label small">ファイル名検索</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="hero.png..." value="{{ request('search') }}">
        </div>
        <div class="mb-3">
            <label class="form-label small">タグ</label>
            <select name="tag" class="form-select form-select-sm">
                <option value="">全て</option>
                <option value="character" {{ request('tag') == 'character' ? 'selected' : '' }}>キャラクター</option>
                <option value="effect" {{ request('tag') == 'effect' ? 'selected' : '' }}>エフェクト</option>
                <option value="ui" {{ request('tag') == 'ui' ? 'selected' : '' }}>UI</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm btn-primary w-100 mb-3">検索</button>
        <hr>
        <div class="small text-muted">
            ※ JSONファイルは画像と同名で保存されている必要があります。
        </div>
    </form>
</div>