{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif
{{-- 🛠️ ピクセルパーツ定義エディタ --}}
<form action="{{ route('admin.game.sprite_sheet.update') }}" method="POST">
    @csrf
    <input type="hidden" name="game_key" value="{{ $gameKey }}">
    <input type="hidden" name="filename" value="{{ $activeFile }}">
    <input type="hidden" name="motion_content" value="{{ $motionContent }}"> 
    <input type="hidden" name="parts_mode" value="pixel">
    <input type="hidden" name="sprite_sheet_id" value="{{ $activeSpriteSheet->id ?? '' }}">

    <div class="row m-0">
        <div class="col-md-2 ps-0 pe-2">
            @include('admin.game.parts.sprite_sheet_list')

            {{-- 定義済みパーツ一覧セクション --}}
            @if($activeFile && isset($definedParts))
                <div class="card shadow-sm border-success animate__animated animate__fadeIn mt-2">
                    <div class="card-header bg-success text-white py-2 small fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-tags-fill me-1"></i> 定義済みパーツ</span>
                        <span class="badge bg-white text-success fw-bold font-monospace" style="font-size:10px;">{{ count($definedParts) }}</span>
                    </div>
                    <div class="card-body bg-light p-1" style="max-height: 380px; overflow-y: auto;">
                        @if(count($definedParts) > 0)
                            <div class="d-flex flex-column gap-1">
                                @foreach($definedParts as $f)
                                    <div class="d-flex align-items-center border border-secondary p-1 px-2 rounded bg-white text-dark shadow-sm pixel-part-trigger" 
                                         style="cursor: pointer; transition: all 0.15s ease; width: 100%; text-align: left;"
                                         data-name="{{ $f['name'] }}">
                                        
                                        <div style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background-color: #f0f0f0; background-image: linear-gradient(45deg, #e0e0e0 25%, transparent 25%, transparent 75%, #e0e0e0 75%), linear-gradient(45deg, #e0e0e0 25%, #f0f0f0 25%, #f0f0f0 75%, #e0e0e0 75%); background-size: 6px 6px; border: 1px solid #dee2e6; border-radius: 4px; overflow: hidden;" class="me-2">
                                            <canvas class="part-thumb-canvas" 
                                                    data-x="{{ $f['frame']['x'] }}" 
                                                    data-y="{{ $f['frame']['y'] }}" 
                                                    data-w="{{ $f['frame']['w'] }}" 
                                                    data-h="{{ $f['frame']['h'] }}" 
                                                    style="max-width: 26px; max-height: 26px; object-fit: contain;">
                                            </canvas>
                                        </div>
                                        @php
                                            if(!isset($f['name'])) {
                                                $f['name'] = $f['filename'];
                                            }
                                        @endphp
                                        <div class="flex-grow-1 min-w-0 font-monospace" style="font-size: 11px; line-height: 1.2;">
                                            <div class="text-success fw-bold text-truncate" style="font-size:11px;" title="{{ $f['name'] }}">
                                                📌 {{ $f['name'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-3 text-muted small border border-dashed rounded bg-white" style="font-size:11px;">
                                まだパーツがありません。
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- 🎨 キャンバスエリア (直接実装) --}}
        <div class="col-md-6 ps-0 pe-2">
            <div class="card mb-0 shadow-sm border-secondary" id="pixel-cropper-card" style="height: calc(100vh - 120px); min-height: 600px; display: flex; flex-direction: column;">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 small fw-bold">
                            <span class="badge bg-primary me-2">ピクセル切出</span><code>{{ $activeFile }}</code>
                        </h6>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                        <span class="btn btn-outline-light disabled text-white fw-bold py-0 small" id="lbl-zoom" style="opacity: 1; min-width: 55px; font-size:11px;">100%</span>
                        <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                        <button type="button" class="btn btn-outline-light px-2 py-0 font-weight-bold" id="btn-zoom-reset" style="font-size:11px;">戻す</button>
                    </div>
                </div>

                {{-- スクロールを破壊する display: flex 関連を撤去して text-center に統一し、市松模様はコンテナの背景に直接埋め込みます --}}
                <div class="card-body bg-secondary p-0 text-center scroll-container" style="flex: 1; overflow: auto; position: relative;">
                    <div id="pixel-canvas-container" class="position-relative d-inline-block m-4 shadow" style="user-select: none; cursor: crosshair; transform-origin: 0 0; background-color: #e5e5e5; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, #e5e5e5 25%, #e5e5e5 75%, #ccc 75%); background-size: 20px 20px; background-position: 0 0, 10px 10px;">
                        
                        {{-- 🌟 画像本体 --}}
                        <img id="pixel-target-img" src="{{ asset('storage/sprite_sheet/' . $activeFileCategory . '/' . $activeFile) }}" class="d-block" style="pointer-events: none; max-width: none !important; min-width: max-content !important; position: relative; z-index: 1;">
                        
                        <div id="pixel-drag-selector" class="position-absolute border border-danger bg-danger bg-opacity-25 d-none" style="z-index: 2000; pointer-events: none;"></div>
                    </div>
                </div>

                <div class="card-footer bg-light small text-muted font-monospace p-2" style="font-size:10px; line-height:1.3;">
                    <strong>操作:</strong> 背景ドラッグで追加。枠クリックで選択し移動・リサイズ。Ctrl+ホイールでズーム可。
                </div>
            </div>
        </div>

        <div class="col-md-4 ps-2 pe-0">
            <div class="card shadow-sm border-dark">
                <div class="card-body p-2 bg-light">
                    {{-- パーツ座標プロパティインプット --}}
                    <div id="pixel-properties-panel" class="row g-1 mb-2 p-2 bg-white rounded border border-secondary shadow-sm">
                        <div class="col-md-12 mb-1 border-bottom pb-1">
                            <span class="badge bg-success font-monospace" style="font-size:10px;">選択中のパーツ座標設定</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small font-monospace fw-bold m-0" style="font-size:10px;">パーツ名</label>
                            <input type="text" id="part-name" class="form-control form-control-sm font-monospace py-0" style="height:22px; font-size:11px;" placeholder="パーツ名">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:10px;">X</label>
                            <input type="number" id="part-x" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:11px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:10px;">Y</label>
                            <input type="number" id="part-y" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:11px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:10px;">幅</label>
                            <input type="number" id="part-w" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:11px;">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace m-0" style="font-size:10px;">高</label>
                            <input type="number" id="part-h" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:11px;">
                        </div>
                    </div>

                    {{-- JSON構造テキストエリア --}}
                    <div class="mb-1 small font-monospace fw-bold text-secondary d-flex justify-content-between align-items-center">
                        <span style="font-size:11px;">① 切り出し構造データ JSON</span>
                        <div class="d-flex gap-1 align-items-center">
                            <button type="submit" class="btn btn-primary fw-bold">
                                <i class="bi bi-save me-1"></i>保存
                            </button>
                            <button type="button" id="btn-delete-pixel-part" class="btn btn-danger text-white fw-bold">
                                <i class="bi bi-trash"></i> 削除
                            </button>
                            <button type="button" class="btn btn-warning fw-bold" 
                                onclick="if(confirm('JSONファイルを書き出し、ゲームに反映します。よろしいですか？')) { 
                                window.location.href='{{ route('admin.game.publish', ['gameKey' => $gameKey, 'type' => 'sprite_sheet', 'targetKey' => $activeFile]) }}'; }">
                                <i class="bi bi-cloud-upload-fill me-1"></i>反映
                            </button>
                        </div>
                    </div>
                    <textarea name="atlas_content" id="pixel-textarea" class="form-control font-monospace small mb-2" rows="6" style="font-size: 10px; tab-size: 2; height:150px;">{{ $atlasContent }}</textarea>
                </div>

            </div>
        </div>
    </div>
</form>

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
        inset 0 0 0 3px #0000ff, 
        0 0 0 2px #ffffff,
        0 0 10px rgba(0, 0, 0, 0.5) !important;
        
    cursor: move;
    box-sizing: border-box !important;
    
    /* 🌟 重なり順を最前面に強制 */
    z-index: 1000 !important;
}

