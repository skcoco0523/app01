<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="m-0">OLED パーツ切り出し・管理</h3>
        @if(isset($selectedSheet))
        <form action="{{ route('admin.oled.parts.destroy_sheet', $selectedSheet->id) }}" method="POST" onsubmit="return confirm('スプライトシートおよび関連パーツをすべて削除しますか？');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> シート削除</button>
        </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if(isset($selectedSheet))
    <div class="row g-2 m-0">
    {{-- 左側メイン領域 (パーツ一覧 ＋ キャンバス) --}}
    <div class="col-md-9">
        <div class="row g-2">
            
            {{-- 🌟 1. 登録済みパーツ一覧 --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-secondary h-100">
                    <div class="card-header bg-secondary text-white py-1 small fw-bold d-flex justify-content-between align-items-center">
                        <span>登録済みパーツ一覧</span>
                        <span class="badge bg-light text-dark">{{ count($parts ?? []) }} 件</span>
                    </div>
                    <div class="card-body p-0 overflow-auto" style="max-height: 600px; min-height: 500px;">
                        <div class="list-group list-group-flush" id="oled-parts-list">
                            @forelse($parts ?? [] as $p)
                            <div class="list-group-item list-group-item-action p-2 oled-part-trigger"
                                style="cursor:pointer;"
                                data-id="{{ $p['id'] ?? '' }}"
                                data-name="{{ $p['name'] ?? '' }}"
                                data-category="{{ $p['category'] ?? '' }}"
                                data-x="{{ $p['src_x'] ?? 0 }}"
                                data-y="{{ $p['src_y'] ?? 0 }}"
                                data-w="{{ $p['src_width'] ?? 16 }}"
                                data-h="{{ $p['src_height'] ?? 16 }}">
                                
                                <div class="d-flex align-items-center justify-content-between gap-1">
                                    {{-- 左側: サムネイル & パーツ情報 --}}
                                    <div class="d-flex align-items-center text-truncate" style="min-width: 0;">
                                        {{-- 🌟 サムネイル枠を 32px から 48px に拡大 --}}
                                        <div class="bg-dark border rounded me-2 flex-shrink-0 d-flex align-items-center justify-content-center text-secondary" style="width:48px; height:48px; overflow:hidden;">
                                            <canvas class="part-thumb-canvas" 
                                                width="{{ $p['src_width'] ?? 16 }}" 
                                                height="{{ $p['src_height'] ?? 16 }}" 
                                                data-x="{{ $p['src_x'] ?? 0 }}" 
                                                data-y="{{ $p['src_y'] ?? 0 }}" 
                                                data-w="{{ $p['src_width'] ?? 16 }}" 
                                                data-h="{{ $p['src_height'] ?? 16 }}" 
                                                style="width: 100%; height: 100%; object-fit: contain; image-rendering: pixelated;">
                                            </canvas>
                                        </div>
                                        <div class="text-truncate">
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <span class="badge bg-info text-dark" style="font-size: 11px;">{{ $p['category'] ?? 'other' }}</span>
                                                {{-- 🌟 パーツ名の制限幅を 90px -> 140px に広げ、文字サイズも拡大 --}}
                                                <strong class="text-truncate" style="max-width: 140px; font-size: 14px;">{{ $p['name'] ?? '' }}</strong>
                                            </div>
                                            {{-- 🌟 座標テキストの文字サイズを 11px -> 12px に拡大 --}}
                                            <div class="text-muted" style="font-size: 12px;">
                                                ({{ $p['src_x'] ?? 0 }}, {{ $p['src_y'] ?? 0 }}) {{ $p['src_width'] ?? 16 }}x{{ $p['src_height'] ?? 16 }}px
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 🌟 右側: ボタン群（折り返し防止 & フォントサイズ調整） --}}
                                    <div class="btn-group flex-shrink-0" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1 btn-edit-oled-part" style="font-size: 11px; border-top-right-radius: 0; border-bottom-right-radius: 0;" title="編集">
                                            <i class="bi bi-pencil"></i> 編集
                                        </button>
                                        <form action="{{ route('admin.oled.parts.destroy_part', ['sheetId' => $selectedSheet->id, 'partId' => $p['id']]) }}" method="POST" onsubmit="return confirm('削除しますか？');" class="d-inline-flex m-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" style="font-size: 11px; border-top-left-radius: 0; border-bottom-left-radius: 0;" title="削除">
                                                <i class="bi bi-trash"></i> 削除
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            </div>
                            @empty
                            <div class="p-3 text-center text-muted small">パーツが登録されていません</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🌟 2. スプライト表示領域（col-md-6 に調整） --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-secondary h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <span class="small font-monospace text-truncate">シート: {{ $selectedSheet->name }} (<span id="lbl-natural-size">?×?</span>px)</span>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                                <span class="btn btn-outline-light disabled text-white fw-bold py-0 small" id="lbl-zoom" style="opacity: 1; min-width: 50px; font-size:11px;">100%</span>
                                <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body bg-secondary p-0 text-center scroll-container" style="flex: 1; overflow: auto; position: relative; min-height: 350px;">
                        <div id="oled-canvas-container" class="d-inline-block m-3 shadow" style="position: relative; user-select: none; cursor: crosshair; background-color: #e5e5e5; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, #e5e5e5 25%, #e5e5e5 75%, #ccc 75%); background-size: 20px 20px; background-position: 0 0, 10px 10px; overflow: hidden; vertical-align: top;">
                            <img id="oled-target-img" src="{{ asset($selectedSheet->file_path) }}" class="d-block" style="pointer-events: none; max-width: none !important; min-width: max-content !important; position: relative; z-index: 1;">
                            <div id="oled-drag-selector" class="position-absolute border border-danger bg-danger bg-opacity-25 d-none" style="z-index: 2000; pointer-events: none;"></div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-muted small py-1" style="font-size: 11px;">
                        💡 マウスドラッグで範囲選択。枠線の移動・サイズ調整が可能です。
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- 右側: 登録・プレビューフォーム --}}
    <div class="col-md-3">
        <div class="card shadow-sm sticky-top" style="top: 10px;">
            <div class="card-header bg-primary text-white py-2">
                <h5 class="card-title m-0 small fw-bold" id="form-title">パーツ新規登録</h5>
            </div>
            <div class="card-body p-3">
                <form id="oled-oled-form" action="{{ route('admin.oled.parts.store_part') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="form-method-field" value="POST">
                    <input type="hidden" name="oled_sprite_sheet_id" value="{{ $selectedSheet->id }}">

                    <div class="mb-3">
                        <label class="form-label small mb-1 fw-bold">パーツ名</label>
                        <input type="text" name="name" id="part-name" class="form-control form-control-sm" required placeholder="例: 目(通常)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1 fw-bold">カテゴリ</label>
                        <select name="category" id="part-category" class="form-select form-select-sm">
                            <option value="eye">目 (eye)</option>
                            <option value="mouth">口 (mouth)</option>
                            <option value="brow">眉 (brow)</option>
                            <option value="accessory">飾り (accessory)</option>
                            <option value="other">その他 (other)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small mb-1">X 座標</label>
                            <input type="number" name="src_x" id="part-x" class="form-control form-control-sm text-center" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Y 座標</label>
                            <input type="number" name="src_y" id="part-y" class="form-control form-control-sm text-center" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">幅 (W)</label>
                            <input type="number" name="src_width" id="part-w" class="form-control form-control-sm text-center" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">高さ (H)</label>
                            <input type="number" name="src_height" id="part-h" class="form-control form-control-sm text-center" min="1" required>
                        </div>
                    </div>
                    
                    <input type="hidden" name="part_data" id="txt-atlas" value='@json($selectedSheet->part_data ?? [])'>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary fw-bold" id="btn-submit-part">更新する</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    @else
    <div class="alert alert-info mt-3">左メニューからスプライトシートを選択するかアップロードしてください。</div>
    @endif
