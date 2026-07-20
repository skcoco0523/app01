{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif
{{-- 🧱 グリッドパーツ定義エディタ --}}
<form id="grid-parts-form" action="{{ route('admin.game.sprite_sheet.update') }}" method="POST">
    @csrf
    <input type="hidden" name="filename" value="{{ $activeFile }}">
    <input type="hidden" name="mode" value="atlas">
    <input type="hidden" name="parts_mode" value="grid"> {{-- 🌟 追加: リダイレクト判定用 --}}
    <input type="hidden" name="sprite_sheet_id" value="{{ $activeSpriteSheet->id ?? '' }}">
    <input type="hidden" name="id" id="hidden-item-id">
    <input type="hidden" name="atlas_frame" id="hidden-atlas-frame">

    <div class="row m-0">
        <div class="col-md-2 ps-0 pe-2">
            @include('admin.game.parts.sprite_sheet_list')

            {{-- 🌟 JSON定義済みパーツ一覧 --}}
            @if($activeFile && isset($definedParts))
                <div class="card shadow-sm border-info animate__animated animate__fadeIn mt-2">
                    <div class="card-header bg-info text-white py-2 small fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam me-1"></i> 定義済みパーツ</span>
                        <span class="badge bg-white text-info fw-bold font-monospace" style="font-size:10px;">{{ count($definedParts) }}</span>
                    </div>
                    <div class="card-body bg-light p-1" style="max-height: 380px; overflow-y: auto;">
                        @if(count($definedParts) > 0)
                            <div class="d-flex flex-column gap-1">
                                @foreach($definedParts as $f)
                                    @php
                                        $name = $f['name'] ?? '';
                                        $gridW = $f['grid_w'] ?? 1;
                                        $gridH = $f['grid_h'] ?? 1;
                                    @endphp
                                    <div class="d-flex align-items-center border border-secondary p-1 px-2 rounded bg-white text-dark shadow-sm grid-item-trigger" 
                                         style="width: 100%; text-align: left; font-size: 11px; cursor: pointer;"
                                         data-name="{{ $name }}"
                                         data-grid-w="{{ $gridW }}"
                                         data-grid-h="{{ $gridH }}">
                                        <div class="flex-grow-1 min-w-0 font-monospace">
                                            <div class="text-info fw-bold text-truncate" title="{{ $name }}">
                                                📌 {{ $name }}
                                            </div>
                                            <div class="text-muted" style="font-size: 9px;">
                                                {{ $gridW }}x{{ $gridH }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-3 text-muted small border border-dashed rounded bg-white" style="font-size:11px;">
                                まだ定義されたパーツがありません。
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- 🎨 キャンバスエリア (直接実装) --}}
        <div class="col-md-6 ps-0 pe-2">
            <div class="card mb-0 shadow-sm border-secondary" id="grid-cropper-card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 small fw-bold">
                            <span class="badge bg-primary me-2">グリッド切出</span><code>{{ $activeFile }}</code>
                        </h6>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                        <span class="btn btn-outline-light disabled text-white fw-bold py-0 small" id="lbl-zoom" style="opacity: 1; min-width: 55px; font-size:11px;">100%</span>
                        <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                        <button type="button" class="btn btn-outline-light px-2 py-0 font-weight-bold" id="btn-zoom-reset" style="font-size:11px;">戻す</button>
                    </div>
                </div>

                <div class="card-body bg-secondary p-0 text-center scroll-container" style="max-height: 440px; overflow: auto; position: relative;">
                    <div id="grid-canvas-container" 
                        class="position-relative d-inline-block m-4 shadow" 
                        style="user-select: none; cursor: crosshair; vertical-align: top; background-color: #e5e5e5; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, #e5e5e5 25%, #e5e5e5 75%, #ccc 75%); background-size: 20px 20px; background-position: 0 0, 10px 10px;">
                        
                        <img id="grid-target-img" src="{{ asset('storage/sprite_sheet/' . $activeFileCategory . '/' . $activeFile) }}" class="d-block" style="pointer-events: none; position: relative; z-index: 1;">
                        
                        <div id="grid-drag-selector" class="position-absolute border border-danger bg-danger bg-opacity-25 d-none" style="z-index: 2000; pointer-events: none;"></div>
                    </div>
                </div>

                <div class="card-footer bg-light small text-muted font-monospace p-2" style="font-size:10px; line-height:1.3;">
                    <strong>操作:</strong> 比率を選択して範囲をドラッグ。マウスアップで名前入力。数値入力で微調整可。Ctrl+ホイールでズーム。
                </div>
            </div>
        </div>

        <div class="col-md-4 ps-2 pe-0">
            <div class="card shadow-sm border-dark">
                <div class="card-body p-2 bg-light">
                    {{-- グリッド定義 ＆ アイテム登録パネル --}}
                    <div id="grid-definition-panel" class="p-2 bg-dark text-white rounded border border-info shadow-sm mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-1">
                            <span class="badge bg-info text-dark fw-bold">グリッド比率設定</span>
                            <div class="text-info font-monospace" style="font-size:10px;">1グリッド=32px相当</div>
                        </div>
                        <div class="row g-1 mb-2">
                            <div class="col-12">
                                <label class="text-info small mb-0" style="font-size:10px;">グリッド比率 (W : H)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" id="grid-w" name="grid_w" class="form-control bg-secondary text-white border-0 text-center" value="1" min="1">
                                    <span class="input-group-text bg-dark text-white border-0 px-1">:</span>
                                    <input type="number" id="grid-h" name="grid_h" class="form-control bg-secondary text-white border-0 text-center" value="1" min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 選択範囲の情報 --}}
                    <div class="row g-1 mb-2 p-2 bg-white rounded border border-secondary shadow-sm">
                        <div class="col-md-12 mb-1 border-bottom pb-1">
                            <span class="badge bg-success font-monospace" style="font-size:10px;">選択中のパーツ座標設定</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small font-monospace fw-bold m-0" style="font-size:10px;">パーツ名</label>
                            <input type="text" id="part-name" name="name" class="form-control form-control-sm font-monospace py-0" style="height:22px; font-size:11px;" placeholder="パーツ名">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:9px;">X</label>
                            <input type="number" id="part-x" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:10px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:9px;">Y</label>
                            <input type="number" id="part-y" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:10px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:9px;">幅</label>
                            <input type="number" id="part-w" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:10px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:9px;">高</label>
                            <input type="number" id="part-h" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:10px;">
                        </div>
                    </div>

                    {{-- アクションボタン --}}
                    <div class="mb-1 d-flex gap-1 align-items-center justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                            <i class="bi bi-save me-1"></i>保存
                        </button>
                        <button type="button" class="btn btn-danger btn-sm text-white fw-bold" id="btn-delete-part">
                            <i class="bi bi-trash"></i> 削除
                        </button>
                    </div>

                    {{-- JSON構造テキストエリア --}}
                    <div class="mt-2">
                        <label class="form-label small font-monospace fw-bold m-0" style="font-size:10px;">切り出し構造 JSON</label>
                        <textarea name="atlas_content" id="atlas-textarea" class="form-control form-control-sm font-monospace" style="font-size:10px; height:150px; background:#f8f9fa;">{{ $atlasContent }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.part-frame-overlay {
    position: absolute !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    
    /* 縮小されても視認できるしっかりとした線 (青枠) */
    border: 3px solid #0000ff !important;
    outline: 2px solid #ffffff !important;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5) !important;
    background-color: rgba(0, 0, 255, 0.2) !important;
    cursor: move;
    box-sizing: border-box !important;
    
    /* 🌟 重なり順を最前面に強制 */
    z-index: 1000 !important;
}
.part-frame-overlay.active-target {
    /* 選択時は緑枠に変更 */
    border: 3px solid #00ff00 !important;
    outline: 2px solid #000000 !important;
    background-color: rgba(0, 255, 0, 0.15) !important;
    box-shadow: 0 0 10px rgba(0, 255, 0, 1), inset 0 0 10px rgba(0, 255, 0, 0.5) !important;
    z-index: 1100 !important;
}
.custom-edge-handle { 
    position: absolute !important; width: 10px !important; height: 10px !important; 
    background-color: #ffffff !important; border: 1px solid #00ff00 !important; border-radius: 50% !important; 
    z-index: 1110 !important; box-sizing: border-box !important; display: none;
}
.part-frame-overlay.active-target .custom-edge-handle { display: block !important; }
.handle-nw { top: -5px; left: -5px; cursor: nw-resize; }
.handle-ne { top: -5px; right: -5px; cursor: ne-resize; }
.handle-sw { bottom: -5px; left: -5px; cursor: sw-resize; }
.handle-se { bottom: -5px; right: -5px; cursor: se-resize; }
.handle-n { top: -5px; left: calc(50% - 5px); cursor: n-resize; }
.handle-s { bottom: -5px; left: calc(50% - 5px); cursor: s-resize; }
.handle-w { top: calc(50% - 5px); left: -5px; cursor: w-resize; }
.handle-e { top: calc(50% - 5px); right: -5px; cursor: e-resize; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const txtAtlas = document.getElementById('atlas-textarea');
    const inpName = document.getElementById('part-name');
    const inpGridW = document.getElementById('grid-w'); const inpGridH = document.getElementById('grid-h');
    const inpX = document.getElementById('part-x'); const inpY = document.getElementById('part-y');
    const inpW = document.getElementById('part-w'); const inpH = document.getElementById('part-h');

    const canvasContainer = document.getElementById('grid-canvas-container');
    const targetImg = document.getElementById('grid-target-img');
    const dragSelector = document.getElementById('grid-drag-selector');
    const lblZoom = document.getElementById('lbl-zoom');
    const hiddenId = document.getElementById('hidden-item-id'); // 🌟 追加
    
    let zoomLevel = 1.0;
    let mode = 'idle';
    let startMouseX = 0, startMouseY = 0;
    let initialBoxRect = { x: 0, y: 0, w: 0, h: 0 };
    let activeBox = null;
    let currentAtlasObj = null;
    let activeItemId = null; // 🌟 選択中の GameItem.id
    let selectedOriginalName = null; // 🌟 追記: 選択時の名前を保持

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

    document.getElementById('btn-zoom-in').addEventListener('click', () => applyZoom(zoomLevel + 0.25));
    document.getElementById('btn-zoom-out').addEventListener('click', () => applyZoom(zoomLevel - 0.25));
    document.getElementById('btn-zoom-reset').addEventListener('click', () => applyZoom(1.0));
    canvasContainer.parentElement.addEventListener('wheel', (e) => {
        if (e.ctrlKey) { e.preventDefault(); applyZoom(e.deltaY < 0 ? zoomLevel * 1.1 : zoomLevel / 1.1); }
    }, { passive: false });

    canvasContainer.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return;
        const pos = getCanvasPos(e);
        const target = e.target;
        const ratio = (parseInt(inpGridW.value) || 1) / (parseInt(inpGridH.value) || 1);

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
            mode = 'moving';
            activeBox = target;
            startMouseX = e.clientX; startMouseY = e.clientY;
            initialBoxRect = {
                x: parseInt(activeBox.style.left), y: parseInt(activeBox.style.top),
                w: parseInt(activeBox.style.width), h: parseInt(activeBox.style.height)
            };
            selectFrame(activeBox.getAttribute('data-name'));
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
        const ratio = (parseInt(inpGridW.value) || 1) / (parseInt(inpGridH.value) || 1);

        if (mode === 'creating') {
            let w = Math.abs(deltaX); let h = Math.abs(deltaY);
            if (w / ratio > h) h = w / ratio; else w = h * ratio;
            let x = deltaX >= 0 ? initialBoxRect.x : initialBoxRect.x - w;
            let y = deltaY >= 0 ? initialBoxRect.y : initialBoxRect.y - h;
            updateSelector(x, y, w, h);
        } else if (mode === 'moving' && activeBox) {
            const nx = Math.floor(initialBoxRect.x + deltaX);
            const ny = Math.floor(initialBoxRect.y + deltaY);
            activeBox.style.left = nx + 'px'; activeBox.style.top = ny + 'px';
            const frame = findFrame(activeBox.dataset.name);
            if (frame) {
                frame.frame.x = nx; frame.frame.y = ny;
                if (inpName.value === (frame.name || frame.filename)) { inpX.value = nx; inpY.value = ny; }
                updateAtlasTextarea();
            }
        } else if (mode.startsWith('resizing-') && activeBox) {
            // [ドラッグ操作] 既存枠のリサイズ
            const dir = mode.replace('resizing-', '');
            let { x, y, w, h } = initialBoxRect;
            if (dir.includes('e')) w += deltaX; if (dir.includes('w')) { x += deltaX; w -= deltaX; }
            if (dir.includes('s')) h += deltaY; if (dir.includes('n')) { y += deltaY; h -= deltaY; }
            if (w / ratio > h) h = w / ratio; else w = h * ratio;
            w = Math.max(4, Math.floor(w)); h = Math.max(4, Math.floor(h));
            x = Math.floor(x); y = Math.floor(y);
            activeBox.style.left = x + 'px'; activeBox.style.top = y + 'px';
            activeBox.style.width = w + 'px'; activeBox.style.height = h + 'px';
            const frame = findFrame(activeBox.dataset.name);
            if (frame) {
                frame.frame.x = x; frame.frame.y = y; frame.frame.w = w; frame.frame.h = h;
                if (inpName.value === frame.name) { inpX.value = x; inpY.value = y; inpW.value = w; inpH.value = h; }
                updateAtlasTextarea();
            }
        }
    });

    window.addEventListener('mouseup', function() {
        // [ドラッグ操作] ドラッグ終了
        if (mode === 'creating') {
            const w = parseInt(dragSelector.style.width);
            const h = parseInt(dragSelector.style.height);
            const x = parseInt(dragSelector.style.left);
            const y = parseInt(dragSelector.style.top);
            dragSelector.classList.add('d-none');
            if (w > 4 && h > 4) {
                const name = prompt('パーツ名を入力してください:');
                if (name && name.trim() !== '') {
                    addOrUpdateFrame(name.trim(), x, y, w, h);
                    renderExistingFrames();
                    updateAtlasTextarea();
                    selectFrame(name.trim());
                }
            }
        }
        mode = 'idle';
    });

    // ドラッグ選択枠の描画更新
    function updateSelector(x, y, w, h) {
        dragSelector.style.left = Math.floor(x) + 'px';
        dragSelector.style.top = Math.floor(y) + 'px';
        dragSelector.style.width = Math.floor(w) + 'px';
        dragSelector.style.height = Math.floor(h) + 'px';
    }

    // [入力同期] 右側パネルの各数値入力・比率入力をJSONデータへ同期
    [inpName, inpX, inpY, inpW, inpH, inpGridW, inpGridH].forEach(input => {
        input.addEventListener('input', () => {
            // 名前が変更された場合の特別処理
            if (input === inpName) {
                const oldName = selectedOriginalName;
                const newName = inpName.value.trim();
                if (!oldName || !newName) return;

                const frame = findFrame(oldName);
                if (frame) {
                    frame.name = newName;
                    
                    // キャンバス上の要素の data-name も更新
                    const box = canvasContainer.querySelector(`.part-frame-overlay[data-name="${oldName}"]`);
                    if (box) {
                        box.dataset.name = newName;
                    }
                    selectedOriginalName = newName; // 追跡用名を更新
                    updateAtlasTextarea();
                }
                return;
            }

            const name = inpName.value;
            const frame = findFrame(name);
            if (frame) {
                const val = parseInt(input.value) || 0;
                
                // カスタム属性の同期 (grid_w/h)
                if (input === inpGridW) frame.grid_w = val || 1;
                if (input === inpGridH) frame.grid_h = val || 1;

                // 比率維持の計算
                const gridW = parseInt(inpGridW.value) || 1;
                const gridH = parseInt(inpGridH.value) || 1;
                const ratio = gridW / gridH;

                if (input === inpX) frame.frame.x = val;
                if (input === inpY) frame.frame.y = val;
                
                if (input === inpW) {
                    const newW = Math.max(4, val);
                    const newH = Math.max(4, Math.round(newW / ratio));
                    frame.frame.w = newW; frame.frame.h = newH;
                    inpH.value = newH;
                } else if (input === inpH) {
                    const newH = Math.max(4, val);
                    const newW = Math.max(4, Math.round(newH * ratio));
                    frame.frame.h = newH; frame.frame.w = newW;
                    inpW.value = newW;
                }

                const box = canvasContainer.querySelector(`.part-frame-overlay[data-name="${name}"]`);
                if (box) {
                    box.style.left = frame.frame.x + 'px'; box.style.top = frame.frame.y + 'px';
                    box.style.width = frame.frame.w + 'px'; box.style.height = frame.frame.h + 'px';
                }
                updateAtlasTextarea();
            }
        });
    });

    // フレーム名から該当するJSON内フレームオブジェクトを検索
    function findFrame(name) {
        if (!currentAtlasObj || !currentAtlasObj.textures || !currentAtlasObj.textures[0].frames) return null;
        return currentAtlasObj.textures[0].frames.find(f => (f.name || f.filename) === name);
    }

    // パーツの新規追加または既存情報の更新
    function addOrUpdateFrame(name, x, y, w, h) {
        if (!currentAtlasObj || !currentAtlasObj.textures) {
            currentAtlasObj = { textures: [{ image: "{{ $activeFile }}", size: { w: targetImg.naturalWidth, h: targetImg.naturalHeight }, frames: [] }] };
        }
        const frames = currentAtlasObj.textures[0].frames;
        const existing = findFrame(name);

        const gridW = parseInt(inpGridW.value) || 1;
        const gridH = parseInt(inpGridH.value) || 1;

        if (existing) { 
            existing.grid_w = gridW;
            existing.grid_h = gridH;
            existing.frame = { x, y, w, h }; 
        }
        else { 
            frames.push({ 
                name: name, 
                grid_w: gridW,
                grid_h: gridH,
                frame: { x, y, w, h }
            }); 
        }
    }

    // [キャンバス描画] JSON内の全フレームをキャンバス上に青枠（または緑枠）として描画
    function renderExistingFrames() {
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => el.remove());
        
        const frames = currentAtlasObj?.textures?.[0]?.frames;
        if (!frames) return;

        frames.forEach(f => {
            const box = document.createElement('div');
            box.className = 'part-frame-overlay';
            box.style.left = f.frame.x + 'px'; box.style.top = f.frame.y + 'px';
            box.style.width = f.frame.w + 'px'; box.style.height = f.frame.h + 'px';
            box.dataset.name = f.name || f.filename;

            // ➔【重要】もしデータにnameがない場合は補完しておく
            if (!f.name) f.name = f.filename;

            // スタイルの強制適用
            box.style.position = 'absolute';
            box.style.display = 'block';
            box.style.zIndex = "1000";
            box.style.border = '3px solid #0000ff';
            box.style.outline = '2px solid #ffffff';
            box.style.backgroundColor = 'rgba(0, 0, 255, 0.2)';
            box.style.pointerEvents = 'auto';

            ['n', 's', 'e', 'w', 'nw', 'ne', 'sw', 'se'].forEach(dir => {
                const h = document.createElement('span');
                h.className = `custom-edge-handle handle-${dir}`;
                h.style.position = 'absolute'; h.style.width = '10px'; h.style.height = '10px';
                h.style.backgroundColor = '#ffffff'; h.style.border = '1px solid #00ff00';
                h.style.borderRadius = '50%'; h.style.display = 'none'; h.style.zIndex = '1110';
                box.appendChild(h);
            });
            canvasContainer.appendChild(box);
        });
        if (inpName.value) selectFrame(inpName.value);
    }

    // [パーツ選択] 指定されたフレームを選択状態にし、各パネル・表示を同期
    function selectFrame(filename) {
        if (!filename) return;
        selectedOriginalName = filename; // 🌟 追記: 編集用に保持
        let targetBox = null;
        const cleanFilename = String(filename).trim().toLowerCase();
        
        // 左一覧のハイライト同期
        const trigger = Array.from(document.querySelectorAll('.grid-item-trigger'))
                             .find(el => String(el.getAttribute('data-name') || '').trim().toLowerCase() === cleanFilename);
                             
        if (trigger) {
            inpGridW.value = trigger.getAttribute('data-grid-w') || 1;
            inpGridH.value = trigger.getAttribute('data-grid-h') || 1;
            
            document.querySelectorAll('.grid-item-trigger').forEach(el => {
                const isMatch = el === trigger;
                el.classList.toggle('bg-success', isMatch); el.classList.toggle('bg-opacity-10', isMatch);
                el.classList.toggle('text-success', isMatch); el.classList.toggle('fw-bold', isMatch);
            });
        }
        
        // キャンバス上の枠表示（青←→緑）の切り替え
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => {
            const isActive = el.dataset.name === filename;
            if (isActive) {
                el.classList.add('active-target');
                el.style.border = '3px solid #00ff00'; el.style.outline = '2px solid #000000';
                el.style.backgroundColor = 'rgba(0, 255, 0, 0.2)'; el.style.zIndex = "1100";
                el.querySelectorAll('.custom-edge-handle').forEach(h => h.style.display = 'block');
                targetBox = el;
            } else {
                el.classList.remove('active-target');
                el.style.border = '3px solid #0000ff'; el.style.outline = '2px solid #ffffff';
                el.style.backgroundColor = 'rgba(0, 0, 255, 0.2)'; el.style.zIndex = "1000";
                el.querySelectorAll('.custom-edge-handle').forEach(h => h.style.display = 'none');
            }
        });

        // 右側パネルの座標・サイズの同期
        const frame = findFrame(filename);
        if (frame) {
            inpName.value = filename;
            inpX.value = Math.floor(frame.frame.x); inpY.value = Math.floor(frame.frame.y);
            inpW.value = Math.floor(frame.frame.w); inpH.value = Math.floor(frame.frame.h);
            
            if (frame.grid_w) inpGridW.value = frame.grid_w;
            if (frame.grid_h) inpGridH.value = frame.grid_h;

            // 選択枠を中央へスクロール
            if (targetBox) {
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

    // 🌟 安全なオブジェクト初期化 (二重パース対策)
    function init() {
        try {
            let jsonRaw = txtAtlas.value.trim();
            if (jsonRaw && jsonRaw !== '""') {
                currentAtlasObj = JSON.parse(jsonRaw);
                if (typeof currentAtlasObj === 'string') {
                    currentAtlasObj = JSON.parse(currentAtlasObj);
                }
            }
            
            if (!currentAtlasObj || !currentAtlasObj.textures) {
                currentAtlasObj = { 
                    textures: [{ 
                        image: "{{ $activeFile }}", 
                        size: { w: targetImg.naturalWidth, h: targetImg.naturalHeight },
                        frames: [] 
                    }] 
                };
            }
        } catch(e) {
            console.error("Grid JSON Parse Error:", e);
            currentAtlasObj = { textures: [{ image: "{{ $activeFile }}", frames: [] }] };
        }
        renderExistingFrames();
    }

    targetImg.onload = () => {
        init();
    };
    if (targetImg.complete) targetImg.onload();

    // 左一覧のクリックイベントを正しく selectFrame へ中継させます
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.grid-item-trigger');
        if (trigger) {
            selectFrame(trigger.getAttribute('data-name'));
        }
    });

    function updateAtlasTextarea() {
        if (!currentAtlasObj) return;
        txtAtlas.value = JSON.stringify(currentAtlasObj, null, 2).replace(
            /"frame":\s*\{\s*\n\s*"x":\s*(-?\d+),\s*\n\s*"y":\s*(-?\d+),\s*\n\s*"w":\s*(-?\d+),\s*\n\s*"h":\s*(-?\d+)\s*\n\s*\}/g, 
            '"frame": { "x": $1, "y": $2, "w": $3, "h": $4 }'
        );
    }

    // 🌟 削除ボタンの挙動をJSONベースに修正 (JSで消去して画面更新のみ)
    document.getElementById('btn-delete-part').addEventListener('click', function() {
        const name = inpName.value;
        if (!name) {
            alert('削除するパーツを選択してください。');
            return;
        }

        if (!confirm('このパーツ定義 「' + name + '」 をJSONから削除しますか？\n(「保存」ボタンを押すまで確定されません)')) return;

        if (currentAtlasObj && currentAtlasObj.textures[0]) {
            const frames = currentAtlasObj.textures[0].frames;
            const index = frames.findIndex(f => (f.name || f.filename) === name);
            if (index !== -1) {
                frames.splice(index, 1);
                updateAtlasTextarea();
                renderExistingFrames(); // キャンバス上の枠を再描画
                inpName.value = ''; // 入力欄をクリア
                inpX.value = ''; inpY.value = ''; inpW.value = ''; inpH.value = '';
            }
        }
    });

});
</script>
