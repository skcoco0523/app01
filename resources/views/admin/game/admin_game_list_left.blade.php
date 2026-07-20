{{-- ゲーム作品一覧 検索条件（左側） --}}
<form method="GET" action="{{ route('admin.game.index') }}">
    <h6 class="mb-3 fw-bold text-secondary"><i class="bi bi-search me-1"></i> 作品検索</h6>
    
    <div class="row g-3">

        <div class="col-12 pt-2">
            <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">
                <i class="bi bi-arrow-clockwise me-1"></i> 一覧を更新
            </button>
        </div>
    </div>
</form>