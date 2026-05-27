<form action="{{ route('admin.game.sprite_sheet.update') }}" method="POST">
    @csrf
    <input type="hidden" name="filename" value="{{ $activeFile }}">
    <input type="hidden" name="motion_content" value="{{ $motionContent }}"> 
    <input type="hidden" name="mode" value="atlas"> <input type="hidden" name="filename" value="{{ $activeFile }}">

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <span class="badge bg-primary me-2">アトラス切出</span><code>{{ $activeFile }}</code>
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                        <span class="btn btn-outline-light disabled text-white fw-bold" id="lbl-zoom" style="opacity: 1; min-width: 65px;">100%</span>
                        <button type="button" class="btn btn-outline-light" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                        <button type="button" class="btn btn-outline-light" id="btn-zoom-reset">リセット</button>
                    </div>
                </div>
                <div class="card-body bg-secondary p-0 text-center position-relative" style="max-height: 450px; overflow: auto;">
                    <div id="canvas-container" class="position-relative d-inline-block m-3 shadow" style="user-select: none; cursor: crosshair;">
                        <img id="sprite-target-img" src="{{ asset('storage/sprite_sheet/' . $activeFile) }}" class="d-block" style="pointer-events: none;">
                        <div id="drag-selector" class="position-absolute border border-danger bg-danger bg-opacity-25 d-none"></div>
                    </div>
                </div>
                <div class="card-footer bg-light small text-muted">
                    <strong>操作方法:</strong> 背景ドラッグで新規枠追加。枠の移動・リサイズでJSONが自動更新されます。<strong>Ctrl + ホイール</strong>でズーム可。
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3 mb-4 p-3 bg-light rounded border">
                        <div class="col-md-4">
                            <label class="form-label small font-monospace fw-bold">選択中のパーツ名 (filename)</label>
                            <input type="text" id="part-filename" class="form-control form-control-sm" placeholder="枠を選択するか新規ドラッグしてください">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace">X 座標</label>
                            <input type="number" id="part-x" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace">Y 座標</label>
                            <input type="number" id="part-y" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace">Width (幅)</label>
                            <input type="number" id="part-w" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small font-monospace">Height (高)</label>
                            <input type="number" id="part-h" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="mb-2 small fw-bold text-secondary">① 切り出し座標 (_atlas.json)
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>切り出し座標をファイルへ保存</button>
                    </div>
                    <textarea name="atlas_content" id="atlas-textarea" class="form-control font-monospace small" rows="12" style="font-size: 12px; tab-size: 2;">{{ $atlasContent }}</textarea>

                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('canvas-container');
    const img = document.getElementById('sprite-target-img');
    const selector = document.getElementById('drag-selector');
    const txtAtlas = document.getElementById('atlas-textarea');
    const inpName = document.getElementById('part-filename');
    const inpX = document.getElementById('part-x'); const inpY = document.getElementById('part-y');
    const inpW = document.getElementById('part-w'); const inpH = document.getElementById('part-h');

    let zoomLevel = 1.0; let mode = 'idle'; let startX = 0, startY = 0;
    let currentAtlasObj = null; let activeBoxElement = null; let targetFrameData = null;
    let boxInitialX = 0, boxInitialY = 0, boxInitialW = 0, boxInitialH = 0;

    // 🌟【新設】frame内の改行だけを綺麗に1行にギュッとまとめる整形マシパペット
    function updateAtlasTextarea() {
        if (!currentAtlasObj) return;
        // 一旦普通に整形して文字列化
        let jsonStr = JSON.stringify(currentAtlasObj, null, 2);
        
        // frame オブジェクトの中身の改行と空白を正規表現で1行に置換
        jsonStr = jsonStr.replace(
            /"frame":\s*\{\s*\n\s*"x":\s*(-?\d+),\s*\n\s*"y":\s*(-?\d+),\s*\n\s*"w":\s*(-?\d+),\s*\n\s*"h":\s*(-?\d+)\s*\n\s*\}/g, 
            '"frame": { "x": $1, "y": $2, "w": $3, "h": $4 }'
        );
        
        txtAtlas.value = jsonStr;
    }

    function changeZoom(newZoom) {
        zoomLevel = Math.max(0.5, Math.min(4.0, newZoom));
        container.style.zoom = zoomLevel;
        document.getElementById('lbl-zoom').textContent = Math.round(zoomLevel * 100) + '%';
    }
    document.getElementById('btn-zoom-in').addEventListener('click', () => changeZoom(zoomLevel + 0.25));
    document.getElementById('btn-zoom-out').addEventListener('click', () => changeZoom(zoomLevel - 0.25));
    document.getElementById('btn-zoom-reset').addEventListener('click', () => changeZoom(1.0));
    container.addEventListener('wheel', function(e) {
        if (e.ctrlKey) { e.preventDefault(); changeZoom(e.deltaY < 0 ? zoomLevel + 0.25 : zoomLevel - 0.25); }
    }, { passive: false });

    function renderExistingFrames() {
        container.querySelectorAll('.part-frame-overlay').forEach(el => el.remove());
        activeBoxElement = null;
        try {
            currentAtlasObj = JSON.parse(txtAtlas.value);
            if (!currentAtlasObj.textures || !currentAtlasObj.textures[0].frames) return;
            currentAtlasObj.textures[0].frames.forEach((f) => {
                const box = document.createElement('div');
                box.className = 'position-absolute border border-primary bg-primary bg-opacity-10 part-frame-overlay';
                box.style.left = f.frame.x + 'px'; box.style.top = f.frame.y + 'px';
                box.style.width = f.frame.w + 'px'; box.style.height = f.frame.h + 'px';
                box.style.cursor = 'move'; box.dataset.filename = f.filename;
                
                ['n', 's', 'e', 'w', 'nw', 'ne', 'sw', 'se'].forEach(dir => {
                    const h = document.createElement('span');
                    h.className = `custom-edge-handle handle-${dir}`; h.innerHTML = '&nbsp;';
                    box.appendChild(h);
                    h.addEventListener('mousedown', function(e) {
                        if (e.button !== 0) return; e.stopPropagation(); e.preventDefault();
                        selectBox(box, f); mode = `resizing-${dir}`;
                        const rect = container.getBoundingClientRect();
                        startX = Math.floor((e.clientX - rect.left) / zoomLevel); startY = Math.floor((e.clientY - rect.top) / zoomLevel);
                        boxInitialX = f.frame.x; boxInitialY = f.frame.y; boxInitialW = f.frame.w; boxInitialH = f.frame.h;
                    });
                });
                box.addEventListener('click', function(e) { e.stopPropagation(); selectBox(box, f); });
                box.addEventListener('mousedown', function(e) {
                    if (e.button !== 0 || e.target.classList.contains('custom-edge-handle')) return;
                    e.stopPropagation(); selectBox(box, f); mode = 'moving';
                    const rect = container.getBoundingClientRect();
                    startX = Math.floor((e.clientX - rect.left) / zoomLevel); startY = Math.floor((e.clientY - rect.top) / zoomLevel);
                    boxInitialX = f.frame.x; boxInitialY = f.frame.y;
                });
                container.appendChild(box);
                if (inpName.value === f.filename) selectBox(box, f);
            });
        } catch (e) {}
    }

    function selectBox(boxElement, frameData) {
        container.querySelectorAll('.part-frame-overlay').forEach(el => el.classList.remove('border-success', 'bg-success', 'bg-opacity-25', 'active-target'));
        boxElement.classList.add('border-success', 'bg-success', 'bg-opacity-25', 'active-target');
        activeBoxElement = boxElement; targetFrameData = frameData;
        inpName.value = frameData.filename; inpX.value = frameData.frame.x; inpY.value = frameData.frame.y; inpW.value = frameData.frame.w; inpH.value = frameData.frame.h;
    }

    img.onload = renderExistingFrames;
    if (img.complete) renderExistingFrames();

    container.addEventListener('mousedown', function (e) {
        if (e.button !== 0 || mode !== 'idle') return;
        if (e.target.classList.contains('part-frame-overlay') || e.target.classList.contains('custom-edge-handle')) return;
        mode = 'creating';
        const rect = container.getBoundingClientRect();
        startX = Math.floor((e.clientX - rect.left) / zoomLevel); startY = Math.floor((e.clientY - rect.top) / zoomLevel);
        selector.classList.remove('d-none');
        selector.style.left = startX + 'px'; selector.style.top = startY + 'px';
        selector.style.width = '0px'; selector.style.height = '0px';
    });

    window.addEventListener('mousemove', function (e) {
        if (mode === 'idle') return;
        const rect = container.getBoundingClientRect();
        let currentX = Math.floor((e.clientX - rect.left) / zoomLevel); let currentY = Math.floor((e.clientY - rect.top) / zoomLevel);
        currentX = Math.max(0, Math.min(currentX, img.naturalWidth)); currentY = Math.max(0, Math.min(currentY, img.naturalHeight));
        const deltaX = currentX - startX; const deltaY = currentY - startY;

        if (mode === 'creating') {
            const x = Math.min(startX, currentX); const y = Math.min(startY, currentY);
            const w = Math.abs(startX - currentX); const h = Math.abs(startY - currentY);
            selector.style.left = x + 'px'; selector.style.top = y + 'px';
            selector.style.width = w + 'px'; selector.style.height = h + 'px';
            inpX.value = x; inpY.value = y; inpW.value = w; inpH.value = h;
        } else if (mode === 'moving' && activeBoxElement && targetFrameData) {
            let newX = Math.max(0, Math.min(boxInitialX + deltaX, img.naturalWidth - targetFrameData.frame.w));
            let newY = Math.max(0, Math.min(boxInitialY + deltaY, img.naturalHeight - targetFrameData.frame.h));
            activeBoxElement.style.left = newX + 'px'; activeBoxElement.style.top = newY + 'px';
            targetFrameData.frame.x = newX; targetFrameData.frame.y = newY;
            inpX.value = newX; inpY.value = newY;
        } else if (mode.startsWith('resizing-') && activeBoxElement && targetFrameData) {
            const resizeDir = mode.replace('resizing-', '');
            let newX = targetFrameData.frame.x; let newY = targetFrameData.frame.y;
            let newW = targetFrameData.frame.w; let newH = targetFrameData.frame.h;
            if (resizeDir.includes('e')) { newW = Math.max(4, Math.min(boxInitialW + deltaX, img.naturalWidth - boxInitialX)); }
            else if (resizeDir.includes('w')) { newX = boxInitialX + deltaX; newW = boxInitialW - deltaX; if (newX < 0) { newW += newX; newX = 0; } if (newW < 4) { newX = boxInitialX + boxInitialW - 4; newW = 4; } }
            if (resizeDir.includes('s')) { newH = Math.max(4, Math.min(boxInitialH + deltaY, img.naturalHeight - boxInitialY)); }
            else if (resizeDir.includes('n')) { newY = boxInitialY + deltaY; newH = boxInitialH - deltaY; if (newY < 0) { newH += newY; newY = 0; } if (newH < 4) { newY = boxInitialY + boxInitialH - 4; newH = 4; } }
            activeBoxElement.style.left = newX + 'px'; activeBoxElement.style.top = newY + 'px';
            activeBoxElement.style.width = newW + 'px'; activeBoxElement.style.height = newH + 'px';
            targetFrameData.frame.x = newX; targetFrameData.frame.y = newY; targetFrameData.frame.w = newW; targetFrameData.frame.h = newH;
            inpX.value = newX; inpY.value = newY; inpW.value = newW; inpH.value = newH;
        }
    });

    window.addEventListener('mouseup', function () {
        if (mode === 'creating') {
            selector.classList.add('d-none');
            const x = parseInt(inpX.value); const y = parseInt(inpY.value);
            const w = parseInt(inpW.value); const h = parseInt(inpH.value);
            if (w > 5 && h > 5) {
                const name = prompt('新規登録するパーツ名（filename）を入力してください:');
                if (name && name.trim() !== '') {
                    if (!currentAtlasObj || !currentAtlasObj.textures) currentAtlasObj = { textures: [{ image: "{{ $activeFile }}", size: { w: img.naturalWidth, h: img.naturalHeight }, frames: [] }] };
                    const frames = currentAtlasObj.textures[0].frames;
                    const targetFrameNode = { filename: name.trim(), frame: { x: x, y: y, w: w, h: h } };
                    const existingIndex = frames.findIndex(f => f.filename === name.trim());
                    if (existingIndex >= 0) { if (!confirm(`既に [${name.trim()}] が存在します。上書きしますか？`)) return; frames[existingIndex] = targetFrameNode; }
                    else { frames.push(targetFrameNode); }
                    updateAtlasTextarea(); // 🌟 変更
                    renderExistingFrames();
                }
            }
        } else if ((mode === 'moving' || mode.startsWith('resizing-')) && currentAtlasObj) {
            updateAtlasTextarea(); // 🌟 変更
        }
        mode = 'idle';
    });

    [inpX, inpY, inpW, inpH].forEach(input => {
        input.addEventListener('input', function() {
            if (!targetFrameData || !activeBoxElement) return;
            const val = parseInt(this.value) || 0;
            if (this === inpX) targetFrameData.frame.x = val;
            if (this === inpY) targetFrameData.frame.y = val;
            if (this === inpW) targetFrameData.frame.w = val;
            if (this === inpH) targetFrameData.frame.h = val;
            activeBoxElement.style.left = targetFrameData.frame.x + 'px'; activeBoxElement.style.top = targetFrameData.frame.y + 'px';
            activeBoxElement.style.width = targetFrameData.frame.w + 'px'; activeBoxElement.style.height = targetFrameData.frame.h + 'px';
            updateAtlasTextarea(); // 🌟 変更
        });
    });

    inpName.addEventListener('input', function() {
        if (!targetFrameData || !activeBoxElement) return;
        const newName = this.value.trim(); if (newName === '') return;
        targetFrameData.filename = newName; activeBoxElement.dataset.filename = newName;
        updateAtlasTextarea(); // 🌟 変更
    });

    txtAtlas.addEventListener('input', renderExistingFrames);
});
</script>

