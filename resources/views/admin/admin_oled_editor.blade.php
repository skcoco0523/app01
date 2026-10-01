<div class="container-fluid py-2" id="oled-editor-app">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3>{{ isset($face) ? 'OLEDフェイス編集' : 'OLEDフェイス新規作成' }}</h3>
            <div>
                <a href="{{ route('admin.oled.index') }}" class="btn btn-secondary">戻る</a>
                <button type="button" class="btn btn-success" id="save-face-btn">保存する</button>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="oled-form" action="{{ isset($face) ? route('admin.oled.update', $face->id) : route('admin.oled.store') }}" method="POST">
        @csrf
        @if(isset($face)) @method('PUT') @endif
        <input type="hidden" name="editor_json" id="editor-json-input">

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">タイトル</label>
                <input type="text" name="title" class="form-control" id="title-input" value="{{ isset($face) ?$face->title : '' }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">イベントキー (event_type)</label>
                <input type="text" name="event_type" class="form-control" id="event-type-input" value="{{ isset($face) ?$face->event_type : '' }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">コマ速度 (ms)</label>
                <input type="number" name="interval_ms" class="form-control" id="interval-ms-input" value="{{ isset($face) ?$face->interval_ms : 150 }}" min="20" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">ポイント</label>
                <input type="number" name="point_cost" class="form-control" id="point-cost-input" value="{{ isset($face) ?$face->point_cost : 0 }}" min="0" required>
            </div>
        </div>
    </form>

    <div class="row">
        {{-- 左側: パーツパレット ＋ コマ一覧 --}}
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white">パーツパレット (カテゴリ別)</div>
                <div class="card-body p-2" style="max-height: 350px; overflow-y: auto;" id="parts-palette"></div>
            </div>

            {{-- コマ（フレーム）一覧 --}}
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                    <span>コマ一覧</span>
                    <button type="button" class="btn btn-sm btn-light py-0 px-2" id="add-frame-btn">+ 追加</button>
                </div>
                <div class="card-body p-2 d-flex gap-2 flex-wrap" id="frame-list"></div>
            </div>
        </div>

        {{-- 中央: キャンバス (128x64) --}}
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white py-2">
                    キャンバス (128x64) - コマ #<span id="current-frame-label">1</span>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center bg-secondary bg-opacity-25 p-3">
                    <div id="oled-canvas"
                         class="position-relative border shadow-sm"
                         style="width: 256px; height: 128px; overflow: hidden; background-color: #eee; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0px;">
                    </div>
                </div>
            </div>
        </div>

        {{-- 右側: プロパティ設定 --}}
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white py-2">プロパティ</div>
                <div class="card-body p-3" id="property-panel">
                    <div id="property-empty" class="text-muted small">
                        キャンバス上のパーツを選択すると座標を変更できます。
                    </div>

                    <div id="property-editor" class="d-none">
                        <div class="mb-2">
                            <label class="small fw-bold mb-1">X 座標 (0-127)</label>
                            <input type="number" class="form-control form-control-sm" id="layer-x-input" min="0" max="127">
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold mb-1">Y 座標 (0-63)</label>
                            <input type="number" class="form-control form-control-sm" id="layer-y-input" min="0" max="63">
                        </div>
                        <button type="button" class="btn btn-sm btn-danger w-100 mt-3" id="remove-layer-btn">レイヤー削除</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    console.log("🚀 [1/3] OLED Editor スクリプトの読み込みを開始しました");

    try {
        const rawParts = @json($allParts ?? $parts ?? []);
        const parts = Array.isArray(rawParts) ? rawParts : Object.values(rawParts || {});
        const existing = @json($face ?? null);
        const baseUrl = @json(asset(''));

        let initialFrames = [{ layers: [] }];
        if (existing && existing.editor_json) {
            try {
                const ej = typeof existing.editor_json === 'string'
                    ? JSON.parse(existing.editor_json)
                    : existing.editor_json;

                if (ej && typeof ej === 'object' && Array.isArray(ej.frames) && ej.frames.length > 0) {
                    initialFrames = ej.frames;
                }
            } catch (e) {
                console.error('editor_json パース失敗:', e);
            }
        }

        initialFrames = Array.isArray(initialFrames) ? initialFrames : [{ layers: [] }];
        initialFrames = initialFrames.map(function (frame) {
            return {
                ...(frame && typeof frame === 'object' ? frame : {}),
                layers: (frame && Array.isArray(frame.layers)) ? frame.layers : []
            };
        });

        const state = {
            form: {
                title: existing ? (existing.title || '') : '',
                event_type: existing ? (existing.event_type || '') : '',
                interval_ms: existing ? Number(existing.interval_ms || 150) : 150,
                point_cost: existing ? Number(existing.point_cost || 0) : 0,
                frames: initialFrames
            },
            currentFrame: 0,
            selectedLayer: null
        };

        const categoryNames = {
            eye: 'eye',
            mouth: 'mouth',
            brow: 'brow',
            accessory: 'accessory',
            other: 'other'
        };

        // 🌟 DOM取得用ヘルパー (常に最新のDOMを取得)
        const getEl = id => document.getElementById(id);

        let dragging = false;
        let dragIndex = null;
        let dragStartMouseX = 0;
        let dragStartMouseY = 0;
        let dragStartLayerX = 0;
        let dragStartLayerY = 0;

        function getPartFilePath(p) {
            if (!p) return '';
            return p.sheet_file_path || p.file_path || p.image_path || p.path || p.src || '';
        }

        function getPartX(p) { if (!p) return 0; return Number(p.src_x ?? p.x ?? 0) || 0; }
        function getPartY(p) { if (!p) return 0; return Number(p.src_y ?? p.y ?? 0) || 0; }
        function getPartW(p) { if (!p) return 16; return Number(p.src_width ?? p.w ?? p.width ?? 16) || 16; }
        function getPartH(p) { if (!p) return 16; return Number(p.src_height ?? p.h ?? p.height ?? 16) || 16; }

        function getImageUrl(path) {
            if (!path) return '';
            return baseUrl + String(path).replace(/^\/+/, '');
        }

        function getCurrentLayers() {
            const frame = state.form.frames[state.currentFrame];
            return frame && Array.isArray(frame.layers) ? frame.layers : [];
        }

        function getPartById(partId) {
            return parts.find(function (p) {
                return String(p.id) === String(partId) || String(p.name) === String(partId);
            }) || null;
        }

        function normalizeLayer(layer) {
            const part = getPartById(layer.part_id);
            const path = getPartFilePath(layer) || getPartFilePath(part);
            const srcW = (layer.src_width ?? layer.w ?? layer.width) != null ? getPartW(layer) : getPartW(part);
            const srcH = (layer.src_height ?? layer.h ?? layer.height) != null ? getPartH(layer) : getPartH(part);
            const srcX = (layer.src_x ?? null) != null ? getPartX(layer) : getPartX(part);
            const srcY = (layer.src_y ?? null) != null ? getPartY(layer) : getPartY(part);

            return {
                ...layer,
                x: clamp(Number(layer.x) || 0, 0, 127),
                y: clamp(Number(layer.y) || 0, 0, 63),
                w: srcW,
                h: srcH,
                src_x: srcX,
                src_y: srcY,
                src_width: srcW,
                src_height: srcH,
                sheet_file_path: path,
                zIndex: Number(layer.zIndex) || 1
            };
        }

        function clamp(value, min, max) {
            return Math.max(min, Math.min(max, value));
        }

        function renderPalette() {
            const palette = getEl('parts-palette');
            if (!palette) return;

            const groups = { eye: [], mouth: [], brow: [], accessory: [], other: [] };

            parts.forEach(function (part) {
                const category = part.category && Object.prototype.hasOwnProperty.call(groups, part.category)
                    ? part.category
                    : 'other';
                groups[category].push(part);
            });

            palette.innerHTML = '';

            Object.keys(groups).forEach(function (category) {
                const categoryParts = groups[category];
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-3';

                const title = document.createElement('h6');
                title.className = 'text-uppercase text-muted small fw-bold border-bottom pb-1 mb-2';
                title.textContent = categoryNames[category] || category;
                wrapper.appendChild(title);

                if (categoryParts.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted small ps-2 mb-2';
                    empty.textContent = 'パーツなし';
                    wrapper.appendChild(empty);
                }

                categoryParts.forEach(function (part) {
                    const row = document.createElement('div');
                    // 🌟 行全体をクリック可能（btn-add-part）にし、カーソルを pointer に設定
                    row.className = 'mb-2 d-flex align-items-center border p-1 rounded bg-light btn-add-part user-select-none';
                    row.style.cssText = 'cursor: pointer; transition: background-color 0.15s;';
                    row.setAttribute('data-part-id', String(part.id || part.name));

                    // ホバー時に背景色を変えてクリックできることを視覚化
                    row.addEventListener('mouseenter', function () { row.classList.replace('bg-light', 'bg-white'); });
                    row.addEventListener('mouseleave', function () { row.classList.replace('bg-white', 'bg-light'); });

                    const info = document.createElement('div');
                    info.className = 'd-flex align-items-center text-truncate w-100';
                    info.style.minWidth = '0';

                    const thumbBox = document.createElement('div');
                    thumbBox.className = 'me-2 rounded border bg-dark flex-shrink-0 d-flex align-items-center justify-content-center';
                    thumbBox.style.cssText = 'width:48px;height:48px;overflow:hidden;';

                    const thumb = document.createElement('div');
                    applyThumbStyle(thumb, part);
                    thumbBox.appendChild(thumb);

                    const textBox = document.createElement('div');
                    textBox.className = 'text-truncate';

                    const titleLine = document.createElement('div');
                    titleLine.className = 'd-flex align-items-center gap-1 mb-1';

                    const badge = document.createElement('span');
                    badge.className = 'badge bg-info text-dark';
                    badge.style.fontSize = '10px';
                    badge.textContent = part.category || 'other';

                    const name = document.createElement('strong');
                    name.className = 'text-truncate small';
                    name.textContent = part.name || '';

                    titleLine.appendChild(badge);
                    titleLine.appendChild(name);

                    const size = document.createElement('div');
                    size.className = 'text-muted';
                    size.style.fontSize = '11px';
                    size.textContent =
                        '(' + getPartX(part) + ', ' + getPartY(part) + ') ' +
                        getPartW(part) + '×' + getPartH(part) + 'px';

                    textBox.appendChild(titleLine);
                    textBox.appendChild(size);

                    info.appendChild(thumbBox);
                    info.appendChild(textBox);

                    row.appendChild(info);
                    wrapper.appendChild(row);
                });

                palette.appendChild(wrapper);
            });
        }

        function applyThumbStyle(element, part) {
            const path = getPartFilePath(part);
            const srcX = getPartX(part);
            const srcY = getPartY(part);
            const srcW = getPartW(part);
            const srcH = getPartH(part);
            const scale = Math.min(44 / srcW, 44 / srcH);

            if (path) {
                element.style.backgroundImage = "url('" + getImageUrl(path) + "')";
                element.style.backgroundPosition = '-' + srcX + 'px -' + srcY + 'px';
            }
            element.style.width = srcW + 'px';
            element.style.height = srcH + 'px';
            element.style.transform = 'scale(' + scale + ')';
            element.style.transformOrigin = 'center center';
            element.style.imageRendering = 'pixelated';
            element.style.backgroundRepeat = 'no-repeat';
        }

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-add-part');
            if (!btn) return;

            event.preventDefault();
            event.stopPropagation();

            const partId = btn.getAttribute('data-part-id');
            const part = parts.find(p => String(p.id) === String(partId) || String(p.name) === String(partId));
            if (part) {
                addPart(part);
            }
        });

        function addPart(part) {
            if (!part) return;

            if (!state.form.frames[state.currentFrame]) {
                state.form.frames[state.currentFrame] = { layers: [] };
            }

            const layers = getCurrentLayers();
            const w = getPartW(part);
            const h = getPartH(part);
            const path = getPartFilePath(part);

            const newLayer = {
                part_id: part.id ?? part.name ?? null,
                x: 16,
                y: 16,
                w: w,
                h: h,
                src_x: getPartX(part),
                src_y: getPartY(part),
                src_width: w,
                src_height: h,
                sheet_file_path: path,
                zIndex: layers.length + 1
            };

            layers.push(newLayer);
            state.selectedLayer = layers.length - 1;
            renderAll();
        }

        function renderFrames() {
            const frameList = getEl('frame-list');
            if (!frameList) return;

            frameList.innerHTML = '';

            state.form.frames.forEach(function (frame, index) {
                const frameButton = document.createElement('div');
                frameButton.className = 'border p-2 rounded text-center fw-bold';
                frameButton.style.width = '50px';
                frameButton.style.cursor = 'pointer';
                frameButton.textContent = '#' + (index + 1);

                if (state.currentFrame === index) {
                    frameButton.classList.add('border-primary', 'bg-primary', 'bg-opacity-10', 'text-primary');
                } else {
                    frameButton.classList.add('bg-light');
                }

                frameButton.addEventListener('click', function () {
                    state.currentFrame = index;
                    state.selectedLayer = null;
                    renderAll();
                });

                frameList.appendChild(frameButton);
            });
        }

        function renderCanvas() {
            const canvas = getEl('oled-canvas');
            const currentFrameLabel = getEl('current-frame-label');

            if (!canvas) return;

            canvas.querySelectorAll('.oled-layer').forEach(function (element) {
                element.remove();
            });

            if (currentFrameLabel) {
                currentFrameLabel.textContent = String(state.currentFrame + 1);
            }

            const layers = getCurrentLayers();

            layers.forEach(function (rawLayer, index) {
                const layer = normalizeLayer(rawLayer);
                state.form.frames[state.currentFrame].layers[index] = layer;

                const layerElement = document.createElement('div');
                layerElement.className = 'oled-layer position-absolute';
                layerElement.dataset.layerIndex = String(index);

                const posX = Number(layer.x) * 2;
                const posY = Number(layer.y) * 2;
                const width = Number(layer.w) * 2;
                const height = Number(layer.h) * 2;

                layerElement.style.cssText = `
                    position: absolute !important;
                    left: ${posX}px !important;
                    top: ${posY}px !important;
                    width: ${width}px !important;
                    height: ${height}px !important;
                    z-index: ${layer.zIndex || index + 10} !important;
                    cursor: move !important;
                    box-sizing: border-box !important;
                    border: 2px solid ${state.selectedLayer === index ? '#0d6efd' : '#ff0055'} !important;
                    background-color: rgba(13, 110, 253, 0.25) !important;
                    display: block !important;
                `;

                const image = document.createElement('div');
                image.style.cssText = `
                    width: ${layer.w}px !important;
                    height: ${layer.h}px !important;
                    transform: scale(2) !important;
                    transform-origin: top left !important;
                    image-rendering: pixelated !important;
                    background-repeat: no-repeat !important;
                    background-size: auto !important;
                    display: block !important;
                `;

                const path = getPartFilePath(layer);
                if (path) {
                    const url = getImageUrl(path);
                    image.style.backgroundImage = "url('" + url + "')";
                    image.style.backgroundPosition = '-' + layer.src_x + 'px -' + layer.src_y + 'px';
                } else {
                    image.style.backgroundColor = 'rgba(255, 0, 0, 0.5)';
                }

                layerElement.appendChild(image);

                layerElement.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    selectLayer(index);
                    beginDrag(event, index);
                });

                canvas.appendChild(layerElement);
            });

            renderPropertyPanel();
        }

        function renderPropertyPanel() {
            const propertyEmpty = getEl('property-empty');
            const propertyEditor = getEl('property-editor');
            const layerXInput = getEl('layer-x-input');
            const layerYInput = getEl('layer-y-input');

            const layers = getCurrentLayers();
            const layer = state.selectedLayer !== null ? layers[state.selectedLayer] : null;

            if (!layer) {
                propertyEmpty?.classList.remove('d-none');
                propertyEditor?.classList.add('d-none');
                return;
            }

            propertyEmpty?.classList.add('d-none');
            propertyEditor?.classList.remove('d-none');

            if (layerXInput) layerXInput.value = Number(layer.x) || 0;
            if (layerYInput) layerYInput.value = Number(layer.y) || 0;
        }

        function selectLayer(index) {
            if (!getCurrentLayers()[index]) return;
            state.selectedLayer = index;
            renderCanvas();
        }

        // 🌟 修正: 実行時に最新の入力フォーム要素を取得するように変更
        function updateSelectedLayerPosition() {
            if (state.selectedLayer === null) return;

            const layers = getCurrentLayers();
            const layer = layers[state.selectedLayer];
            if (!layer) return;

            const layerXInput = getEl('layer-x-input');
            const layerYInput = getEl('layer-y-input');

            if (layerXInput) layer.x = clamp(parseInt(layerXInput.value, 10) || 0, 0, 127);
            if (layerYInput) layer.y = clamp(parseInt(layerYInput.value, 10) || 0, 0, 63);

            renderCanvas();
        }

        function removeLayer() {
            if (state.selectedLayer === null) return;

            const layers = getCurrentLayers();
            if (!layers[state.selectedLayer]) return;

            layers.splice(state.selectedLayer, 1);
            state.selectedLayer = null;

            layers.forEach(function (layer, index) {
                layer.zIndex = index + 1;
            });

            renderAll();
        }

        function addFrame() {
            const currentLayers = JSON.parse(JSON.stringify(getCurrentLayers()));
            state.form.frames.push({ layers: currentLayers });
            state.currentFrame = state.form.frames.length - 1;
            state.selectedLayer = null;
            renderAll();
        }

        function beginDrag(event, index) {
            const layer = getCurrentLayers()[index];
            if (!layer) return;

            dragging = true;
            dragIndex = index;
            dragStartMouseX = event.clientX;
            dragStartMouseY = event.clientY;
            dragStartLayerX = Number(layer.x) || 0;
            dragStartLayerY = Number(layer.y) || 0;
        }

        // 🌟 修正: リアルタイム追従時にも最新の canvas を検索
        function onMouseMove(event) {
            if (!dragging || dragIndex === null) return;

            const layer = getCurrentLayers()[dragIndex];
            if (!layer) return;

            layer.x = clamp(
                dragStartLayerX + Math.round((event.clientX - dragStartMouseX) / 2),
                0,
                127
            );

            layer.y = clamp(
                dragStartLayerY + Math.round((event.clientY - dragStartMouseY) / 2),
                0,
                63
            );

            const canvas = getEl('oled-canvas');
            if (canvas) {
                const layerElement = canvas.querySelector('[data-layer-index="' + dragIndex + '"]');
                if (layerElement) {
                    layerElement.style.left = (layer.x * 2) + 'px';
                    layerElement.style.top = (layer.y * 2) + 'px';
                }
            }

            const layerXInput = getEl('layer-x-input');
            const layerYInput = getEl('layer-y-input');
            if (layerXInput) layerXInput.value = layer.x;
            if (layerYInput) layerYInput.value = layer.y;
        }

        function onMouseUp() {
            if (!dragging) return;
            dragging = false;
            dragIndex = null;
            renderPropertyPanel();
        }

        function handleKeyDown(event) {
            if (state.selectedLayer === null) return;

            const tag = document.activeElement ? document.activeElement.tagName : '';
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;

            const layer = getCurrentLayers()[state.selectedLayer];
            if (!layer) return;

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                layer.y = Math.max(0, Number(layer.y) - 1);
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                layer.y = Math.min(63, Number(layer.y) + 1);
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                layer.x = Math.max(0, Number(layer.x) - 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                layer.x = Math.min(127, Number(layer.x) + 1);
            } else {
                return;
            }

            renderCanvas();
        }

        function updateFormStateFromInputs() {
            const titleInput = getEl('title-input');
            const eventTypeInput = getEl('event-type-input');
            const intervalMsInput = getEl('interval-ms-input');
            const pointCostInput = getEl('point-cost-input');

            if (titleInput) state.form.title = titleInput.value;
            if (eventTypeInput) state.form.event_type = eventTypeInput.value;
            if (intervalMsInput) state.form.interval_ms = Number(intervalMsInput.value) || 150;
            if (pointCostInput) state.form.point_cost = Number(pointCostInput.value) || 0;
        }

        function saveFace() {
            updateFormStateFromInputs();
            const editorJsonInput = getEl('editor-json-input');
            const form = getEl('oled-form');

            if (editorJsonInput && form) {
                editorJsonInput.value = JSON.stringify(state.form);
                form.submit();
            }
        }

        function renderAll() {
            renderFrames();
            renderCanvas();
        }

        // 🌟 イベントリスナーも毎回最新DOMを取得して安全に登録（イベント委譲/動的アタッチ）
        document.addEventListener('click', function (event) {
            if (event.target.closest('#add-frame-btn')) {
                event.preventDefault();
                addFrame();
            } else if (event.target.closest('#remove-layer-btn')) {
                event.preventDefault();
                removeLayer();
            } else if (event.target.closest('#save-face-btn')) {
                event.preventDefault();
                saveFace();
            }
        });

        document.addEventListener('input', function (event) {
            if (event.target.id === 'layer-x-input' || event.target.id === 'layer-y-input') {
                updateSelectedLayerPosition();
            }
        });

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        document.addEventListener('keydown', handleKeyDown);

        renderPalette();
        renderAll();

        console.log("✅ [3/3] OLED Editor 正常に初期化完了しました");

    } catch (globalErr) {
        console.error("💥 [スクリプト全体エラー]:", globalErr);
    }
})();
</script>