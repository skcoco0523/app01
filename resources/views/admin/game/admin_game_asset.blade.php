{{-- 🎬 モーション組み立てワークスペース --}}
@php
    $parts_mode = 'motion';
@endphp
@if(session('msg'))
    <div class="alert alert-info shadow-sm py-2 small animate__animated animate__flash">
        <i class="bi bi-info-circle-fill me-1"></i> {!! session('msg') !!}
    </div>
@endif

<div class="row m-0 p-0">
    {{-- 👤 左側：キャラクター＆素材選択サイドバー --}}
    <div class="col-md-2 animate__animated animate__fadeIn ps-0 pe-2">
        <div class="p-2 mb-2 bg-dark text-white rounded border" style="font-size:12px;">
            <label class="form-label m-0 p-0 font-monospace small text-info fw-bold"><i class="bi bi-person-check-fill"></i> 対象キャラクター</label>
            <select id="editor-character-select" class="form-select form-select-sm font-monospace text-dark fw-bold mt-1" required
                    onchange="if(this.value) window.location.href = this.value;">
                <option value="{{ route('admin.game.asset.index', ['game_key' => $gameKey]) }}">-- 選択してください --</option>
                @foreach($gameCharacters as $gChar)
                    <option value="{{ route('admin.game.asset.index', ['game_key' => $gameKey, 'character_id' => $gChar->id]) }}" {{ ($character->id ?? '') == $gChar->id ? 'selected' : '' }}>
                        👤 {{ $gChar->name }} ({{ $gChar->character_key }})
                    </option>
                @endforeach
            </select>
        </div>

        @include('admin.game.parts.material_palette')

        {{-- 2. このキャラにすでに配置された部位リスト --}}
        <div class="card mb-0 shadow-sm border-success">
            <div class="card-header bg-success text-white small fw-bold py-2">
                <i class="bi bi-diagram-3-fill me-1"></i> ② 配置済みの部位
            </div>
            <div class="card-body bg-light p-2" id="motion-used-list" style="max-height: 250px; overflow-y: auto;"></div>
        </div>
    </div>

    {{-- 🎮 右側：モーション編集エディタ本体 --}}
    <div class="col-md-10 animate__animated animate__fadeIn px-0">
        @include('admin.game.sprite_sheet_motion')
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const usedList = document.getElementById('motion-used-list');
    const txtAtlases = document.getElementById('all-atlases-json');

    window.renderUsedPartsPalette = function() {
        if (!usedList) return; usedList.innerHTML = '';
        const currentMotionObj = window.currentMotionObj;
        const txtAtlases = document.getElementById('all-atlases-json');
        
        if (!currentMotionObj || !currentMotionObj.setup || !currentMotionObj.setup.parts) {
            usedList.innerHTML = '<div class="text-muted text-center py-2" style="font-size:11px;">配置されているパーツはありません。</div>';
            return;
        }

        let allAtlases = {};
        try { allAtlases = JSON.parse(txtAtlases.value); } catch(e) { allAtlases = {}; }

        currentMotionObj.setup.parts.forEach(part => {
            const item = document.createElement('div');
            const isActive = (window.targetPartData && window.targetPartData.name === part.name);
            item.className = `d-flex align-items-center justify-content-between p-1 px-2 mb-1 rounded border ${isActive ? 'bg-success bg-opacity-25 border-success fw-bold text-success shadow-sm' : 'bg-white text-dark'}`;
            item.style.cursor = 'pointer'; item.style.fontSize = '12px';

            const pSrcImg = part.image || "{{ $activeFile }}";
            let frameName = part.frame;
            
            if (currentMotionObj.forms?.[window.activeForm]?.[part.name]) { 
                frameName = currentMotionObj.forms[window.activeForm][part.name]; 
            }

            let textureMeta = null; let srcMeta = null;
            if (allAtlases[pSrcImg] && allAtlases[pSrcImg].textures?.[0]) {
                textureMeta = allAtlases[pSrcImg].textures[0];
                srcMeta = textureMeta.frames.find(f => f.filename === frameName);
            }

            const thumbWrapper = document.createElement('div');
            thumbWrapper.style.width = '28px'; thumbWrapper.style.height = '28px';
            thumbWrapper.style.display = 'flex'; thumbWrapper.style.alignItems = 'center'; thumbWrapper.style.justifyContent = 'center'; thumbWrapper.style.flexShrink = '0';
            thumbWrapper.style.backgroundColor = '#f0f0f0'; thumbWrapper.style.border = '1px solid #dee2e6'; thumbWrapper.style.borderRadius = '4px'; thumbWrapper.style.overflow = 'hidden';
            thumbWrapper.className = 'me-2';

            if (srcMeta && textureMeta && frameName !== 'transparent') {
                const partW = srcMeta.frame.w; const partH = srcMeta.frame.h;
                const thumbMaxSize = 26; const thumbScale = thumbMaxSize / Math.max(partW, partH, 1);
                const thumbW = partW * thumbScale; const thumbH = partH * thumbScale;

                const thumb = document.createElement('div');
                thumb.style.width = thumbW + 'px'; thumb.style.height = thumbH + 'px';
                const partCategory = allAtlases[pSrcImg]?.category || 'character';
                thumb.style.backgroundImage = `url(${window.SPRITE_SHEET_BASE_URL || '/storage/sprite_sheet/'}${partCategory}/${pSrcImg})`;
                thumb.style.backgroundPosition = `-${srcMeta.frame.x * thumbScale}px -${srcMeta.frame.y * thumbScale}px`;
                thumb.style.backgroundSize = `${(textureMeta.size?.w || 512) * thumbScale}px ${(textureMeta.size?.h || 512) * thumbScale}px`;
                thumb.style.backgroundRepeat = 'no-repeat';
                thumbWrapper.appendChild(thumb);
            } else {
                thumbWrapper.innerHTML = '<span style="font-size:9px; color:#ccc;">👻</span>';
            }

            const leftDiv = document.createElement('div');
            leftDiv.className = 'text-truncate me-2 flex-grow-1 font-monospace'; leftDiv.style.minWidth = '0';
            leftDiv.innerHTML = `<span class="badge bg-dark me-1">${part.name}</span><span class="text-muted" style="font-size:10px;">(${frameName})</span>`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'd-flex align-items-center flex-grow-1 min-w-0';
            contentDiv.appendChild(thumbWrapper);
            contentDiv.appendChild(leftDiv);

            const delBtn = document.createElement('button');
            delBtn.type = 'button'; delBtn.className = 'btn btn-link text-danger p-0 px-1 border-0 m-0 align-middle';
            delBtn.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            
            delBtn.addEventListener('click', (e) => {
                e.stopPropagation(); 
                if (window.activeAnimName !== 'setup') { alert('パーツの削除はセットアップポーズ時のみ可能です。'); return; }
                if (confirm(`部位 [${part.name}] をセットアップ配列から完全に削除しますか？`)) {
                    window.currentMotionObj.setup.parts = window.currentMotionObj.setup.parts.filter(p => p !== part);
                    if (window.updateMotionTextarea) window.updateMotionTextarea();
                    if (window.targetPartData === part) { window.targetPartData = null; if (window.resetFields) window.resetFields(); }
                    if (window.renderMotionFrames) window.renderMotionFrames();
                }
            });

            item.addEventListener('click', () => {
                const charRoot = document.getElementById('motion-character-root');
                if (!charRoot) return;
                const targetEl = charRoot.querySelector(`.motion-spawned-part[data-name="${part.name}"]`);
                if (targetEl && window.selectSetupPart) { window.selectSetupPart(targetEl, part); }
            });

            item.appendChild(contentDiv); item.appendChild(delBtn); usedList.appendChild(item);
        });

        if (currentMotionObj.setup.parts.length === 0) {
            usedList.innerHTML = '<div class="text-muted text-center py-2" style="font-size:11px;">配置されているパーツはありません。</div>';
        }
    };
});
</script>