</div>



<style>
/* 🌟 セレクタを強化して確実に適用 */
div.part-frame-overlay {
    position: absolute !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
    
    /* 🌟 GPU描画を強制し、描画スキップを防止 */
    transform: translateZ(0);
    backface-visibility: hidden;
    
    /* 縮小されても視認できるしっかりとした「面」としての描画 */
    background-color: rgba(0, 0, 255, 0.2) !important;
    
    /* 🌟 border ではなく box-shadow (inset) で確実に内側に線を描画 */
    border: none !important;
    box-shadow: 
        inset 0 0 0 2px #0000ff, 
        0 0 0 1px #ffffff,
        0 0 3px rgba(0, 0, 0, 0.5) !important;
        
    cursor: move;
    box-sizing: border-box !important;
    
    /* 🌟 重なり順を最前面に強制 */
    z-index: 1000 !important;
}

div.part-frame-overlay:hover {
    background-color: rgba(0, 255, 0, 0.3) !important;
    box-shadow: 
        inset 0 0 0 2px #00ff00, 
        0 0 0 1px #ffffff,
        0 0 2px rgba(0, 255, 0, 0.5) !important;
    z-index: 1100 !important;
}

div.part-frame-overlay.active-target {
    /* 選択時は緑枠に変更し、さらに強調 */
    background-color: rgba(0, 255, 0, 0.15) !important;
    box-shadow: 
        inset 0 0 0 2px #00ff00, 
        0 0 0 1px #000000,
        0 0 2px rgba(0, 255, 0, 0.8) !important;
    z-index: 1200 !important;
}

