{{-- OLEDパーツ管理 左メニュー --}}
<div class="mb-4">
    <h6>スプライトシート一覧</h6>
    <div class="list-group mb-3">
        @forelse($sheets ?? [] as $sheet)
            <a href="{{ route('admin.oled.parts.index', ['sheet_id' => $sheet->id]) }}" 
               class="list-group-item list-group-item-action py-1 px-2 small {{ (isset($selectedSheet) && $selectedSheet->id == $sheet->id) ? 'active' : '' }}">
                {{ $sheet->name }}
            </a>
        @empty
            <div class="text-muted small">シートがありません</div>
        @endforelse
    </div>

    <h6>新規シートアップロード</h6>
    <form action="{{ route('admin.oled.parts.upload_sheet') }}" method="POST" enctype="multipart/form-data" class="mb-4">
        @csrf
        <div class="mb-2">
            <label class="form-label small">シート名</label>
            <input type="text" name="name" class="form-control form-control-sm" required>
        </div>
        <div class="mb-2">
            <label class="form-label small">画像 (PNG/JPG)</label>
            <input type="file" name="image" class="form-control form-control-sm" accept="image/png, image/jpeg" required>
        </div>
        <button type="submit" class="btn btn-sm btn-primary w-100">アップロード</button>
    </form>

    {{-- OLED用素材画像（スプライトシート）作成の要点 --}}
    <div class="card bg-light border-secondary mb-3">
        <div class="card-body p-2" style="font-size: 11px;">
            <div class="fw-bold mb-1 text-dark">📌 素材画像作成の要点</div>
            <ul class="ps-3 mb-1 text-muted">
                <li><strong>ファイル形式</strong>: 透過PNG (背景完全透明)</li>
                <li><strong>配置・余白</strong>: パーツ間に2〜4pxの隙間</li>
                <li><strong>サイズ推奨</strong>: 8×8, 16×16, 32×32px 等</li>
                <li><strong>画質・デザイン</strong>: 白黒ドット</li>
            </ul>
        </div>
    </div>

    {{-- おすすめツール ＆ 設定値ガイド --}}
    <div class="card bg-light border-secondary">
        <div class="card-body p-2" style="font-size: 11px;">
            <div class="fw-bold mb-1 text-dark">おすすめ作成ツール</div>
            <a href="https://www.piskelapp.com/p/create/sprite/" target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-2" style="font-size: 11px;">
                🔗 Piskel (外部サイト) を開く
            </a>

            <div class="fw-bold mb-1 text-dark">⚙️ 作成時の推奨設定値</div>
            <ul class="ps-3 mb-2 text-muted">
                <li><strong>初期サイズ</strong>: 32×32px または 64×64px</li>
                <li><strong>背景</strong>: Transparent (透過)</li>
                <li><strong>エクスポート</strong>: PNG形式で書き出し</li>
            </ul>

            <div class="text-center bg-white p-1 border rounded">
                <span class="text-muted" style="font-size: 10px;">[piskelでの設定]</span>
                <img src="{{ asset('img/oled/piskel_setting.png') }}" class="img-fluid mt-1" alt="Piskel設定値サンプル">
            </div>
        </div>
    </div>
</div>