div.part-frame-overlay:hover {
    background-color: rgba(0, 255, 0, 0.3) !important;
    box-shadow: 
        inset 0 0 0 3px #00ff00, 
        0 0 0 2px #ffffff,
        0 0 15px rgba(0, 255, 0, 0.5) !important;
    z-index: 1100 !important;
}

div.part-frame-overlay.active-target {
    /* 選択時は緑枠に変更し、さらに強調 */
    background-color: rgba(0, 255, 0, 0.15) !important;
    box-shadow: 
        inset 0 0 0 4px #00ff00, 
        0 0 0 2px #000000,
        0 0 20px rgba(0, 255, 0, 0.8) !important;
    z-index: 1200 !important;
}

.custom-edge-handle { 
    position: absolute !important; 
    width: 10px !important; 
    height: 10px !important; 
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const txtAtlas = document.getElementById('pixel-textarea');
    const inpName = document.getElementById('part-name');
    const inpX = document.getElementById('part-x'); const inpY = document.getElementById('part-y');
    const inpW = document.getElementById('part-w'); const inpH = document.getElementById('part-h');

    const canvasContainer = document.getElementById('pixel-canvas-container');
    const targetImg = document.getElementById('pixel-target-img');
    const dragSelector = document.getElementById('pixel-drag-selector');
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
    document.getElementById('btn-zoom-in').addEventListener('click', () => applyZoom(zoomLevel + 0.25));
    document.getElementById('btn-zoom-out').addEventListener('click', () => applyZoom(zoomLevel - 0.25));
    document.getElementById('btn-zoom-reset').addEventListener('click', () => applyZoom(1.0));
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
        } else if (mode === 'moving' && activeBox) {
            const nx = Math.floor(initialBoxRect.x + deltaX);
            const ny = Math.floor(initialBoxRect.y + deltaY);
            activeBox.style.left = nx + 'px'; activeBox.style.top = ny + 'px';
            const frame = findFrame(activeBox.dataset.name);
            if (frame) {
                frame.frame.x = nx; frame.frame.y = ny;
                if (inpName.value === frame.name) { inpX.value = nx; inpY.value = ny; }
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
            const frame = findFrame(activeBox.dataset.name);
            if (frame) {
                frame.frame.x = x; frame.frame.y = y; frame.frame.w = w; frame.frame.h = h;
                if (inpName.value === frame.name) { inpX.value = x; inpY.value = y; inpW.value = w; inpH.value = h; }
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
                const name = prompt('パーツ名を入力してください:');
                if (name && name.trim() !== '') {
                    addOrUpdateFrame(name.trim(), x, y, w, h);
                    renderExistingFrames();
                    updateAtlasTextarea();
                    drawAtlasThumbnails();
                    selectFrame(name.trim()); // 🌟 選択状態にする
                }
            }
        }
        mode = 'idle';
    });

    function updateSelector(x, y, w, h) {
        dragSelector.style.left = Math.floor(x) + 'px';
        dragSelector.style.top = Math.floor(y) + 'px';
        dragSelector.style.width = Math.floor(w) + 'px';
        dragSelector.style.height = Math.floor(h) + 'px';
    }

    // 🌟 入力欄からの微調整 (双方向同期)
    [inpX, inpY, inpW, inpH].forEach(input => {
        input.addEventListener('input', () => {
            const name = inpName.value;
            const frame = findFrame(name);
            if (frame) {
                const val = parseInt(input.value) || 0;
                if (input === inpX) frame.frame.x = val;
                if (input === inpY) frame.frame.y = val;
                if (input === inpW) frame.frame.w = Math.max(1, val);
                if (input === inpH) frame.frame.h = Math.max(1, val);
                
                const box = canvasContainer.querySelector(`.part-frame-overlay[data-filename="${name}"]`);
                if (box) {
                    box.style.left = frame.frame.x + 'px'; 
                    box.style.top = frame.frame.y + 'px';
                    box.style.width = frame.frame.w + 'px'; 
                    box.style.height = frame.frame.h + 'px';
                }
                updateAtlasTextarea();
                drawAtlasThumbnails(); // サムネイルもリアルタイム更新
            }
        });
    });

    // 🌟 データ管理ロジック
    function findFrame(name) {
        return currentAtlasObj?.textures?.[0]?.frames?.find(f => f.name === name);
    }

    function addOrUpdateFrame(name, x, y, w, h) {
        if (!currentAtlasObj || !currentAtlasObj.textures) {
            currentAtlasObj = { textures: [{ image: "{{ $activeFile }}", size: { w: targetImg.naturalWidth, h: targetImg.naturalHeight }, frames: [] }] };
        }
        const frames = currentAtlasObj.textures[0].frames;
        const existing = frames.find(f => f.name === name);
        if (existing) { existing.frame = { x, y, w, h }; }
        else { frames.push({ name: name, frame: { x, y, w, h } }); }
    }

    // 🌟 既存の定義データから画像内に四角い枠線を組み立てる処理（大修正）
    function renderExistingFrames() {
        // 既存の枠を全て一旦お掃除
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => el.remove());
        
        if (!currentAtlasObj || !currentAtlasObj.textures || !currentAtlasObj.textures[0].frames) {
            console.warn("表示できるパーツ定義フレームが見つかりません。");
            return;
        }
        
        // 🌟 カラムが分離されたため、そのまま全フレーム（このカラム内の全データ）を描画します
        const frames = currentAtlasObj.textures[0].frames;
        
        console.log(`${frames.length} 件のパーツ枠を画像内に生成します。`, frames);
        
        frames.forEach((f, index) => {
            const box = document.createElement('div');
            box.className = 'part-frame-overlay';
            box.style.left = f.frame.x + 'px'; 
            box.style.top = f.frame.y + 'px';
            box.style.width = f.frame.w + 'px'; 
            box.style.height = f.frame.h + 'px';
            box.dataset.name = f.name;
            
            // 🌟 インラインスタイルで描画を強制
            box.style.position = 'absolute';
            box.style.display = 'block';
            box.style.zIndex = "1000";
            box.style.border = '2px solid #0000ff';
            box.style.outline = '1px solid #ffffff';
            box.style.backgroundColor = 'rgba(0, 0, 255, 0.1)';
            box.style.pointerEvents = 'auto';

            // リサイズ用ハンドルを追加
            ['n', 's', 'e', 'w', 'nw', 'ne', 'sw', 'se'].forEach(dir => {
                const h = document.createElement('span');
                h.className = `custom-edge-handle handle-${dir}`;
                
                // 🌟 ハンドルのスタイルもインラインで強制
                h.style.position = 'absolute';
                h.style.width = '10px';
                h.style.height = '10px';
                h.style.backgroundColor = '#ffffff';
                h.style.border = '1px solid #0000ff';
                h.style.borderRadius = '50%';
                h.style.display = 'none';
                h.style.zIndex = '1010';
                
                box.appendChild(h);
            });
            
            // 画像コンテナの中に四角い枠を物理的に注入
            canvasContainer.appendChild(box);
            
            // デバッグログ: 生成された枠線の座標と親要素への追加状況を確認
            if (index < 3 || index === frames.length - 1) {
                console.log(`Frame generated: ${f.filename} at (${f.frame.x}, ${f.frame.y}) size ${f.frame.w}x${f.frame.h}`, {
                    offsetParent: box.offsetParent,
                    zIndex: window.getComputedStyle(box).zIndex,
                    visibility: window.getComputedStyle(box).visibility
                });
            }
        });
        
        console.log(`DOM生成完了: 現在のコンテナ内の枠線数 = ${canvasContainer.querySelectorAll('.part-frame-overlay').length}`);

        // すでにインプット欄に名前がある場合は、その枠をアクティブ（黄色）にする
        if (inpName.value) selectFrame(inpName.value, true);
    }

    // 🌟 指定したパーツの四角い枠を「選択状態」にして中央にスクロールする処理
    function selectFrame(name, skipScroll = false) {
        let targetBox = null;
        console.log("Selecting frame:", name);
        
        // 全ての枠線の状態を一旦リセットし、対象のみをアクティブにする
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => {
            const isActive = el.dataset.name === name;
            
            // 🌟 インラインスタイルで状態を切り替え
            if (isActive) {
                el.classList.add('active-target');
                el.style.border = '3px solid #00ff00';
                el.style.outline = '2px solid #000000';
                el.style.backgroundColor = 'rgba(0, 255, 0, 0.2)';
                el.style.zIndex = "1100";
                
                // ハンドルを表示
                el.querySelectorAll('.custom-edge-handle').forEach(h => h.style.display = 'block');
                
                targetBox = el;
            } else {
                el.classList.remove('active-target');
                el.style.border = '2px solid #0000ff';
                el.style.outline = '1px solid #ffffff';
                el.style.backgroundColor = 'rgba(0, 0, 255, 0.1)';
                el.style.zIndex = "1000";
                
                // ハンドルを非表示
                el.querySelectorAll('.custom-edge-handle').forEach(h => h.style.display = 'none');
            }
        });
        
        const frame = findFrame(name);
        if (frame) {
            activeBox = targetBox; 
            inpName.value = name; 
            inpX.value = frame.frame.x; 
            inpY.value = frame.frame.y;
            inpW.value = frame.frame.w; 
            inpH.value = frame.frame.h;
            
            // 左側サイドバーの一覧アイテムをハイライト
            document.querySelectorAll('.pixel-part-trigger').forEach(el => {
                const isMatch = el.dataset.name === name;
                el.classList.toggle('bg-success', isMatch);
                el.classList.toggle('bg-white', !isMatch);
                el.classList.toggle('bg-opacity-10', isMatch);
                if (isMatch && !skipScroll){
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } 
            });

            // 選択された四角い枠線が画面外にある場合、自動でそこまでスムーズスクロール
            if (targetBox && !skipScroll) {
                const container = canvasContainer.closest('.scroll-container');
                if (container) {
                    // zoomLevel を考慮した実際の表示位置を計算
                    const boxX = parseInt(targetBox.style.left) * zoomLevel;
                    const boxY = parseInt(targetBox.style.top) * zoomLevel;
                    const boxW = parseInt(targetBox.style.width) * zoomLevel;
                    const boxH = parseInt(targetBox.style.height) * zoomLevel;
                    
                    // コンテナの中央に対象パーツが来るようにスクロール位置を調整
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
        if (!currentAtlasObj) return;
        txtAtlas.value = JSON.stringify(currentAtlasObj, null, 2).replace(
            /"frame":\s*\{\s*\n\s*"x":\s*(-?\d+),\s*\n\s*"y":\s*(-?\d+),\s*\n\s*"w":\s*(-?\d+),\s*\n\s*"h":\s*(-?\d+)\s*\n\s*\}/g, 
            '"frame": { "x": $1, "y": $2, "w": $3, "h": $4 }'
        );
    }

    // 🌟 初期化処理（二重JSON文字列の安全パース対応）
    function init() {
        console.log("Initializing Pixel Parts Editor...");
        try { 
            let jsonRaw = txtAtlas.value.trim();
            if (jsonRaw && jsonRaw !== '""') {
                currentAtlasObj = JSON.parse(jsonRaw); 
                
                // ➔【最重要】もしデータが文字列のままなら、もう一度パースして確実にオブジェクトに変換する
                if (typeof currentAtlasObj === 'string') {
                    currentAtlasObj = JSON.parse(currentAtlasObj);
                }
            }
            
            // データが空、または構造が不完全な場合のデフォルト初期化
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
            console.error("JSON Parse Error:", e);
            currentAtlasObj = { textures: [{ image: "{{ $activeFile }}", frames: [] }] };
        }
        
        // データを元に画像の上に四角い枠線をレンダリング
        renderExistingFrames(); 
        
        // サムネイルを同期描画
        drawAtlasThumbnails();

        // 初期ロード時にパーツが登録されていれば、最初の1つを自動で黄色枠囲み選択にする
        const frames = currentAtlasObj?.textures?.[0]?.frames || [];
        if (frames.length > 0) {
            setTimeout(() => { selectFrame(frames[0].name); }, 100);
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
        const trigger = e.target.closest('.pixel-part-trigger');
        console.log("Atlas part trigger clicked:", trigger?.dataset?.name); 
        if (trigger) selectFrame(trigger.dataset.name);
    });

    // 削除ボタンのロジック
    document.getElementById('btn-delete-pixel-part').addEventListener('click', () => {
        const name = inpName.value;
        if (name && confirm(`[${name}] を削除しますか？`)) {
            const frames = currentAtlasObj.textures[0].frames;
            const idx = frames.findIndex(f => f.filename === name);
            if (idx >= 0) { 
                frames.splice(idx, 1); 
                updateAtlasTextarea(); 
                renderExistingFrames(); 
                inpName.value = '';
                resetFields();
            }
        }
    });

    function resetFields() {
        inpX.value = ''; inpY.value = ''; inpW.value = ''; inpH.value = '';
        canvasContainer.querySelectorAll('.part-frame-overlay').forEach(el => el.classList.remove('active-target'));
    }
});

</script>