.custom-edge-handle { 
    position: absolute !important; 
    width: 5px !important; 
    height: 5px !important; 
    background-color: #ffffff !important; 
    border: 1px solid #198754 !important; 
    border-radius: 50% !important; 
    box-sizing: border-box !important; 
    display: none;
    z-index: 210 !important; /* ハンドルポチは最前面 */
}

.part-frame-overlay.active-target .custom-edge-handle { display: block !important; }

.handle-nw { top: -5px !important; left: -5px !important; cursor: nw-resize !important; }
.handle-ne { top: -5px !important; right: -5px !important; cursor: ne-resize !important; }
.handle-sw { bottom: -5px !important; left: -5px !important; cursor: sw-resize !important; }
.handle-se { bottom: -5px !important; right: -5px !important; cursor: se-resize !important; }
.handle-n  { top: -5px !important; left: calc(50% - 5px) !important; cursor: n-resize !important; }
.handle-s  { bottom: -5px !important; left: calc(50% - 5px) !important; cursor: s-resize !important; }
.handle-w  { top: calc(50% - 5px) !important; left: -5px !important; cursor: w-resize !important; }
.handle-e  { top: calc(50% - 5px) !important; right: -5px !important; cursor: e-resize !important; }
</style>

@if(isset($selectedSheet))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const txtAtlas = document.getElementById('oled-textarea');
    const inpName = document.getElementById('part-name');
    const inpcategory = document.getElementById('part-category');
    const inpX = document.getElementById('part-x'); const inpY = document.getElementById('part-y');
    const inpW = document.getElementById('part-w'); const inpH = document.getElementById('part-h');

    const canvasContainer = document.getElementById('oled-canvas-container');
    const targetImg = document.getElementById('oled-target-img');
    const dragSelector = document.getElementById('oled-drag-selector');
    const lblZoom = document.getElementById('lbl-zoom');
    
    let zoomLevel = 1.0;
    let mode = 'idle';
    let startMouseX = 0, startMouseY = 0;
    let initialBoxRect = { x: 0, y: 0, w: 0, h: 0 };
    let activeBox = null;
    let currentAtlasObj = null;

    // 🌟 精密な座標計算 (sprite_sheet_motion 方式)
    function getCanvasPos(e) {
        const rect = canvasContainer.getBoundingClientRect();
        return {
            x: Math.floor((e.clientX - rect.left) / zoomLevel),
            y: Math.floor((e.clientY - rect.top) / zoomLevel)
        };
    }

    function applyZoom(newZoom) {
        zoomLevel = Math.max(0.25, Math.min(8.0, newZoom));
        canvasContainer.style.zoom = zoomLevel;
        if (lblZoom) lblZoom.textContent = Math.round(zoomLevel * 100) + '%';
    }

    // ズーム操作
    document.getElementById('btn-zoom-in').addEventListener('click', () => applyZoom(zoomLevel + 0.5));
    document.getElementById('btn-zoom-out').addEventListener('click', () => applyZoom(zoomLevel - 0.5));
    canvasContainer.parentElement.addEventListener('wheel', (e) => {
        if (e.ctrlKey) { e.preventDefault(); applyZoom(e.deltaY < 0 ? zoomLevel * 1.1 : zoomLevel / 1.1); }
    }, { passive: false });

    // 🌟 マウスイベント
    canvasContainer.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return;
        const pos = getCanvasPos(e);
        const target = e.target;

        if (target.classList.contains('custom-edge-handle')) {
            e.stopPropagation();
            const dir = Array.from(target.classList).find(c => c.startsWith('handle-')).replace('handle-', '');
            mode = 'resizing-' + dir;
            activeBox = target.closest('.part-frame-overlay');
            startMouseX = e.clientX; startMouseY = e.clientY;
            initialBoxRect = {
                x: parseInt(activeBox.style.left), y: parseInt(activeBox.style.top),
                w: parseInt(activeBox.style.width), h: parseInt(activeBox.style.height)
            };
        } else if (target.classList.contains('part-frame-overlay')) {
            e.stopPropagation();
            const name = target.dataset.name;
            selectFrame(name, true); // 🌟 クリック時はスクロールさせない
            
            mode = 'moving';
            activeBox = target;
            startMouseX = e.clientX; startMouseY = e.clientY;
            initialBoxRect = {
                x: parseInt(activeBox.style.left), y: parseInt(activeBox.style.top),
                w: parseInt(activeBox.style.width), h: parseInt(activeBox.style.height)
            };
        } else {
            mode = 'creating';
            startMouseX = e.clientX; startMouseY = e.clientY;
            const startPos = getCanvasPos(e);
            initialBoxRect = { x: startPos.x, y: startPos.y, w: 0, h: 0 };
            dragSelector.classList.remove('d-none');
            updateSelector(initialBoxRect.x, initialBoxRect.y, 0, 0);
        }
    });

    window.addEventListener('mousemove', function(e) {
        if (mode === 'idle') return;
        const deltaX = (e.clientX - startMouseX) / zoomLevel;
        const deltaY = (e.clientY - startMouseY) / zoomLevel;

        if (mode === 'creating') {
            let w = Math.abs(deltaX); let h = Math.abs(deltaY);
            let x = deltaX >= 0 ? initialBoxRect.x : initialBoxRect.x - w;
            let y = deltaY >= 0 ? initialBoxRect.y : initialBoxRect.y - h;
            updateSelector(x, y, w, h);
            updateAtlasTextarea();
        } else if (mode === 'moving' && activeBox) {
            const nx = Math.floor(initialBoxRect.x + deltaX);
            const ny = Math.floor(initialBoxRect.y + deltaY);
            activeBox.style.left = nx + 'px'; activeBox.style.top = ny + 'px';
            
            // 🌟 findPart で新形式のパーツを取得・更新
            const part = findPart(activeBox.dataset.name);
            if (part) {
                part.src_x = nx; 
                part.src_y = ny;
                if (inpName.value === part.name) { inpX.value = nx; inpY.value = ny; }
                updateAtlasTextarea();
            }
        } else if (mode.startsWith('resizing-') && activeBox) {
            const dir = mode.replace('resizing-', '');
            let { x, y, w, h } = initialBoxRect;
            if (dir.includes('e')) w += deltaX;
            if (dir.includes('w')) { x += deltaX; w -= deltaX; }
            if (dir.includes('s')) h += deltaY;
            if (dir.includes('n')) { y += deltaY; h -= deltaY; }
            w = Math.max(4, Math.floor(w)); h = Math.max(4, Math.floor(h));
            x = Math.floor(x); y = Math.floor(y);
            
            activeBox.style.left = x + 'px'; activeBox.style.top = y + 'px';
            activeBox.style.width = w + 'px'; activeBox.style.height = h + 'px';
            
            // 🌟 新構造のキー名 (src_x, src_y, src_width, src_height) で代入
            const part = findPart(activeBox.dataset.name);
            if (part) {
                part.src_x = x; 
                part.src_y = y; 
                part.src_width = w; 
                part.src_height = h;
                if (inpName.value === part.name) { 
                    inpX.value = x; inpY.value = y; inpW.value = w; inpH.value = h; 
                }
                updateAtlasTextarea();
            }
        }
    });

    window.addEventListener('mouseup', function() {
        if (mode === 'creating') {
            const w = parseInt(dragSelector.style.width);
            const h = parseInt(dragSelector.style.height);
            const x = parseInt(dragSelector.style.left);
            const y = parseInt(dragSelector.style.top);
            dragSelector.classList.add('d-none');
            if (w > 4 && h > 4) {
                const name = prompt('パーツ名を入力してください');
                const category = inpcategory ? inpcategory.value : 'eye';
                if (name && name.trim() !== '') {
                    addOrUpdatePart(name.trim(),category, x, y, w, h);
                    renderExistingFrames();
                    updateAtlasTextarea();
                    drawAtlasThumbnails();
                    selectFrame(name.trim()); // 🌟 選択状態にする
                }
            }
        }
        mode = 'idle';
    });

    // 選択枠の更新関数 (drawPreview を drawAtlasThumbnails に変更)
    function updateSelector(x, y, w, h) {
        dragSelector.style.left = Math.floor(x) + 'px';
        dragSelector.style.top = Math.floor(y) + 'px';
        dragSelector.style.width = Math.floor(w) + 'px';
        dragSelector.style.height = Math.floor(h) + 'px';
        
        if (typeof drawAtlasThumbnails === 'function') {
            drawAtlasThumbnails(); // サムネイルをリアルタイム更新
        }
    }

    // 🌟 入力欄からの微調整 (双方向同期)
    [inpX, inpY, inpW, inpH].forEach(input => {
        if (input) {
            input.addEventListener('input', () => {
                const name = inpName.value;
                const part = findPart(name);
                if (part) {
                    // 数値を書き換え
                    part.src_x = parseInt(inpX.value) || 0;
                    part.src_y = parseInt(inpY.value) || 0;
                    part.src_width = Math.max(1, parseInt(inpW.value) || 1);
                    part.src_height = Math.max(1, parseInt(inpH.value) || 1);

                    // 画面上の枠線を同期更新
                    const box = canvasContainer.querySelector(`.part-frame-overlay[data-name="${CSS.escape(name)}"]`);
                    if (box) {
                        box.style.left = part.src_x + 'px';
                        box.style.top = part.src_y + 'px';
                        box.style.width = part.src_width + 'px';
                        box.style.height = part.src_height + 'px';
                    }

                    // テキストエリア（hidden等）やサムネイルを更新
                    if (typeof updateAtlasTextarea === 'function') updateAtlasTextarea();
                    if (typeof drawAtlasThumbnails === 'function') drawAtlasThumbnails();
                }
            });
        }
    });
    //カテゴリ変更時の適用
    if (inpcategory) {
        inpcategory.addEventListener('change', () => {
            const name = inpName.value;
            const frame = findPart(name);
            if (frame) {
                frame.category = inpcategory.value;
                updateAtlasTextarea();
            }
        });
    }

    // 🌟 2. 検索ロジック (新構造に統一)
    function findPart(name) {
        return currentParts.find(p => p.name === name);
    }

    function addOrUpdatePart(name, category, x, y, w, h) {
        const existing = findPart(name);
        if (existing) { 
            existing.category = category;
            existing.src_x = parseInt(x) || 0;
            existing.src_y = parseInt(y) || 0;
            existing.src_width = parseInt(w) || 1;
            existing.src_height = parseInt(h) || 1;
        } else { 
            currentParts.push({ 
                name: name, 
                category: category, 
                src_x: parseInt(x) || 0,
                src_y: parseInt(y) || 0,
                src_width: parseInt(w) || 1,
                src_height: parseInt(h) || 1
            }); 
        }
    }

    // 初期データの読み込み（BladeからPHP配列をそのまま取得）
    let currentParts = @json($selectedSheet->part_data ?? []);

    // 検索・更新ロジックの修正
    function findPart(name) {
        return currentParts.find(p => p.name === name);
    }
    // 🌟 既存の定義データから画像内に四角い枠線を組み立てる処理（大修正）
    function renderExistingFrames() {
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => el.remove());
        const directions = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];

        currentParts.forEach(p => {
            const box = document.createElement('div');
            box.className = 'part-frame-overlay';
            box.dataset.name = p.name;
            
            box.style.position = 'absolute';
            box.style.left = (p.src_x || 0) + 'px';
            box.style.top = (p.src_y || 0) + 'px';
            box.style.width = (p.src_width || 1) + 'px';
            box.style.height = (p.src_height || 1) + 'px';

            directions.forEach(dir => {
                const handle = document.createElement('div');
                handle.className = `custom-edge-handle handle-${dir}`;
                box.appendChild(handle);
            });

            canvasContainer.appendChild(box);
        });
    }

    // 🌟 指定したパーツの四角い枠を「選択状態」にして中央にスクロールする処理
    function selectFrame(name, skipScroll = false) {
        let targetBox = null;
        
        // 全ての枠線の状態をリセット
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => {
            const isActive = el.dataset.name === name;
            if (isActive) {
                el.classList.add('active-target');
                el.style.border = '3px solid #00ff00';
                el.style.outline = '2px solid #000000';
                el.style.backgroundColor = 'rgba(0, 255, 0, 0.2)';
                el.style.zIndex = "1100";
                el.querySelectorAll('.custom-edge-handle').forEach(h => h.style.display = 'block');
                targetBox = el;
            } else {
                el.classList.remove('active-target');
                el.style.border = '2px solid #0000ff';
                el.style.outline = '1px solid #ffffff';
                el.style.backgroundColor = 'rgba(0, 0, 255, 0.1)';
                el.style.zIndex = "1000";
                el.querySelectorAll('.custom-edge-handle').forEach(h => h.style.display = 'none');
            }
        });
        
        // 🌟 findPart で新構造データを取得し、フォーム数値を正しく書き換え
        const part = findPart(name);
        if (part) {
            activeBox = targetBox; 
            if (inpName) inpName.value = part.name; 
            if (inpcategory) inpcategory.value = part.category || 'eye'; // 👈 カテゴリ同期
            if (inpX) inpX.value = part.src_x; 
            if (inpY) inpY.value = part.src_y;
            if (inpW) inpW.value = part.src_width; 
            if (inpH) inpH.value = part.src_height;
            
            // サイドバーの一覧表示同期
            document.querySelectorAll('.oled-part-trigger').forEach(el => {
                const isMatch = el.dataset.name === name;
                el.classList.toggle('bg-success', isMatch);
                el.classList.toggle('bg-white', !isMatch);
                el.classList.toggle('bg-opacity-10', isMatch);
                if (isMatch && !skipScroll) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } 
            });

            // 画面外にある場合のスムーズスクロール
            if (targetBox && !skipScroll) {
                const container = canvasContainer.closest('.scroll-container');
                if (container) {
                    const boxX = parseInt(targetBox.style.left) * zoomLevel;
                    const boxY = parseInt(targetBox.style.top) * zoomLevel;
                    const boxW = parseInt(targetBox.style.width) * zoomLevel;
                    const boxH = parseInt(targetBox.style.height) * zoomLevel;
                    
                    container.scrollTo({
                        left: boxX - (container.clientWidth / 2) + (boxW / 2),
                        top: boxY - (container.clientHeight / 2) + (boxH / 2),
                        behavior: 'smooth'
                    });
                }
            }
        }
    }

    function drawAtlasThumbnails() {
        document.querySelectorAll('.part-thumb-canvas').forEach(canvas => {
            const x = parseInt(canvas.dataset.x); const y = parseInt(canvas.dataset.y);
            const w = parseInt(canvas.dataset.w); const h = parseInt(canvas.dataset.h);
            canvas.width = w; canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (ctx && targetImg.naturalWidth > 0) {
                ctx.drawImage(targetImg, x, y, w, h, 0, 0, w, h);
            }
        });
    }

    function updateAtlasTextarea() {
        const txtAtlas = document.getElementById('txt-atlas');
        if (txtAtlas) {
            txtAtlas.value = JSON.stringify(currentParts, null, 4);
        }
        
        console.log("送信されるJSON:", txtAtlas.value); // デバッグ用
                
    }

    // 🌟 初期化処理（二重JSON文字列の安全パース対応）
    function init() {
        console.log("Initializing Pixel Parts Editor...");
        try { 
            let jsonRaw = txtAtlas ? txtAtlas.value.trim() : '';
            if (jsonRaw && jsonRaw !== '""') {
                currentParts = JSON.parse(jsonRaw); 
                
                // データが文字列化されている場合の再パース
                if (typeof currentParts === 'string') {
                    currentParts = JSON.parse(currentParts);
                }
            }
            
            // 配列でない場合は空配列で初期化
            if (!Array.isArray(currentParts)) {
                currentParts = [];
            }
        } catch(e) {
            console.error("JSON Parse Error:", e);
            currentParts = [];
        }
        
        // 枠線のレンダリングとサムネイル同期
        renderExistingFrames(); 
        if (typeof drawAtlasThumbnails === 'function') {
            drawAtlasThumbnails();
        }

        // 初期ロード時に最初の1つを選択
        if (currentParts.length > 0) {
            setTimeout(() => { 
                if (typeof selectFrame === 'function') {
                    selectFrame(currentParts[0].name); 
                }
            }, 100);
        }
        // 🌟 画像の元サイズ (横×縦) をヘッダーラベルに自動セット
        const targetImg = document.getElementById('oled-target-img');
        const lblNaturalSize = document.getElementById('lbl-natural-size');

        function updateImageSizeLabel() {
            if (targetImg && lblNaturalSize && targetImg.naturalWidth > 0) {
                lblNaturalSize.textContent = `${targetImg.naturalWidth}×${targetImg.naturalHeight}`;
            }
        }

        if (targetImg) {
            // 既に画像読み込みが完了している場合（キャッシュ等）
            if (targetImg.complete) {
                updateImageSizeLabel();
            } else {
                // 画像読み込み完了時に実行
                targetImg.addEventListener('load', updateImageSizeLabel);
            }
        }
    }

    // エディタ起動
    // 画像が完全に読み込まれ、キャンバスサイズが確定した後にエディタを安全に初期化する
    console.log("targetImg status:", { complete: targetImg.complete, naturalWidth: targetImg.naturalWidth });
    
    if (targetImg.complete && targetImg.naturalWidth > 0) {
        console.log("Image already loaded, initializing...");
        init();
    } else {
        console.log("Waiting for image load event...");
        targetImg.addEventListener('load', function() {
            console.log("Image load event fired, initializing...");
            init();
        });
        
        // 万が一のタイムアウト処理
        setTimeout(() => {
            if (!currentAtlasObj) {
                console.log("Load timeout, forced init...");
                init();
            }
        }, 3000);
    }

    // 左側サイドバーの一覧をクリックした時の連動イベント
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.oled-part-trigger');
        console.log("Atlas part trigger clicked:", trigger?.dataset?.name); 
        if (trigger) selectFrame(trigger.dataset.name);
    });

    // 削除ボタンのロジック
    const btnDelete = document.getElementById('btn-delete-oled-part');
    if (btnDelete) {
        btnDelete.addEventListener('click', () => {
            const name = inpName.value;
            if (name && confirm(`[${name}] を削除しますか？`)) {
                const idx = currentParts.findIndex(p => p.name === name);
                if (idx >= 0) { 
                    currentParts.splice(idx, 1); 
                    updateAtlasTextarea(); 
                    renderExistingFrames(); 
                    if (typeof drawAtlasThumbnails === 'function') drawAtlasThumbnails();
                    inpName.value = '';
                    resetFields();
                }
            }
        });
    }

    function resetFields() {
        inpX.value = ''; inpY.value = ''; inpW.value = ''; inpH.value = '';
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => el.classList.remove('active-target'));
    }
});
@endif
</script>