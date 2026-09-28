<div class="container-fluid py-2" id="oled-editor-app">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3>{{ isset($face) ? 'OLEDフェイス編集' : 'OLEDフェイス新規作成' }}</h3>
            <div>
                <a href="{{ route('admin.oled.index') }}" class="btn btn-secondary">戻る</a>
                <button type="button" class="btn btn-success" @click="saveFace">保存する</button>
            </div>
        </div>
    </div>
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form id="oled-form" action="{{ isset($face) ? route('admin.oled.update', $face->id) : route('admin.oled.store') }}" method="POST">
        @csrf
        @if(isset($face)) @method('PUT') @endif
        <input type="hidden" name="editor_json" v-model="editorJsonString">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">タイトル</label>
                <input type="text" name="title" class="form-control" v-model="form.title" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">イベントキー (event_type)</label>
                <input type="text" name="event_type" class="form-control" v-model="form.event_type" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">コマ速度 (ms)</label>
                <input type="number" name="interval_ms" class="form-control" v-model.number="form.interval_ms" min="20" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">ポイント</label>
                <input type="number" name="point_cost" class="form-control" v-model.number="form.point_cost" min="0" required>
            </div>
        </div>
    </form>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white">パーツパレット (カテゴリ別)</div>
                <div class="card-body p-2" style="max-height: 350px; overflow-y: auto;">
                    <div v-for="(categoryParts, catName) in partsByCategory" :key="catName" class="mb-3" v-if="Object.keys(partsByCategory).length > 0">
                        <h6 class="text-uppercase text-muted small fw-bold border-bottom pb-1 mb-2">@{{ catName }}</h6>
                        <div v-if="categoryParts.length === 0" class="text-muted small ps-2 mb-2">パーツなし</div>
                        <div class="mb-2 d-flex justify-content-between align-items-center border p-1 rounded bg-light" v-for="part in categoryParts" :key="part.id">
                            <div class="d-flex align-items-center">
                                <img :src="'/' + part.file_path" style="width: 28px; height: 28px; background:#222; image-rendering: pixelated;" class="me-2 rounded border">
                                <div>
                                    <div class="small fw-bold">@{{ part.name }}</div>
                                    <div class="text-muted" style="font-size: 10px;">@{{ part.src_width }}×@{{ part.src_height }}px</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 11px;" @click="addPart(part)">追加</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <span>コマ一覧</span>
                    <button type="button" class="btn btn-sm btn-light" @click="addFrame">+ 追加</button>
                </div>
                <div class="card-body p-2 d-flex gap-2 flex-wrap">
                    <div v-for="(f, i) in form.frames" :key="i" @click="currentFrame = i" 
                         class="border p-2 rounded text-center" :class="{'border-primary bg-light': currentFrame === i}" style="width: 50px; cursor: pointer;">
                        #@{{ i + 1 }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">キャンバス (128x64) - #@{{ currentFrame + 1 }}</div>
                <div class="card-body d-flex justify-content-center align-items-center bg-secondary bg-opacity-25 p-3">
                    <div class="position-relative border" style="width: 256px; height: 128px; overflow: hidden; background-color: #eee; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0px;"
                         @mousemove="onMouseMove" @mouseup="onMouseUp" @mouseleave="onMouseUp">
                        <div v-for="(layer, lIdx) in currentLayers" :key="lIdx"
                             class="position-absolute"
                             :style="{ left: (layer.x * 2) + 'px', top: (layer.y * 2) + 'px', width: (layer.w * 2) + 'px', height: (layer.h * 2) + 'px', zIndex: layer.zIndex, outline: selectedLayer === lIdx ? '2px solid blue' : 'none' }"
                             @mousedown.stop="selectLayer(lIdx, $event)">
                            <img :src="'/' + getPartPath(layer.part_id)" style="image-rendering: pixelated;" class="w-100 h-100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">プロパティ</div>
                <div class="card-body p-2" v-if="selectedLayer !== null && currentLayers[selectedLayer]">
                    <div class="mb-2">
                        <label class="small">X (0-127)</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="currentLayers[selectedLayer].x" min="0" max="127">
                    </div>
                    <div class="mb-2">
                        <label class="small">Y (0-63)</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="currentLayers[selectedLayer].y" min="0" max="63">
                    </div>
                    <button type="button" class="btn btn-sm btn-danger w-100 mt-2" @click="removeLayer">削除</button>
                </div>
                <div class="card-body text-muted small" v-else>パーツを選択してください。</div>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script>
    const { createApp, ref, reactive, computed, onMounted, onUnmounted } = Vue;
    createApp({
        setup() {
            const parts = @json($parts ?? []);
            const existing = @json($face ?? null);
            const form = reactive({
                title: existing ? existing.title : '',
                event_type: existing ? existing.event_type : '',
                interval_ms: existing ? existing.interval_ms : 150,
                point_cost: existing ? existing.point_cost : 0,
                frames: existing && existing.editor_json && existing.editor_json.frames ? existing.editor_json.frames : [{ layers: [] }]
            });
            const currentFrame = ref(0);
            const selectedLayer = ref(null);

            const currentLayers = computed(() => form.frames[currentFrame.value].layers);
            const editorJsonString = computed(() => JSON.stringify(form));

            const partsByCategory = computed(() => {
                const groups = { eye: [], mouth: [], brow: [], accessory: [], other: [] };
                parts.forEach(p => {
                    if (groups[p.category]) {
                        groups[p.category].push(p);
                    } else {
                        groups['other'].push(p);
                    }
                });
                return groups;
            });

            function getPartPath(id) {
                const p = parts.find(x => x.id === id);
                return p ? p.file_path : '';
            }
            function addPart(part) {
                currentLayers.value.push({ 
                    part_id: part.id, 
                    x: 16, 
                    y: 16, 
                    w: part.src_width || 16, 
                    h: part.src_height || 16, 
                    zIndex: currentLayers.value.length + 1 
                });
                selectedLayer.value = currentLayers.value.length - 1;
            }
            function selectLayer(idx, e) {
                selectedLayer.value = idx;
                initDrag(e, idx);
            }

            let dragging = false, dIdx = null, sx = 0, sy = 0, lx = 0, ly = 0;
            function initDrag(e, idx) {
                dragging = true; dIdx = idx; sx = e.clientX; sy = e.clientY;
                lx = currentLayers.value[idx].x; ly = currentLayers.value[idx].y;
            }
            function onMouseMove(e) {
                if (!dragging || dIdx === null) return;
                currentLayers.value[dIdx].x = Math.max(0, Math.min(127, lx + Math.round((e.clientX - sx) / 2)));
                currentLayers.value[dIdx].y = Math.max(0, Math.min(63, ly + Math.round((e.clientY - sy) / 2)));
            }
            function onMouseUp() { dragging = false; dIdx = null; }
            function removeLayer() {
                if (selectedLayer.value !== null) {
                    currentLayers.value.splice(selectedLayer.value, 1);
                    selectedLayer.value = null;
                }
            }
            function addFrame() {
                form.frames.push({ layers: JSON.parse(JSON.stringify(currentLayers.value)) });
                currentFrame.value = form.frames.length - 1;
            }
            function saveFace() { document.getElementById('oled-form').submit(); }

            // 矢印キーでの1px単位の精密移動対応
            function handleKeyDown(e) {
                if (selectedLayer.value === null || !currentLayers.value[selectedLayer.value]) return;
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;

                let layer = currentLayers.value[selectedLayer.value];
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    layer.y = Math.max(0, layer.y - 1);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    layer.y = Math.min(63, layer.y + 1);
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    layer.x = Math.max(0, layer.x - 1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    layer.x = Math.min(127, layer.x + 1);
                }
            }

            onMounted(() => {
                window.addEventListener('keydown', handleKeyDown);
            });
            onUnmounted(() => {
                window.removeEventListener('keydown', handleKeyDown);
            });

            return { parts, partsByCategory, form, currentFrame, selectedLayer, currentLayers, editorJsonString, getPartPath, addPart, selectLayer, onMouseMove, onMouseUp, removeLayer, addFrame, saveFace };
        }
    }).mount('#oled-editor-app');
</script>