<style>
.part-frame-overlay.active-target { border: 2px solid #198754 !important; background-color: rgba(25, 135, 84, 0.04) !important; z-index: 2999 !important; }
.custom-edge-handle { display: none; position: absolute !important; width: 12px !important; height: 12px !important; background-color: #ffffff !important; border: 2px solid #198754 !important; border-radius: 50% !important; z-index: 9999 !important; box-shadow: 0 2px 4px rgba(0,0,0,0.3) !important; box-sizing: border-box !important; visibility: visible !important; opacity: 1 !important; overflow: hidden !important; }
.part-frame-overlay.active-target .custom-edge-handle { display: block !important; }
.handle-nw { top: 0px !important; left: 0px !important; cursor: nw-resize !important; }
.handle-ne { top: 0px !important; right: 0px !important; cursor: ne-resize !important; }
.handle-sw { bottom: 0px !important; left: 0px !important; cursor: sw-resize !important; }
.handle-se { bottom: 0px !important; right: 0px !important; cursor: se-resize !important; }
.handle-n  { top: 0px !important; left: calc(50% - 6px) !important; cursor: n-resize !important; }
.handle-s  { bottom: 0px !important; left: calc(50% - 6px) !important; cursor: s-resize !important; }
.handle-w  { top: calc(50% - 6px) !important; left: 0px !important; cursor: w-resize !important; }
.handle-e  { top: calc(50% - 6px) !important; right: 0px !important; cursor: e-resize !important; }
</style>