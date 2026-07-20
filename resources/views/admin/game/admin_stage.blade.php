{{-- 🗺️ ステージ管理：ワークスペース本体 --}}
<div class="row m-0 p-0">
    
    {{-- 🧩 ① 左側：素材選択・レイヤーリスト (2/12列) --}}
    <div class="col-md-2 animate__animated animate__fadeIn ps-0 pe-2">
        
        {{-- 対象ステージ選択 (キャラクター管理の配置に合わせる) --}}
        <div class="p-2 mb-2 bg-dark text-white rounded border" style="font-size:12px;">
            <label class="form-label m-0 p-0 font-monospace small text-info fw-bold"><i class="bi bi-map-fill"></i> 対象ステージ</label>
            <select id="editor-stage-select" class="form-select form-select-sm font-monospace text-dark fw-bold mt-1">
                <option value="">-- 新規作成 --</option>
                @foreach($stages as $s)
                    <option value="{{ $s->id }}" 
                            data-stage='{{ json_encode($s) }}'
                            data-custom-settings='{{ json_encode($s->custom_settings) }}'>
                        No.{{ $s->number }}: {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- 素材切り替えパレット --}}
        @include('admin.game.parts.material_palette')

        {{-- 配置済みレイヤーリスト --}}
        <div class="card mb-0 shadow-sm border-success">
            <div class="card-header bg-success text-white small fw-bold py-2">
                <i class="bi bi-layers-fill me-1"></i> ② 配置済みレイヤー
            </div>
            <div class="card-body bg-light p-2 shadow-inner">
                <div id="placed_layers_list" style="max-height: 350px; overflow-y: auto;">
                    <div class="text-muted small text-center py-2" style="font-size:10px;">レイヤーがありません</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🎨 ② 中央：メインエディタ ＆ プレビュー (7/12列) --}}
    <div class="col-md-7 animate__animated animate__fadeIn px-1">
        <div class="card mb-3 shadow-sm border-secondary" id="stage-card-wrapper">
            <div class="card-header bg-dark text-white py-2 small fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-1"></i> セットアップモード: <span id="header-stage-name" class="text-info">新規作成</span></span>
                <button type="button" class="btn btn-xs btn-outline-light d-none" id="btn-reset-stage-mode" onclick="setStageCreateMode()">新規作成に戻る</button>
            </div>
            
            <div class="card-body p-0 bg-secondary position-relative overflow-hidden" style="height: 600px;">
                <div id="stage_preview_container" class="position-absolute w-100 h-100" style="cursor: crosshair; background-image: 
                    linear-gradient(45deg, #333 25%, transparent 25%), 
                    linear-gradient(-45deg, #333 25%, transparent 25%), 
                    linear-gradient(45deg, transparent 75%, #333 75%), 
                    linear-gradient(-45deg, transparent 75%, #333 75%);
                    background-size: 20px 20px;
                    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;">
                    
                    {{-- ガイド線 --}}
                    <div style="position: absolute; left: 50%; top: 0; width: 1px; height: 100%; background: rgba(0,255,255,0.15); pointer-events: none; z-index: 1000;"></div>
                    <div style="position: absolute; left: 0; top: 50%; width: 100%; height: 1px; background: rgba(0,255,255,0.15); pointer-events: none; z-index: 1000;"></div>
                    
                    <div id="guide-width-x" style="position: absolute; top: 0; width: 0; height: 100%; border-left: 3px dashed rgba(13, 202, 240, 0.7); pointer-events: none; z-index: 1001;"></div>
                    <span id="lbl-guide-width" style="position: absolute; top: 10px; color: #0dcaf0; font-size: 10px; font-weight: bold; pointer-events: none; z-index: 1001;">📏 Width</span>

                    <div id="guide-height-y" style="position: absolute; left: 0; width: 100%; height: 0; border-top: 3px dashed rgba(220, 53, 69, 0.7); pointer-events: none; z-index: 1001;"></div>
                    <span id="lbl-guide-height" style="position: absolute; left: 10px; color: #dc3545; font-size: 10px; font-weight: bold; pointer-events: none; z-index: 1001;">📏 Height</span>

                    <div id="guide-ground-y" style="position: absolute; left: 0; width: 100%; height: 0; border-top: 3px solid rgba(255, 193, 7, 0.9); pointer-events: none; z-index: 1002;"></div>
                    <span id="lbl-guide-ground" style="position: absolute; left: 10px; color: #ffc107; font-size: 10px; font-weight: bold; pointer-events: none; z-index: 1002;">👠 Ground Y</span>

                    <div id="preview_layers_root" style="position: absolute; width: 100%; height: 100%; left: 0; top: 0;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🛠️ ③ 右側：プロパティ ＆ JSON (3/12列) --}}
    <div class="col-md-3 animate__animated animate__fadeIn ps-2 pe-0">
        
        <form id="stage_change_form" method="POST" action="{{ route('admin.game.stage.update') }}">
            @csrf
            <input type="hidden" name="game_key" value="{{ $gameKey }}">
            <input type="hidden" id="stage_id" name="id" value="">

            <div class="card mb-3 shadow-sm border-dark">
                <div class="card-header bg-dark text-white py-2 small fw-bold">
                    <i class="bi bi-sliders"></i> コントロール ＆ プロパティ
                </div>
                <div class="card-body p-2 bg-light">
                    
                    {{-- ステージ基本情報 --}}
                    <div class="p-2 mb-2 bg-dark text-white rounded border" style="font-size:11px;">
                        <span class="text-info font-monospace fw-bold"><i class="bi bi-gear-fill"></i> ステージ基本設定</span>
                        <div class="row g-1 mt-1">
                            <div class="col-4">
                                <label class="form-label m-0 p-0 text-info">No.</label>
                                <input type="number" id="stage_number" name="number" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center" min="1" required>
                            </div>
            <div class="col-8">
                <label class="form-label m-0 p-0 text-info">名称</label>
                <input type="text" id="stage_name" name="name" class="form-control form-control-sm bg-secondary text-white border-0 py-0" required>
            </div>
            <div class="col-12">
                <label class="form-label m-0 p-0 text-info">使用マップ</label>
                <select id="stage_map_id" name="map_id" class="form-select form-select-sm bg-secondary text-white border-0 py-0">
                    <option value="">-- マップなし --</option>
                    @foreach($maps as $m)
                        <option value="{{ $m->id }}" data-custom-settings='{{ json_encode($m->custom_settings) }}'>{{ $m->name }} ({{ $m->map_key }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label m-0 p-0 text-info">タイプ</label>
                                <select id="stage_type" name="type" class="form-select form-select-sm bg-secondary text-white border-0 py-0">
                                    <option value="fixed">fixed</option>
                                    <option value="horizontal">horizontal</option>
                                    <option value="vertical">vertical</option>
                                </select>
                            </div>
                            <div class="col-6 text-center">
                                <div class="form-check form-switch d-inline-block mt-3">
                                    <input class="form-check-input" type="checkbox" name="enable_flag" value="1" id="stage-enable">
                                    <label class="form-check-label text-success fw-bold" for="stage-enable" style="font-size:10px;">公開</label>
                                </div>
                            </div>
                            <div class="col-4 mt-1">
                                <label class="form-label m-0 p-0 text-info fw-bold">Width</label>
                                <input type="number" id="base_width" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center fw-bold">
                            </div>
                            <div class="col-4 mt-1">
                                <label class="form-label m-0 p-0 text-danger fw-bold">Height</label>
                                <input type="number" id="base_height" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center fw-bold">
                            </div>
                            <div class="col-4 mt-1">
                                <label class="form-label m-0 p-0 text-warning fw-bold">Ground Y</label>
                                <input type="number" id="base_ground_y" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center fw-bold">
                            </div>
                        </div>
                    </div>

                    {{-- パーツプロパティ --}}
                    <div id="part-properties-box" class="p-2 bg-white rounded border border-secondary shadow-sm mb-2" style="font-size:11px;">
                        <div class="fw-bold text-secondary font-monospace small mb-1 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-pencil-square"></i> 選択中のパーツ設定</span>
                            <button type="button" class="btn btn-xxs btn-outline-danger d-none" id="btn-delete-layer" onclick="deleteSelectedLayer()">削除</button>
                        </div>
                        <div class="row g-1">
                            <div class="col-12">
                                <label class="form-label small m-0 text-muted">キー: <b class="text-primary" id="lbl-selected-key">なし</b></label>
                            </div>
                            <div class="col-4">
                                <label class="form-label small m-0 text-primary">X</label>
                                <input type="number" id="prop_x" class="form-control form-control-sm py-0 text-center">
                            </div>
                            <div class="col-4">
                                <label class="form-label small m-0 text-primary">Y</label>
                                <input type="number" id="prop_y" class="form-control form-control-sm py-0 text-center">
                            </div>
                            <div class="col-4">
                                <label class="form-label small m-0 text-primary">幅 (W)</label>
                                <input type="number" id="prop_w" class="form-control form-control-sm py-0 text-center">
                            </div>
                            <div class="col-6">
                                <label class="form-label small m-0 text-primary">Depth (Z)</label>
                                <input type="number" id="prop_depth" class="form-control form-control-sm py-0 text-center">
                            </div>
                            <div class="col-6">
                                <label class="form-label small m-0 text-primary">Scroll (X)</label>
                                <input type="number" id="prop_scroll" class="form-control form-control-sm py-0 text-center" step="0.1">
                            </div>
                        </div>
                    </div>

                    {{-- JSON & 保存 --}}
                    <div class="border rounded bg-white p-2 shadow-inner">
                        <div class="mb-1 small font-monospace fw-bold text-success d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-filetype-json"></i> ③ 構造データ JSON</span>
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-xs btn-success fw-bold">保存</button>
                                <button type="button" class="btn btn-xs btn-danger fw-bold" onclick="location.href='{{ route('admin.game.publish', ['gameKey' => $gameKey]) }}'">反映</button>
                            </div>
                        </div>
                        <textarea id="custom_settings_json" name="custom_settings_json" class="form-control form-control-sm font-monospace bg-dark text-success border-0" 
                                  style="height: 250px; font-size: 10px; tab-size: 2;" placeholder='{"base":{}, "layers": []}'></textarea>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window.SPRITE_SHEET_BASE_URL = "{{ asset('storage/sprite_sheet') }}/";

    const form = document.getElementById('stage_change_form');
    const jsonTextarea = document.getElementById('custom_settings_json');
    const root = document.getElementById('preview_layers_root');
    const placedList = document.getElementById('placed_layers_list');
    const hStageName = document.getElementById('header-stage-name');
    const stageSelect = document.getElementById('editor-stage-select');

    // グリッド設定
    const gridSize = 32;

    // 基本設定項目
    const inpBaseW = document.getElementById('base_width');
    const inpBaseH = document.getElementById('base_height');
    const inpBaseGY = document.getElementById('base_ground_y');
    
    const guideWidthX = document.getElementById('guide-width-x');
    const lblWidth = document.getElementById('lbl-guide-width');
    const guideHeightY = document.getElementById('guide-height-y');
    const lblHeight = document.getElementById('lbl-guide-height');
    const guideGroundY = document.getElementById('guide-ground-y');
    const lblGround = document.getElementById('lbl-guide-ground');

    // パーツプロパティ項目
    const inpPropX = document.getElementById('prop_x');
    const inpPropY = document.getElementById('prop_y');
    const inpPropW = document.getElementById('prop_w');
    const inpPropDepth = document.getElementById('prop_depth');
    const inpPropScroll = document.getElementById('prop_scroll');

    let isDragging = false;
    let dragTarget = null;
    let dragLayerIdx = -1;
    let startX, startY, initialX, initialY;
    let selectedLayerIdx = -1;
    let selectedEntityType = 'layer'; // 'layer' or 'entity'


    /**
     * 🌟 定義済みグリッドパーツをステージに追加
     */
    window.addGridPartToStage = function(itemId) {
        const el = document.querySelector(`.grid-part-item[onclick="addGridPartToStage(${itemId})"]`);
        if (!el) return;
        const item = JSON.parse(el.dataset.item);

        let data = JSON.parse(jsonTextarea.value || '{"base":{},"layers":[],"entities":[]}');
        if (!data.entities) data.entities = [];
        
        const newIdx = data.entities.length;
        data.entities.push({
            "type": item.type,
            "item_id": item.id,
            "key": item.item_key,
            "atlas": item.sprite_sheet ? item.sprite_sheet.filename : '',
            "frame": item.atlas_frame,
            "x": 0, "y": 0,
            "grid_w": item.grid_w,
            "grid_h": item.grid_h,
            "depth": 10
        });

        jsonTextarea.value = JSON.stringify(data, null, 2);
        updatePreview();
        setTimeout(() => {
            const els = root.querySelectorAll('.entity-element');
            if (els[newIdx]) selectLayer(newIdx, els[newIdx], 'entity');
        }, 50);
    };

    function addLayerFromPalette(frame, atlas, category) {
        let data = JSON.parse(jsonTextarea.value || '{"base":{},"layers":[]}');
        if (!data.layers) data.layers = [];
        const newIdx = data.layers.length;
        data.layers.push({
            "key": frame.filename, "atlas": atlas, "category": category,
            "x": 100, "y": 100, "w": frame.frame.w, "depth": newIdx, "scrollFactorX": 1.0
        });
        jsonTextarea.value = JSON.stringify(data, null, 2);
        updatePreview();
        setTimeout(() => {
            const els = root.querySelectorAll('.layer-element');
            if (els[newIdx]) selectLayer(newIdx, els[newIdx]);
        }, 50);
    }

    window.selectLayer = function(idx, el, type = 'layer') {
        selectedLayerIdx = idx;
        selectedEntityType = type;
        const data = JSON.parse(jsonTextarea.value || '{"layers":[],"entities":[]}');
        const layer = (type === 'layer') ? (data.layers ? data.layers[idx] : null) : (data.entities ? data.entities[idx] : null);
        
        root.querySelectorAll('.layer-element, .entity-element').forEach(item => item.classList.remove('border', 'border-primary', 'shadow', 'active-layer'));
        if (el && layer) {
            el.classList.add('border', 'border-primary', 'shadow', 'active-layer');
            document.getElementById('lbl-selected-key').textContent = layer.key;
            document.getElementById('btn-delete-layer').classList.remove('d-none');
            inpPropX.value = layer.x || 0; inpPropY.value = layer.y || 0; 
            inpPropW.value = (type === 'layer' ? (layer.w || 0) : (layer.grid_w * gridSize));
            inpPropDepth.value = layer.depth || 0; inpPropScroll.value = layer.scrollFactorX || 1.0;
        } else {
            document.getElementById('lbl-selected-key').textContent = "なし";
            document.getElementById('btn-delete-layer').classList.add('d-none');
            inpPropX.value = ''; inpPropY.value = ''; inpPropW.value = ''; inpPropDepth.value = ''; inpPropScroll.value = '';
        }
    }

    window.deleteSelectedLayer = function() {
        if (selectedLayerIdx === -1) return;
        if (!confirm('選択中の項目を削除しますか？')) return;
        
        let data = JSON.parse(jsonTextarea.value || '{"layers":[],"entities":[]}');
        if (selectedEntityType === 'layer') {
            if (data.layers) data.layers.splice(selectedLayerIdx, 1);
        } else {
            if (data.entities) data.entities.splice(selectedLayerIdx, 1);
        }
        jsonTextarea.value = JSON.stringify(data, null, 2);
        selectedLayerIdx = -1; selectLayer(-1, null); updatePreview();
    }

    window.updatePreview = function() {
        root.innerHTML = ''; if (placedList) placedList.innerHTML = '';
        try {
            const data = JSON.parse(jsonTextarea.value || '{"base":{},"layers":[],"entities":[]}');
            const layers = data.layers || [];
            const entities = data.entities || [];
            const base = data.base || {width:800, height:600, groundY:500};
            inpBaseW.value = base.width; inpBaseH.value = base.height; inpBaseGY.value = base.groundY;
            guideWidthX.style.left = base.width + 'px'; lblWidth.style.left = (base.width + 5) + 'px';
            guideHeightY.style.top = base.height + 'px'; lblHeight.style.top = (base.height - 15) + 'px';
            guideGroundY.style.top = base.groundY + 'px'; lblGround.style.top = (base.groundY + 4) + 'px';

            // マップデータの統合描画
            const mapSelect = document.getElementById('stage_map_id');
            if (mapSelect && mapSelect.value) {
                const mapOpt = mapSelect.options[mapSelect.selectedIndex];
                const mapSettings = JSON.parse(mapOpt.dataset.customSettings || '{"layers":[]}');
                if (mapSettings.layers) {
                    mapSettings.layers.forEach((mLayer) => {
                        const mEl = createLayerElement(mLayer, -1); // マップレイヤーは編集不可扱い(idx=-1)
                        if (mEl) {
                            mEl.style.opacity = "0.7"; // マップレイヤーであることを示すために少し薄くする
                            mEl.style.pointerEvents = "none"; // ステージ編集画面ではマップレイヤーは触れない
                            root.appendChild(mEl);
                        }
                    });
                }
            }

            layers.forEach((layer, idx) => {
                if (placedList) {
                    const item = document.createElement('div');
                    const isActive = (selectedEntityType === 'layer' && selectedLayerIdx === idx);
                    item.className = `d-flex align-items-center justify-content-between p-1 px-2 mb-1 border rounded ${isActive ? 'bg-primary bg-opacity-10 border-primary' : 'bg-white'} shadow-sm`;
                    item.style = "cursor:pointer; font-size:10px;";
                    item.innerHTML = `<div class="text-truncate" style="max-width:100px;"><span class="badge bg-secondary me-1">L${idx}</span>${layer.key}</div>
                                      <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="event.stopPropagation(); removeLayer(${idx})"><i class="bi bi-x-circle-fill"></i></button>`;
                    item.onclick = () => selectLayer(idx, root.querySelectorAll('.layer-element')[idx], 'layer');
                    placedList.appendChild(item);
                }
                const el = createLayerElement(layer, idx, 'layer');
                if (el) root.appendChild(el);
            });

            entities.forEach((entity, idx) => {
                if (placedList) {
                    const item = document.createElement('div');
                    const isActive = (selectedEntityType === 'entity' && selectedLayerIdx === idx);
                    item.className = `d-flex align-items-center justify-content-between p-1 px-2 mb-1 border rounded ${isActive ? 'bg-info bg-opacity-10 border-info' : 'bg-white'} shadow-sm`;
                    item.style = "cursor:pointer; font-size:10px;";
                    item.innerHTML = `<div class="text-truncate" style="max-width:100px;"><span class="badge bg-info text-dark me-1">E${idx}</span>${entity.key}</div>
                                      <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="event.stopPropagation(); removeEntity(${idx})"><i class="bi bi-x-circle-fill"></i></button>`;
                    item.onclick = () => selectLayer(idx, root.querySelectorAll('.entity-element')[idx], 'entity');
                    placedList.appendChild(item);
                }
                const el = createLayerElement(entity, idx, 'entity');
                if (el) root.appendChild(el);
            });
        } catch(e) {}
    }

    function createLayerElement(layer, idx, type = 'layer') {
        const el = document.createElement('div');
        el.className = `position-absolute ${type}-element shadow-sm`;
        if (idx !== -1 && selectedEntityType === type && selectedLayerIdx === idx) el.classList.add('border', 'border-primary', 'shadow', 'active-layer');
        el.style.left = (layer.x || 0) + 'px'; el.style.top = (layer.y || 0) + 'px'; el.style.zIndex = layer.depth || 0;
        
        const selector = document.getElementById('atlas_selector');
        if (!selector) return null;

        const atlasOption = Array.from(selector.options).find(opt => opt.value === layer.atlas);
        if (atlasOption) {
            const atlasData = JSON.parse(atlasOption.dataset.atlas || '{}');
            const frameData = (atlasData.textures && atlasData.textures[0]) 
                ? atlasData.textures[0].frames.find(f => f.filename === (type === 'layer' ? layer.key : (layer.frame || layer.key)))
                : null;
            if (frameData) {
                const f = frameData.frame; 
                let drawW = (type === 'layer') ? (layer.w || f.w) : (layer.grid_w * gridSize);
                const scaleX = drawW / f.w;
                el.style.width = drawW + 'px'; el.style.height = (f.h * scaleX) + 'px';
                el.style.backgroundImage = `url(${window.SPRITE_SHEET_BASE_URL}${layer.category}/${layer.atlas})`;
                el.style.backgroundPosition = `-${f.x * scaleX}px -${f.y * scaleX}px`;
                el.style.backgroundSize = `${atlasData.textures[0].size.w * scaleX}px ${atlasData.textures[0].size.h * scaleX}px`;
            }
        } else { 
            el.style.width = (layer.w || 64) + "px"; el.style.height = "64px"; el.style.border = "1px dashed #fff"; el.style.background = "rgba(255,255,255,0.1)"; 
        }

        if (idx !== -1) {
            el.style.cursor = "grab";
            el.addEventListener('mousedown', (e) => { 
                selectLayer(idx, el, type); 
                isDragging = true; dragTarget = el; dragLayerIdx = idx; 
                startX = e.clientX; startY = e.clientY; 
                initialX = parseInt(el.style.left) || 0; initialY = parseInt(el.style.top) || 0; 
                el.style.cursor = "grabbing"; e.stopPropagation(); e.preventDefault(); 
            });
        }
        return el;
    }

    window.addEventListener('mousemove', (e) => {
        if (!isDragging || dragTarget === null) return;
        let curX = initialX + (e.clientX - startX); 
        let curY = initialY + (e.clientY - startY);
        
        if (selectedEntityType === 'entity') {
            curX = Math.round(curX / gridSize) * gridSize;
            curY = Math.round(curY / gridSize) * gridSize;
        }

        dragTarget.style.left = curX + 'px'; dragTarget.style.top = curY + 'px';
        inpPropX.value = curX; inpPropY.value = curY;
    });

    window.addEventListener('mouseup', () => { if (!isDragging) return; updateJsonFromForm(); isDragging = false; dragTarget = null; updatePreview(); });

    function updateJsonFromForm() {
        try {
            let data = JSON.parse(jsonTextarea.value || '{"base":{},"layers":[],"entities":[]}');
            data.base = { width: parseInt(inpBaseW.value) || 800, height: parseInt(inpBaseH.value) || 600, groundY: parseInt(inpBaseGY.value) || 500 };
            
            if (selectedLayerIdx !== -1) {
                if (selectedEntityType === 'layer' && data.layers && data.layers[selectedLayerIdx]) {
                    const l = data.layers[selectedLayerIdx];
                    l.x = parseInt(inpPropX.value) || 0; l.y = parseInt(inpPropY.value) || 0; l.w = parseInt(inpPropW.value) || 0;
                    l.depth = parseInt(inpPropDepth.value) || 0; l.scrollFactorX = parseFloat(inpPropScroll.value) || 1.0;
                } else if (selectedEntityType === 'entity' && data.entities && data.entities[selectedLayerIdx]) {
                    const e = data.entities[selectedLayerIdx];
                    e.x = parseInt(inpPropX.value) || 0; e.y = parseInt(inpPropY.value) || 0;
                    e.depth = parseInt(inpPropDepth.value) || 0;
                }
            }
            jsonTextarea.value = JSON.stringify(data, null, 2);
        } catch(e) {}
    }

    [inpBaseW, inpBaseH, inpBaseGY, inpPropX, inpPropY, inpPropW, inpPropDepth, inpPropScroll].forEach(inp => {
        inp.addEventListener('input', () => { updateJsonFromForm(); updatePreview(); });
    });

    window.removeLayer = function(idx) {
        if (!confirm('このレイヤーを削除しますか？')) return;
        let data = JSON.parse(jsonTextarea.value || '{"layers":[],"entities":[]}');
        if (data.layers) data.layers.splice(idx, 1); jsonTextarea.value = JSON.stringify(data, null, 2);
        selectedLayerIdx = -1; selectLayer(-1, null); updatePreview();
    }

    window.removeEntity = function(idx) {
        if (!confirm('このエンティティを削除しますか？')) return;
        let data = JSON.parse(jsonTextarea.value || '{"layers":[],"entities":[]}');
        if (data.entities) data.entities.splice(idx, 1); jsonTextarea.value = JSON.stringify(data, null, 2);
        selectedLayerIdx = -1; selectLayer(-1, null); updatePreview();
    }

    // ステージセレクトボックス連動
    if (stageSelect) {
        stageSelect.addEventListener('change', function() {
            if (!this.value) {
                setStageCreateMode();
                return;
            }
            const opt = this.options[this.selectedIndex];
            const stage = JSON.parse(opt.dataset.stage || '{}');
            const customSettings = JSON.parse(opt.dataset.customSettings || '{}');

            form.querySelector('#stage_id').value = stage.id;
            form.querySelector('#stage_number').value = stage.number;
            form.querySelector('#stage_name').value = stage.name;
            form.querySelector('#stage_map_id').value = stage.map_id || '';
            form.querySelector('#stage_type').value = stage.type;
            form.querySelector('#stage-enable').checked = stage.enable_flag === 1;
            
            if (!customSettings.base) customSettings.base = {width:800, height:600, groundY:500};
            if (!customSettings.layers) customSettings.layers = [];
            
            // ★ ステージにマップが紐づいている場合、そのマップデータを読み込む
            if (stage.map_id) {
                const mapSelect = document.getElementById('stage_map_id');
                // map_id に対応するマップ名などを表示に反映させる（必要なら）
            }

            jsonTextarea.value = JSON.stringify(customSettings, null, 2);
            hStageName.textContent = stage.name;
            
            document.getElementById('stage-card-wrapper').classList.replace('border-secondary', 'border-primary');
            document.getElementById('btn-reset-stage-mode').classList.remove('d-none');
            
            selectedLayerIdx = -1;
            selectLayer(-1, null);
            updatePreview();

            // ★ 自動反映: JSONの内容をパースしてプレビューを更新
            if (window.updatePreview) {
                setTimeout(window.updatePreview, 100);
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    window.setStageCreateMode = function() {
        form.reset(); form.querySelector('#stage_id').value = '';
        if (stageSelect) stageSelect.value = '';
        jsonTextarea.value = JSON.stringify({base:{width:800,height:600,groundY:500},layers:[]}, null, 2);
        hStageName.textContent = "新規作成";
        document.getElementById('stage-card-wrapper').classList.replace('border-primary', 'border-secondary');
        document.getElementById('btn-reset-stage-mode').classList.add('d-none');
        selectedLayerIdx = -1; updatePreview();
    }
    // マップ選択時のプレビュー更新
    const stageMapSelect = document.getElementById('stage_map_id');
    if (stageMapSelect) {
        stageMapSelect.addEventListener('change', updatePreview);
    }

    jsonTextarea.addEventListener('input', updatePreview); updatePreview();
});

/**
 * 🌟 プレビュー表示の拡張：グリッドガイドの描画
 */
function drawGridGuide() {
    const container = document.getElementById('stage_preview_container');
    if (!container) return;
    let grid = document.getElementById('preview-grid-overlay');
    if (!grid) {
        grid = document.createElement('div');
        grid.id = 'preview-grid-overlay';
        grid.className = 'position-absolute w-100 h-100 top-0 left-0';
        grid.style.pointerEvents = 'none';
        grid.style.zIndex = '500';
        grid.style.backgroundImage = `
            linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px)
        `;
        grid.style.backgroundSize = `32px 32px`;
        container.appendChild(grid);
    }
}
document.addEventListener('DOMContentLoaded', drawGridGuide);
</script>
<style>
.layer-element.active-layer, .entity-element.active-layer { 
    outline: 3px solid #007bff !important; 
    outline-offset: 2px !important; 
    box-shadow: 0 0 15px rgba(0, 123, 255, 0.7) !important;
    z-index: 9999 !important;
}
.btn-xxs { padding: 1px 4px; font-size: 9px; line-height: 1.2; }
</style>
