{{-- 📁 スプライトシート管理専用：画像検索・絞り込み（左側） --}}
@php
    $search_route = route('admin.game.sprite_sheet.index');
    if (($parts_mode ?? null) === 'pixel') $search_route = route('admin.game.pixel_parts.index');
    if (($parts_mode ?? null) === 'grid')  $search_route = route('admin.game.grid_parts.index');
@endphp
<form method="GET" action="{{ $search_route }}">
    <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-filter-square me-1"></i> スプライトシート検索</h6>
    
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-bold text-primary">カテゴリで絞り込み</label>
            <select name="tag" class="form-select form-select-sm font-monospace">
                <option value="">📁 全てのカテゴリを表示</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('tag') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label small">ファイル名で部分一致</label>
            <input type="text" name="search" class="form-control form-control-sm font-monospace" placeholder="hero.png など..." value="{{ request('search') }}">
        </div>

        <div class="col-12 pt-2">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                <i class="bi bi-search me-1"></i> 画像を検索
            </button>
            {{-- 🌟【修正】クリア時のジャンプ先ルート名も正しく修正 --}}
            <a href="{{ $search_route }}" class="btn btn-sm btn-outline-secondary w-100 fw-bold mt-2">
                条件リセット
            </a>
        </div>
    </div>
</form>