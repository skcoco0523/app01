{{-- 🧩 共通素材パレット --}}
<div class="card mb-2 shadow-sm border-primary">
    <div class="card-header bg-primary text-white small fw-bold py-2 d-flex justify-content-between align-items-center" 
         data-bs-toggle="collapse" 
         data-bs-target="#materialPaletteCollapse" 
         role="button" 
         style="user-select: none; cursor: pointer;">
        <span><i class="bi bi-plus-circle-fill me-1"></i> ① 素材を切り替え</span>
        <i class="bi bi-chevron-down"></i>
    </div>
    <div id="materialPaletteCollapse" class="collapse show">
        <div class="card-body bg-light p-2">
            <select id="atlas_selector" class="form-select form-select-sm mb-2 border-primary fw-bold font-monospace" style="font-size: 11px;">
                <option value="">-- 素材を選択 --</option>
                {{-- マップ/ステージ管理用 --}}
                @if(isset($spriteSheets))
                    @foreach($spriteSheets as $ss)
                        @if(!empty($ss->pixel_data))
                            <option value="{{ $ss->filename }}" 
                                    data-atlas='{{ json_encode($ss->pixel_data) }}'
                                    data-category="{{ $ss->category }}">
                                📁 {{ $ss->filename }}
                            </option>
                        @endif
                    @endforeach
                {{-- アセット/キャラクター管理用 --}}
                @elseif(isset($images))
                    @foreach($images as $img)
                        <option value="{{ $img }}" {{ ($activeFile ?? '') === $img ? 'selected' : '' }}>📁 {{ $img }}</option>
                    @endforeach
                @endif
            </select>

            {{-- 🌟 ステージ管理用のパーツパレット --}}
            @if(isset($gameItems))
                @php
                    $parts_mode = $parts_mode ?? null;
                @endphp
                <div class="mt-2 border-top pt-2 {{ $parts_mode === 'pixel' ? 'd-none' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-info text-dark fw-bold" style="font-size:10px;">定義済みパーツ</span>
                    </div>
                    <div id="grid_parts_container" class="row g-1 overflow-auto" style="max-height: 200px;">
                        @foreach($gameItems as $item)
                            <div class="col-4">
                                <div class="bg-white border rounded p-1 text-center grid-part-item" 
                                     style="cursor: pointer;" 
                                     title="{{ $item->name }}"
                                     data-item='{{ json_encode($item) }}'
                                     onclick="if(typeof addGridPartToStage === 'function') addGridPartToStage({{ $item->id }})">
                                    <div style="width:100%; height:40px; background-image: url({{ asset('storage/sprite_sheet/' . ($item->spriteSheet->category ?? 'item') . '/' . ($item->spriteSheet->filename ?? '')) }}); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                                    <div class="small text-truncate" style="font-size: 8px;">{{ $item->name }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div id="part_palette_container" class="bg-white border rounded p-1 shadow-inner mt-2" style="max-height: 400px; overflow-y: auto;">
                <div class="text-muted small text-center w-100 py-3" style="font-size: 10px;">素材を選択してください</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    /**
     * 共通パレットの描画処理
     */
    window.renderPartPalette = function() {
        const selector = document.getElementById('atlas_selector');
        const container = document.getElementById('part_palette_container');
        if (!selector || !container) return;

        const selectedOption = selector.options[selector.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) { 
            container.innerHTML = '<div class="text-muted small text-center w-100 py-3" style="font-size: 10px;">未選択</div>'; 
            return; 
        }
        
        let atlasData = {};
        let filename = selectedOption.value;
        let category = 'background';

        // 1. マップ/ステージ管理のコンテキスト判定
        if (selectedOption.dataset.atlas) {
            try {
                atlasData = JSON.parse(selectedOption.dataset.atlas);
                category = selectedOption.dataset.category || 'background';
            } catch(e) { console.error("Atlas parse error (data-atlas)", e); }
        } else {
            // 2. キャラクターアセット管理のコンテキスト判定
            const txtAtlases = document.getElementById('all-atlases-json');
            if (txtAtlases) {
                try {
                    const allAtlases = JSON.parse(txtAtlases.value);
                    const currentAtlasObj = allAtlases[filename];
                    if (currentAtlasObj) {
                        atlasData = currentAtlasObj;
                        category = currentAtlasObj.category || (filename.includes('character') ? 'character' : 'background');
                    }
                } catch(e) { console.error("Atlas parse error (#all-atlases-json)", e); }
            }
        }

        if (!atlasData || !atlasData.textures || !atlasData.textures[0]) {
            container.innerHTML = '<div class="text-muted text-center small p-2 text-warning">データが読み込めません。</div>';
            return;
        }

        container.innerHTML = '';
        const grid = document.createElement('div');
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        grid.style.gap = '8px';
        container.appendChild(grid);

        const textureMeta = atlasData.textures[0];
        if (textureMeta.frames) {
            textureMeta.frames.forEach(frame => {
                const item = document.createElement('div');
                item.className = 'bg-white border rounded p-1 d-flex flex-column align-items-center justify-content-center shadow-sm palette-item';
                item.style.cursor = 'pointer';
                item.title = frame.filename;
                
                const partW = frame.frame.w; 
                const partH = frame.frame.h;
                const isWide = partW > partH * 1.4;

                if (isWide) {
                    item.style.gridColumn = 'span 2';
                    item.style.minHeight = '80px';
                } else {
                    item.style.aspectRatio = '1 / 1';
                }
                
                const thumbWrapper = document.createElement('div');
                const boxSize = 80;
                thumbWrapper.style.width = isWide ? '100%' : boxSize + 'px';
                thumbWrapper.style.height = boxSize + 'px';
                thumbWrapper.style.display = 'flex';
                thumbWrapper.style.alignItems = 'center';
                thumbWrapper.style.justifyContent = 'center';
                thumbWrapper.style.overflow = 'hidden';
                thumbWrapper.style.background = '#f8f9fa';
                thumbWrapper.style.borderRadius = '3px';
                
                const thumb = document.createElement('div');
                const containerW = isWide ? (container.clientWidth - 20) : boxSize;
                const scale = Math.min(containerW / partW, boxSize / partH);
                
                thumb.style.width = (partW * scale) + 'px'; 
                thumb.style.height = (partH * scale) + 'px';
                const baseUrl = window.SPRITE_SHEET_BASE_URL || "{{ asset('storage/sprite_sheet') }}/";
                thumb.style.backgroundImage = `url(${baseUrl}${category}/${filename})`;
                thumb.style.backgroundPosition = `-${frame.frame.x * scale}px -${frame.frame.y * scale}px`;
                thumb.style.backgroundSize = `${(textureMeta.size?.w || 512) * scale}px ${(textureMeta.size?.h || 512) * scale}px`;
                thumb.style.backgroundRepeat = 'no-repeat';
                
                thumbWrapper.appendChild(thumb);
                item.appendChild(thumbWrapper);

                item.addEventListener('click', () => {
                    if (typeof window.addLayerFromPalette === 'function') {
                        window.addLayerFromPalette(frame, filename, category);

                        // 🌟 プレビュー側で追加された最新の要素を自動で選択状態にする（枠を表示させる）
                        setTimeout(() => {
                            const root = document.getElementById('preview_layers_root');
                            if (root && typeof window.selectLayer === 'function') {
                                const els = root.querySelectorAll('.layer-element');
                                if (els.length > 0) {
                                    window.selectLayer(els.length - 1, els[els.length - 1]);
                                }
                            }
                        }, 100);
                    }
                    if (typeof window.handlePalettePartClick === 'function') {
                        window.handlePalettePartClick(frame, filename);
                    }
                });
                grid.appendChild(item);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const selector = document.getElementById('atlas_selector');
        if (selector) {
            selector.addEventListener('change', window.renderPartPalette);
            setTimeout(window.renderPartPalette, 200);
        }
    });
})();
</script>

<style>
.palette-item:hover {
    border-color: #007bff !important;
    background-color: #e7f1ff !important;
    transform: scale(1.02);
    transition: all 0.1s ease-in-out;
}
.card-header[data-bs-toggle="collapse"] .bi-chevron-down {
    transition: transform 0.2s;
}
.card-header[data-bs-toggle="collapse"].collapsed .bi-chevron-down {
    transform: rotate(-90deg);
}
</style>
