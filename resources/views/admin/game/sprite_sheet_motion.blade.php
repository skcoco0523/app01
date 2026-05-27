<form action="{{ route('admin.game.sprite_sheet.update') }}" method="POST">
    @csrf
    <input type="hidden" name="mode" value="motion">
    <input type="hidden" name="filename" value="{{ $activeFile }}">
    <input type="hidden" name="atlas_content" value="{{ $atlasContent }}"> 

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">
                        <span id="editor-badge-mode" class="badge bg-primary me-2">セットアップモード</span><code>{{ $activeFile }}</code>
                    </h6>
                    
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                        <span class="btn btn-outline-light disabled text-white fw-bold" id="lbl-zoom" style="opacity: 1; min-width: 65px;">100%</span>
                        <button type="button" class="btn btn-outline-light" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                        <button type="button" class="btn btn-outline-light" id="btn-zoom-reset">リセット</button>
                    </div>
                </div>

                <div class="card-body bg-light border-bottom p-2 d-flex align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-secondary font-monospace small">向き</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active fw-bold" id="sim-dir-right">右</button>
                            <button type="button" class="btn btn-outline-primary fw-bold" id="sim-dir-left">左</button>
                            <button type="button" class="btn btn-outline-primary fw-bold" id="sim-dir-front">前</button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-grow-1 flex-wrap">
                        <select id="sim-anim-select" class="form-select form-select-sm font-monospace w-auto" style="min-width: 140px;">
                            <option value="setup">⚙️ 0. setupポーズ</option>
                        </select>
                        
                        <div id="anim-fps-box" class="d-flex align-items-center gap-1 border rounded bg-white px-2 py-1 shadow-sm" style="display: none !important; height: 31px;">
                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 10px;">FPS</span>
                            <input type="number" id="anim-fps" class="form-control form-control-sm text-center border-0 p-0 m-0 fw-bold text-primary font-monospace" style="width: 35px; box-shadow: none;" min="1" max="60" value="6">
                        </div>
                        
                        <button type="button" class="btn btn-sm btn-outline-dark px-1 py-0" id="btn-add-new-anim" title="新規モーションを追加"><i class="bi bi-plus-circle"></i>+追加</button>

                        <div class="btn-group btn-group-sm" id="anim-play-controls" style="display:none;">
                            <button type="button" class="btn btn-success py-1 px-2" id="btn-anim-play" title="再生">
                                <i class="bi bi-play-fill"></i> 再生
                            </button>
                            <button type="button" class="btn btn-danger py-1 px-2 d-none" id="btn-anim-stop" title="停止">
                                <i class="bi bi-stop-fill"></i> 停止
                            </button>
                        </div>

                        <div class="btn-group btn-group-sm border rounded bg-white p-1" id="timeline-step-box" style="display:none;">
                            <button type="button" class="btn btn-light btn-sm py-1 px-2" id="btn-frame-prev" title="前のコマ">
                                <i class="bi bi-chevron-left"></i> 前へ
                            </button>
                            <span class="px-2 font-monospace small fw-bold text-primary align-self-center" id="lbl-frame-index" style="min-width: 65px; text-align:center; font-size:11px;">1 / 1</span>
                            <button type="button" class="btn btn-light btn-sm py-1 px-2" id="btn-frame-next" title="次のコマ">
                                次へ <i class="bi bi-chevron-right"></i>
                            </button>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 border-0 ms-1" id="btn-frame-copy" title="コピー複製">
                                <i class="bi bi-copy"></i> 複製
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm py-1 px-2 border-0" id="btn-frame-add" title="空コマ挿入">
                                <i class="bi bi-file-earmark-plus"></i> 挿入
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 border-0" id="btn-frame-del" title="コマ削除">
                                <i class="bi bi-file-earmark-minus"></i> 削除
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 text-center position-relative" style="height: 480px; background-color: #555; overflow: auto;">
                    <div id="motion-canvas-container" class="position-relative m-3 shadow text-start d-inline-block" 
                        style="width: 600px; height: 440px; background-color: #e5e5e5; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, #e5e5e5 25%, #e5e5e5 75%, #ccc 75%); background-size: 20px 20px; background-position: 0 0, 10px 10px; overflow: hidden; user-select: none;">
                        
                        <div style="position: absolute; left: 300px; top: 0; width: 1px; height: 100%; background: rgba(0,0,255,0.2); pointer-events: none; z-index: 9990;"></div>
                        <div style="position: absolute; left: 0; top: 220px; width: 100%; height: 1px; background: rgba(0,0,255,0.2); pointer-events: none; z-index: 9990;"></div>

                        <div id="guide-hitbox" style="position: absolute; border: 2px dashed rgba(255, 0, 0, 0.5); background: rgba(255,0,0,0.02); pointer-events: none; z-index: 9991; display: none;"></div>
                        <div id="guide-foot-y" style="position: absolute; left: 0; width: 100%; height: 0; border-top: 2px solid rgba(13, 110, 253, 0.7); pointer-events: none; z-index: 9992; display: none;"></div>
                        <span id="lbl-guide-foot" style="position: absolute; left: 10px; color: #0d6efd; font-size: 10px; font-weight: bold; pointer-events: none; z-index: 9992; display: none;">👠 足元床面 (Foot Y)</span>
                        <div id="guide-top-y" style="position: absolute; left: 0; width: 100%; height: 0; border-top: 2px dashed rgba(111, 66, 193, 0.7); pointer-events: none; z-index: 9992; display: none;"></div>
                        <span id="lbl-guide-top" style="position: absolute; left: 10px; color: #6f42c1; font-size: 10px; font-weight: bold; pointer-events: none; z-index: 9992; display: none;">👑 頭上上限 (Hitbox Top)</span>
                        <div id="guide-wall-l" style="position: absolute; top: 0; width: 0; height: 100%; border-left: 2px dashed rgba(25, 135, 84, 0.7); pointer-events: none; z-index: 9992; display: none;"></div>
                        <div id="guide-wall-r" style="position: absolute; top: 0; width: 0; height: 100%; border-left: 2px dashed rgba(25, 135, 84, 0.7); pointer-events: none; z-index: 9992; display: none;"></div>
                        <span id="lbl-guide-wall" style="position: absolute; top: 10px; color: #198754; font-size: 10px; font-weight: bold; pointer-events: none; z-index: 9992; display: none;">🚧 横の壁判定 (Wall X)</span>

                        <div id="motion-character-root" style="position: absolute; left: 300px; top: 220px; width: 0; height: 0; transform-origin: 0px 0px; z-index: 100;"></div>
                    </div>
                </div>
                <div class="card-footer bg-light small text-muted py-1">
                    パーツを直接ドラッグ移動、または右側フォームで微調整可能です。
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3 shadow-sm">
                <div class="card-body p-3">
                    <div class="p-2 mb-3 bg-dark text-white rounded border" style="font-size:12px;">
                        <span class="text-info font-monospace fw-bold small"><i class="bi bi-sliders me-1"></i> 共通物理設定 (physics)</span>
                        <div class="row g-1 mt-1">
                            <div class="col-4">
                                <label class="form-label m-0 p-0 font-monospace" style="font-size:10px;">Hitbox 幅</label>
                                <input type="number" id="phys-hb-w" class="form-control form-control-sm bg-secondary text-white border-0 py-0" value="40">
                            </div>
                            <div class="col-4">
                                <label class="form-label m-0 p-0 font-monospace" style="font-size:10px;">Hitbox 高</label>
                                <input type="number" id="phys-hb-h" class="form-control form-control-sm bg-secondary text-white border-0 py-0" value="40">
                            </div>
                            <div class="col-4">
                                <label class="form-label m-0 p-0 font-monospace" style="font-size:10px;">Foot Y</label>
                                <input type="number" id="phys-foot-y" class="form-control form-control-sm bg-secondary text-white border-0 py-0" value="215">
                            </div>
                            <div class="col-6 mt-1">
                                <label class="form-label m-0 p-0 font-monospace" style="font-size:10px;">OffsetX</label>
                                <input type="number" id="phys-offset-x" class="form-control form-control-sm bg-secondary text-white border-0 py-0" value="0">
                            </div>
                            <div class="col-6 mt-1">
                                <label class="form-label m-0 p-0 font-monospace text-warning" style="font-size:10px;">globalPartScale</label>
                                <input type="number" id="phys-scale" class="form-control form-control-sm bg-secondary text-white border-0 py-0" step="0.05" value="0.6">
                            </div>
                        </div>
                    </div>

                    <div id="part-properties-box" class="p-3 bg-light rounded border shadow-inner">
                        <div class="fw-bold text-secondary font-monospace small mb-2"><i class="bi bi-pencil-square"></i> 選択中パーツプロパティ
                        
                        <button type="button" id="btn-delete-setup-part" class="btn btn-xs btn-danger font-monospace small px-3 py-1" title="この要素を削除"><i class="bi bi-trash"></i> このパーツをキャンバスから削除</button>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small font-monospace text-success m-0">部位名 (name)</label>
                                <input type="text" id="part-name" class="form-control form-control-sm" readonly style="background-color: #e9ecef;">
                            </div>
                            <div class="col-6">
                                <label class="form-label small font-monospace text-muted m-0">描画画像 (frame)</label>
                                <input type="text" id="part-frame" class="form-control form-control-sm" readonly style="background-color: #e9ecef;">
                            </div>
                            <div class="col-4">
                                <label id="lbl-part-x" class="form-label small font-monospace text-primary fw-bold m-0">相対 X</label>
                                <input type="number" id="part-x" class="form-control form-control-sm">
                            </div>
                            <div class="col-4">
                                <label id="lbl-part-y" class="form-label small font-monospace text-primary fw-bold m-0">相対 Y</label>
                                <input type="number" id="part-y" class="form-control form-control-sm">
                            </div>
                            <div class="col-4" id="box-part-angle">
                                <label class="form-label small font-monospace text-warning fw-bold m-0">角度 (angle)</label>
                                <input type="number" id="part-angle" class="form-control form-control-sm" min="-360" max="360" value="0">
                            </div>
                            <div class="col-4" id="box-part-depth">
                                <label class="form-label small font-monospace m-0">重なり (Z)</label>
                                <input type="number" id="part-depth" class="form-control form-control-sm" min="0" max="999">
                            </div>
                            <div class="col-4" id="box-part-ox">
                                <label class="form-label small font-monospace text-danger m-0">軸 ox</label>
                                <input type="number" id="part-ox" class="form-control form-control-sm" step="0.05" min="0" max="1">
                            </div>
                            <div class="col-4" id="box-part-oy">
                                <label class="form-label small font-monospace text-danger m-0">軸 oy</label>
                                <input type="number" id="part-oy" class="form-control form-control-sm" step="0.05" min="0" max="1">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <div class="mb-2 small font-monospace fw-bold text-success"><i class="bi bi-filetype-json"></i> ② 配置＆モーション構造データ (_motion.json)
                        <button type="submit" class="btn btn-success text-white fw-bold"><i class="bi bi-save me-1"></i>ﾓｰｼｮﾝﾃﾞｰﾀを保存</button>
                    </div>
                    <textarea name="motion_content" id="motion-textarea" class="form-control font-monospace small" rows="8" style="font-size: 11px; tab-size: 2; height:200px;">{{ $motionContent }}</textarea>
                    <textarea id="atlas-textarea" class="d-none">{{ $atlasContent }}</textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<img id="sprite-target-img" src="{{ asset('storage/sprite_sheet/' . $activeFile) }}" class="d-none">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const motionContainer = document.getElementById('motion-canvas-container');
    const charRoot = document.getElementById('motion-character-root'); 
    const img = document.getElementById('sprite-target-img');
    const txtAtlas = document.getElementById('atlas-textarea');
    const txtMotion = document.getElementById('motion-textarea');
    
    const inpHbW = document.getElementById('phys-hb-w'); const inpHbH = document.getElementById('phys-hb-h');
    const inpFootY = document.getElementById('phys-foot-y'); const inpOffsetX = document.getElementById('phys-offset-x');
    const inpPhysScale = document.getElementById('phys-scale');
    const gHitbox = document.getElementById('guide-hitbox'); 
    const gFootY = document.getElementById('guide-foot-y'); const lblFoot = document.getElementById('lbl-guide-foot');
    const gTopY = document.getElementById('guide-top-y'); const lblTop = document.getElementById('lbl-guide-top');
    const gWallL = document.getElementById('guide-wall-l'); const gWallR = document.getElementById('guide-wall-r'); const lblWall = document.getElementById('lbl-guide-wall');

    const inpName = document.getElementById('part-name'); const inpFrame = document.getElementById('part-frame');
    const inpX = document.getElementById('part-x'); const inpY = document.getElementById('part-y');
    const inpAngle = document.getElementById('part-angle'); const inpDepth = document.getElementById('part-depth');
    const inpOx = document.getElementById('part-ox'); const inpOy = document.getElementById('part-oy');
    const btnDeleteSetup = document.getElementById('btn-delete-setup-part');
    
    const paletteList = document.getElementById('motion-palette-list');
    const usedList = document.getElementById('motion-used-list'); 
    const propBox = document.getElementById('part-properties-box');
    
    const lblPartX = document.getElementById('lbl-part-x'); const lblPartY = document.getElementById('lbl-part-y');
    const badgeMode = document.getElementById('editor-badge-mode');

    const btnDirRight = document.getElementById('sim-dir-right'); const btnDirLeft = document.getElementById('sim-dir-left'); const btnDirFront = document.getElementById('sim-dir-front');
    const animSelect = document.getElementById('sim-anim-select');
    const btnAnimPlay = document.getElementById('btn-anim-play'); const btnAnimStop = document.getElementById('btn-anim-stop');
    const timelineBox = document.getElementById('timeline-step-box'); const playControlsBox = document.getElementById('anim-play-controls');
    const lblFrameIndex = document.getElementById('lbl-frame-index');
    const btnFramePrev = document.getElementById('btn-frame-prev'); const btnFrameNext = document.getElementById('btn-frame-next');
    const btnFrameAdd = document.getElementById('btn-frame-add'); const btnFrameDel = document.getElementById('btn-frame-del');
    const btnFrameCopy = document.getElementById('btn-frame-copy');
    const btnAddNewAnim = document.getElementById('btn-add-new-anim');
    const animFpsBox = document.getElementById('anim-fps-box');
    const inpAnimFps = document.getElementById('anim-fps');

    const ORIGIN_X = 300; const ORIGIN_Y = 220;
    let zoomLevel = 1.0; let mode = 'idle'; let startX = 0, startY = 0;
    let currentAtlasObj = null; let currentMotionObj = null;
    let activeMotionElement = null; let targetPartData = null; 
    let partInitialX = 0, partInitialY = 0;

    let activeForm = 'right'; let activeAnimName = 'setup';
    let animPlaybackInterval = null; let currentFrameIndex = 0;

    function changeZoom(newZoom) {
        zoomLevel = Math.max(0.5, Math.min(4.0, newZoom));
        motionContainer.style.zoom = zoomLevel;
        document.getElementById('lbl-zoom').textContent = Math.round(zoomLevel * 100) + '%';
    }
    
    document.getElementById('btn-zoom-in').addEventListener('click', () => changeZoom(zoomLevel + 0.25));
    document.getElementById('btn-zoom-out').addEventListener('click', () => changeZoom(zoomLevel - 0.25));
    document.getElementById('btn-zoom-reset').addEventListener('click', () => changeZoom(1.0));
    
    motionContainer.addEventListener('wheel', function(e) {
        if (e.ctrlKey) { 
            e.preventDefault(); 
            changeZoom(e.deltaY < 0 ? zoomLevel + 0.25 : zoomLevel - 0.25); 
        }
    }, { passive: false });

    function updateMotionTextarea() {
        if (!currentMotionObj) return;
        let jsonStr = JSON.stringify(currentMotionObj, null, 2);
        jsonStr = jsonStr.replace(/\{\s*\n\s*"name":\s*("[^"]+"),\s*\n\s*"frame":\s*("[^"]+"),\s*\n\s*"x":\s*(-?\d+),\s*\n\s*"y":\s*(-?\d+),\s*\n\s*"depth":\s*(-?\d+),\s*\n\s*"originX":\s*([0-9.]+),\s*\n\s*"originY":\s*([0-9.]+)\s*\n\s*\}/g, '{ "name": $1, "frame": $2, "x": $3, "y": $4, "depth": $5, "originX": $6, "originY": $7 }');
        txtMotion.value = jsonStr;
    }

    function buildPartPalette() {
        if (!paletteList) return; paletteList.innerHTML = '';
        try {
            currentAtlasObj = JSON.parse(txtAtlas.value);
            currentAtlasObj.textures[0].frames.forEach(f => {
                const btn = document.createElement('button');
                btn.type = 'button'; 
                btn.className = 'btn btn-outline-secondary btn-sm w-100 text-start mb-1 p-1 d-flex align-items-center gap-2 bg-white overflow-hidden';
                
                const partW = f.frame.w; const partH = f.frame.h;
                const thumbMaxSize = 32; const thumbScale = thumbMaxSize / Math.max(partW, partH, 1);
                const thumbW = partW * thumbScale; const thumbH = partH * thumbScale;

                const thumbWrapper = document.createElement('div');
                thumbWrapper.style.width = thumbMaxSize + 'px'; thumbWrapper.style.height = thumbMaxSize + 'px';
                thumbWrapper.style.display = 'flex'; thumbWrapper.style.alignItems = 'center'; thumbWrapper.style.justifyContent = 'center'; thumbWrapper.style.flexShrink = '0';
                
                const thumb = document.createElement('div');
                thumb.style.width = thumbW + 'px'; thumb.style.height = thumbH + 'px'; thumb.style.backgroundImage = `url(${img.src})`;
                thumb.style.backgroundPosition = `-${f.frame.x * thumbScale}px -${f.frame.y * thumbScale}px`; thumb.style.backgroundSize = `${img.naturalWidth * thumbScale}px ${img.naturalHeight * thumbScale}px`;
                thumb.style.backgroundRepeat = 'no-repeat'; thumb.style.border = '1px solid #dee2e6'; thumb.style.flexShrink = '0';
                
                const label = document.createElement('span');
                label.className = 'text-truncate font-monospace small flex-grow-1'; label.style.minWidth = '0'; label.textContent = f.filename;
                
                thumbWrapper.appendChild(thumb); btn.appendChild(thumbWrapper); btn.appendChild(label);

                btn.addEventListener('click', () => {
                    initMotionJsonStructure();
                    const partKeyName = prompt(`追加するパーツの「部位名(name)」を入力してください:`, f.filename);
                    if (!partKeyName) return;
                    const cleanName = partKeyName.trim();

                    if (activeAnimName === 'setup') {
                        if (!currentMotionObj.forms) currentMotionObj.forms = { right:{}, left:{}, front:{} };
                        const existingPart = currentMotionObj.setup.parts.find(p => p.name === cleanName);

                        if (existingPart) {
                            if (!currentMotionObj.forms[activeForm]) currentMotionObj.forms[activeForm] = {};
                            currentMotionObj.forms[activeForm][cleanName] = f.filename;
                        } else {
                            const newPart = { name: cleanName, frame: f.filename, x: 0, y: 0, depth: currentMotionObj.setup.parts.length + 1, originX: 0.5, originY: 0.5 };
                            currentMotionObj.setup.parts.push(newPart);

                            const allForms = ['right', 'left', 'front'];
                            allForms.forEach(form => {
                                if (!currentMotionObj.forms[form]) currentMotionObj.forms[form] = {};
                                if (form !== activeForm) {
                                    currentMotionObj.forms[form][cleanName] = 'transparent';
                                } else {
                                    currentMotionObj.forms[form][cleanName] = f.filename;
                                }
                            });
                        }
                    } else {
                        initAnimFrameNodePath();
                        const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts;
                        partsNode[cleanName] = { frame: f.filename, x: 0, y: 0, angle: 0, depth: 1 };
                    }

                    updateMotionTextarea(); 
                    renderMotionFrames();
                    
                    setTimeout(() => {
                        const targetEl = charRoot.querySelector(`.motion-spawned-part[data-name="${cleanName}"]`);
                        const partData = currentMotionObj.setup.parts.find(p => p.name === cleanName) || { name: cleanName, frame: f.filename, x: 0, y: 0 };
                        if (targetEl && partData) selectSetupPart(targetEl, partData);
                    }, 50);
                });
                paletteList.appendChild(btn);
            });
        } catch(e) { paletteList.innerHTML = '<div class="text-danger small">アトラスJSONが不正です</div>'; }
    }

    function renderUsedPartsPalette() {
        if (!usedList) return; usedList.innerHTML = '';
        if (!currentMotionObj || !currentMotionObj.setup || !currentMotionObj.setup.parts) return;

        currentMotionObj.setup.parts.forEach(part => {
            const item = document.createElement('div');
            const isActive = (targetPartData && targetPartData.name === part.name);
            item.className = `d-flex align-items-center justify-content-between p-1 px-2 mb-1 rounded border ${isActive ? 'bg-success bg-opacity-25 border-success fw-bold text-success shadow-sm' : 'bg-white text-dark'}`;
            item.style.cursor = 'pointer'; item.style.fontSize = '12px';

            const leftDiv = document.createElement('div');
            leftDiv.className = 'text-truncate me-2 flex-grow-1 font-monospace'; leftDiv.style.minWidth = '0';
            leftDiv.innerHTML = `<span class="badge bg-dark me-1">${part.name}</span><span class="text-muted" style="font-size:10px;">(${part.frame})</span>`;

            const delBtn = document.createElement('button');
            delBtn.type = 'button'; delBtn.className = 'btn btn-link text-danger p-0 px-1 border-0 m-0 align-middle';
            delBtn.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            
            delBtn.addEventListener('click', (e) => {
                e.stopPropagation(); 
                if (activeAnimName !== 'setup') { alert('パーツの削除はセットアップポーズ時のみ可能です。'); return; }
                if (confirm(`部位 [${part.name}] をセットアップ配列から完全に削除しますか？`)) {
                    currentMotionObj.setup.parts = currentMotionObj.setup.parts.filter(p => p !== part);
                    updateMotionTextarea();
                    if (targetPartData === part) { targetPartData = null; resetFields(); }
                    renderMotionFrames();
                }
            });

            item.addEventListener('click', () => {
                const targetEl = charRoot.querySelector(`.motion-spawned-part[data-name="${part.name}"]`);
                if (targetEl) { selectSetupPart(targetEl, part); }
            });

            item.appendChild(leftDiv); item.appendChild(delBtn); usedList.appendChild(item);
        });

        if (currentMotionObj.setup.parts.length === 0) {
            usedList.innerHTML = '<div class="text-muted text-center py-2" style="font-size:11px;">配置されているパーツはありません。</div>';
        }
    }

    function resetFields() {
        inpName.value = ''; inpFrame.value = ''; inpX.value = ''; inpY.value = ''; inpAngle.value = 0; inpDepth.value = 1; inpOx.value = 0.5; inpOy.value = 0.5;
    }

    function initMotionJsonStructure() {
        try { currentMotionObj = JSON.parse(txtMotion.value); if (!currentMotionObj || !currentMotionObj.physics) throw new Error(); }
        catch(e) { currentMotionObj = { physics: { hitboxWidth: 40, hitboxHeight: 40, footY: 215, offsetX: 0, globalPartScale: 0.6 }, setup: { parts: [] }, forms: { right:{}, left:{}, front:{} }, animations: {} }; }
    }

    function syncAnimationSelectOptions() {
        initMotionJsonStructure();
        while(animSelect.options.length > 1) { animSelect.remove(1); }
        if (currentMotionObj.animations) {
            Object.keys(currentMotionObj.animations).forEach(key => {
                const opt = document.createElement('option'); opt.value = key; opt.textContent = `🎬 モーション: ${key}`; animSelect.appendChild(opt);
            });
        }
    }

    function reselectCurrentPart() {
        if (!targetPartData) return;
        const savedPartName = targetPartData.name;
        const part = currentMotionObj.setup.parts.find(p => p.name === savedPartName);
        const targetEl = charRoot.querySelector(`.motion-spawned-part[data-name="${savedPartName}"]`);
        if (part && targetEl) { selectSetupPart(targetEl, part); }
    }

    function renderMotionFrames() {
        charRoot.querySelectorAll('.motion-spawned-part').forEach(el => el.remove());

        const phys = currentMotionObj.physics; const scale = phys.globalPartScale || 1.0;
        inpHbW.value = phys.hitboxWidth; inpHbH.value = phys.hitboxHeight; inpFootY.value = phys.footY; inpOffsetX.value = phys.offsetX; inpPhysScale.value = scale;

        const absoluteFootY = ORIGIN_Y + (phys.footY || 0);
        const absoluteTopY = absoluteFootY - phys.hitboxHeight;
        const absoluteHitboxLeft = ORIGIN_X + (phys.offsetX || 0) - (phys.hitboxWidth / 2);

        gHitbox.style.display = 'block'; gHitbox.style.width = phys.hitboxWidth + 'px'; gHitbox.style.height = phys.hitboxHeight + 'px'; gHitbox.style.left = absoluteHitboxLeft + 'px'; gHitbox.style.top = absoluteTopY + 'px'; 
        gFootY.style.display = 'block'; gFootY.style.top = absoluteFootY + 'px'; lblFoot.style.display = 'block'; lblFoot.style.top = (absoluteFootY + 4) + 'px';
        gTopY.style.display = 'block'; gTopY.style.top = absoluteTopY + 'px'; lblTop.style.display = 'block'; lblTop.style.top = (absoluteTopY - 16) + 'px';
        const absoluteWallL = ORIGIN_X + (phys.offsetX || 0) - (phys.hitboxWidth / 2); const absoluteWallR = ORIGIN_X + (phys.offsetX || 0) + (phys.hitboxWidth / 2); gWallL.style.display = 'block'; gWallL.style.left = absoluteWallL + 'px'; gWallR.style.display = 'block'; gWallR.style.left = absoluteWallR + 'px'; lblWall.style.display = 'block'; lblWall.style.left = (absoluteWallR + 6) + 'px';

        charRoot.style.transform = (activeForm === 'left') ? 'scaleX(-1)' : 'scaleX(1)';

        try { currentAtlasObj = JSON.parse(txtAtlas.value); } catch(e) { currentAtlasObj = { textures: [{ frames: [] }] }; }
        const atlasFrames = currentAtlasObj.textures[0].frames;

        let animFrameParts = null;
        if (activeAnimName !== 'setup' && currentMotionObj.animations[activeAnimName]) {
            const anim = currentMotionObj.animations[activeAnimName];
            if (anim.frames && anim.frames[currentFrameIndex]) { animFrameParts = anim.frames[currentFrameIndex].parts; }
        }

        const allPartNames = new Set();
        currentMotionObj.setup.parts.forEach(p => allPartNames.add(p.name));
        if (animFrameParts) { Object.keys(animFrameParts).forEach(name => allPartNames.add(name)); }

        allPartNames.forEach((partName) => {
            const part = currentMotionObj.setup.parts.find(p => p.name === partName) || { name: partName, frame: '', x: 0, y: 0, depth: 1, originX: 0.5, originY: 0.5 };
            
            let frameName = part.frame;
            if (currentMotionObj.forms?.[activeForm]?.[part.name]) { frameName = currentMotionObj.forms[activeForm][part.name]; }
            if (animFrameParts?.[part.name]?.frame) { frameName = animFrameParts[part.name].frame; }

            if (!frameName || frameName === 'transparent') return; 

            const srcMeta = atlasFrames.find(f => f.filename === frameName);
            if (!srcMeta) return;

            const w = srcMeta.frame.w * scale; const h = srcMeta.frame.h * scale;
            const ox = part.originX !== undefined ? part.originX : 0.5; const oy = part.originY !== undefined ? part.originY : 0.5;

            // 🌟【互換性復活】ベース座標 ＋ モーション移動量の相対加算(+=)に戻す
            let finalX = part.x; 
            let finalY = part.y; 
            let finalAngle = 0;
            let finalDepth = part.depth || 1;

            if (animFrameParts?.[part.name]) {
                const kf = animFrameParts[part.name];
                if (kf.x !== undefined) finalX += kf.x; 
                if (kf.y !== undefined) finalY += kf.y; 
                if (kf.angle !== undefined) finalAngle = kf.angle;
                if (kf.depth !== undefined) finalDepth = kf.depth;
            }

            const pEl = document.createElement('div');
            pEl.className = 'position-absolute motion-spawned-part'; pEl.dataset.name = part.name; 
            pEl.style.width = w + 'px'; pEl.style.height = h + 'px'; 
            pEl.style.backgroundImage = `url(${img.src})`; 
            pEl.style.backgroundPosition = `-${srcMeta.frame.x * scale}px -${srcMeta.frame.y * scale}px`; 
            pEl.style.backgroundSize = `${img.naturalWidth * scale}px ${img.naturalHeight * scale}px`; 
            pEl.style.backgroundRepeat = 'no-repeat'; 
            pEl.style.zIndex = finalDepth;
            pEl.style.left = (finalX * scale - w * ox) + 'px'; pEl.style.top = (finalY * scale - h * oy) + 'px';
            pEl.style.transformOrigin = `${ox * 100}% ${oy * 100}%`; pEl.style.transform = `rotate(${finalAngle}deg)`;

            const pivotMark = document.createElement('div');
            pivotMark.style.position = 'absolute'; pivotMark.style.width = '6px'; pivotMark.style.height = '6px';
            pivotMark.style.background = (activeAnimName === 'setup') ? 'red' : '#ffc107'; pivotMark.style.borderRadius = '50%'; pivotMark.style.border = '1px solid white';
            pivotMark.style.left = (w * ox - 3) + 'px'; pivotMark.style.top = (h * oy - 3) + 'px'; pEl.appendChild(pivotMark);

            if (!animPlaybackInterval) {
                pEl.style.cursor = 'grab';
                pEl.addEventListener('mousedown', function(e) {
                    if (e.button !== 0) return; e.stopPropagation(); e.preventDefault();
                    selectSetupPart(pEl, part); mode = 'part-dragging';
                    const rect = motionContainer.getBoundingClientRect();
                    startX = Math.floor((e.clientX - rect.left) / zoomLevel); startY = Math.floor((e.clientY - rect.top) / zoomLevel);
                    
                    if (activeAnimName === 'setup') { 
                        partInitialX = part.x; partInitialY = part.y; 
                    } else { 
                        initAnimFrameNodePath(); 
                        const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts; 
                        // 🌟 モーション時は「純粋な移動量」をドラッグ初期値にする
                        partInitialX = partsNode[part.name]?.x !== undefined ? partsNode[part.name].x : 0; 
                        partInitialY = partsNode[part.name]?.y !== undefined ? partsNode[part.name].y : 0; 
                    }
                    pEl.style.cursor = 'grabbing';
                });
            } else { pEl.style.cursor = 'not-allowed'; }

            charRoot.appendChild(pEl);
            if (targetPartData && targetPartData.name === part.name) { pEl.classList.add('border', 'border-success', 'shadow'); }
        });
        
        renderUsedPartsPalette();
    }

    function initAnimFrameNodePath() {
        if (!currentMotionObj.animations[activeAnimName]) currentMotionObj.animations[activeAnimName] = { fps:6, loop:true, frames:[] };
        const anim = currentMotionObj.animations[activeAnimName];
        if (!anim.frames[currentFrameIndex]) anim.frames[currentFrameIndex] = { parts: {} };
        if (!anim.frames[currentFrameIndex].parts) anim.frames[currentFrameIndex].parts = {};
    }

    function selectSetupPart(element, partData) {
        charRoot.querySelectorAll('.motion-spawned-part').forEach(el => el.classList.remove('border', 'border-success', 'shadow'));
        element.classList.add('border', 'border-success', 'shadow');
        targetPartData = partData; activeMotionElement = element;
        
        if (document.activeElement !== inpName) inpName.value = partData.name; 
        if (document.activeElement !== inpFrame) inpFrame.value = partData.frame;

        if (activeAnimName === 'setup') {
            if (document.activeElement !== inpX) inpX.value = partData.x; 
            if (document.activeElement !== inpY) inpY.value = partData.y; 
            if (document.activeElement !== inpAngle) inpAngle.value = 0;
            document.getElementById('box-part-angle').classList.add('opacity-50'); document.getElementById('box-part-depth').classList.remove('d-none'); document.getElementById('box-part-ox').classList.remove('d-none'); document.getElementById('box-part-oy').classList.remove('d-none');
        } else {
            initAnimFrameNodePath(); const pNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts[partData.name] || {};
            // 🌟 モーション時はインプット欄に純粋な「移動量(オフセット)」を表示（無ければ0）
            if (document.activeElement !== inpX) inpX.value = pNode.x !== undefined ? pNode.x : 0; 
            if (document.activeElement !== inpY) inpY.value = pNode.y !== undefined ? pNode.y : 0; 
            if (document.activeElement !== inpAngle) inpAngle.value = pNode.angle !== undefined ? pNode.angle : 0;
            if (document.activeElement !== inpDepth) inpDepth.value = pNode.depth !== undefined ? pNode.depth : (partData.depth || 1);

            document.getElementById('box-part-angle').classList.remove('opacity-50'); 
            document.getElementById('box-part-depth').classList.remove('d-none'); 
            document.getElementById('box-part-ox').classList.add('d-none'); 
            document.getElementById('box-part-oy').classList.add('d-none');
        }
        if (activeAnimName === 'setup' && document.activeElement !== inpDepth) inpDepth.value = partData.depth || 1; 
        if (document.activeElement !== inpOx) inpOx.value = partData.originX !== undefined ? partData.originX : 0.5; 
        if (document.activeElement !== inpOy) inpOy.value = partData.originY !== undefined ? partData.originY : 0.5;

        renderUsedPartsPalette();
    }

    window.addEventListener('mousemove', function (e) {
        if (mode === 'part-dragging' && activeMotionElement && targetPartData) {
            const rect = motionContainer.getBoundingClientRect();
            let currentX = Math.floor((e.clientX - rect.left) / zoomLevel); let currentY = Math.floor((e.clientY - rect.top) / zoomLevel);
            let deltaX = currentX - startX; const deltaY = currentY - startY;
            const scale = currentMotionObj.physics.globalPartScale || 1.0;
            if (activeForm === 'left') deltaX = -deltaX;

            let newX = Math.round(partInitialX + (deltaX / scale)); let newY = Math.round(partInitialY + (deltaY / scale));
            inpX.value = newX; inpY.value = newY;

            try {
                let frameName = targetPartData.frame;
                if (currentMotionObj.forms?.[activeForm]?.[targetPartData.name]) { 
                    frameName = currentMotionObj.forms[activeForm][targetPartData.name]; 
                }
                if (activeAnimName !== 'setup') {
                    const anim = currentMotionObj.animations[activeAnimName];
                    const animFrameParts = anim?.frames?.[currentFrameIndex]?.parts;
                    if (animFrameParts?.[targetPartData.name]?.frame) {
                        frameName = animFrameParts[targetPartData.name].frame;
                    }
                }

                const srcMeta = currentAtlasObj.textures[0].frames.find(f => f.filename === frameName);
                if (!srcMeta) return;

                const w = srcMeta.frame.w * scale; const h = srcMeta.frame.h * scale;
                const ox = targetPartData.originX !== undefined ? targetPartData.originX : 0.5; const oy = targetPartData.originY !== undefined ? targetPartData.originY : 0.5;
                
                // 🌟【リアルタイムドラッグ修復】モーション時はベース位置 ＋ 移動量でカーソル追従描画
                let dispX = (activeAnimName === 'setup') ? newX : (targetPartData.x + newX); 
                let dispY = (activeAnimName === 'setup') ? newY : (targetPartData.y + newY); 
                
                activeMotionElement.style.left = (dispX * scale - w * ox) + 'px'; activeMotionElement.style.top = (dispY * scale - h * oy) + 'px';
            } catch(err){}
        }
    });

    window.addEventListener('mouseup', function () {
        if (mode === 'part-dragging' && targetPartData) {
            const xVal = parseInt(inpX.value) || 0; const yVal = parseInt(inpY.value) || 0;
            if (activeAnimName === 'setup') { 
                targetPartData.x = xVal; targetPartData.y = yVal; 
            } else { 
                initAnimFrameNodePath(); 
                const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts; 
                if (!partsNode[targetPartData.name]) partsNode[targetPartData.name] = {}; 
                partsNode[targetPartData.name].x = xVal; partsNode[targetPartData.name].y = yVal; 
            }
            if (activeMotionElement) activeMotionElement.style.cursor = 'grab';
            updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart();
        }
        mode = 'idle';
    });

    [inpHbW, inpHbH, inpFootY, inpOffsetX, inpPhysScale].forEach(input => {
        input.addEventListener('input', function() {
            if (!currentMotionObj) return;
            currentMotionObj.physics.hitboxWidth = parseInt(inpHbW.value) || 0; currentMotionObj.physics.hitboxHeight = parseInt(inpHbH.value) || 0; currentMotionObj.physics.footY = parseInt(inpFootY.value) || 0; currentMotionObj.physics.offsetX = parseInt(inpOffsetX.value) || 0; currentMotionObj.physics.globalPartScale = parseFloat(inpPhysScale.value) || 1.0;
            updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart();
        });
    });

    [inpX, inpY, inpAngle, inpDepth, inpOx, inpOy].forEach(input => {
        input.addEventListener('input', function() {
            if (!targetPartData) return; const val = parseFloat(this.value) || 0;
            if (activeAnimName === 'setup') {
                if (this === inpX) targetPartData.x = Math.round(val); 
                if (this === inpY) targetPartData.y = Math.round(val); 
                if (this === inpDepth) targetPartData.depth = Math.round(val); 
                if (this === inpOx) targetPartData.originX = val; 
                if (this === inpOy) targetPartData.originY = val;
            } else {
                initAnimFrameNodePath(); 
                const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts; 
                if (!partsNode[targetPartData.name]) partsNode[targetPartData.name] = {}; 
                const pNode = partsNode[targetPartData.name]; 
                
                if (this === inpX) pNode.x = Math.round(val); 
                if (this === inpY) pNode.y = Math.round(val); 
                if (this === inpAngle) pNode.angle = Math.round(val);
                if (this === inpDepth) pNode.depth = Math.round(val); 
            }
            updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart();
        });
    });

    btnDirRight.addEventListener('click', () => selectForm('right', btnDirRight, btnDirLeft, btnDirFront));
    btnDirLeft.addEventListener('click', () => selectForm('left', btnDirLeft, btnDirRight, btnDirFront));
    btnDirFront.addEventListener('click', () => selectForm('front', btnDirFront, btnDirRight, btnDirLeft));

    function selectForm(formName, activeBtn, inactiveBtn1, inactiveBtn2) {
        activeForm = formName; activeBtn.classList.add('active'); inactiveBtn1.classList.remove('active'); inactiveBtn2.classList.remove('active');
        renderMotionFrames(); reselectCurrentPart();
    }

    animSelect.addEventListener('change', function() {
        stopAnimationPlayback(); activeAnimName = this.value; currentFrameIndex = 0;
        if (activeAnimName === 'setup') {
            playControlsBox.style.display = 'none'; timelineBox.style.display = 'none';
            animFpsBox.style.setProperty('display', 'none', 'important'); 
            badgeMode.className = "badge bg-primary me-2"; badgeMode.textContent = "セットアップポーズ"; lblPartX.textContent = "相対 X"; lblPartY.textContent = "相対 Y";
        } else {
            playControlsBox.style.display = 'inline-flex'; timelineBox.style.display = 'inline-flex';
            animFpsBox.style.setProperty('display', 'flex', 'important'); 
            const anim = currentMotionObj.animations[activeAnimName];
            if (anim) { inpAnimFps.value = anim.fps || 6; }
            badgeMode.className = "badge bg-success me-2"; badgeMode.textContent = "タイムline編集ポーズ"; lblPartX.textContent = "コマ追加 X"; lblPartY.textContent = "コマ追加 Y"; refreshTimelineLabel();
        }
        renderMotionFrames();
    });

    function refreshTimelineLabel() { const anim = currentMotionObj.animations[activeAnimName]; const max = anim?.frames?.length || 0; lblFrameIndex.textContent = `コマ: ${max > 0 ? currentFrameIndex + 1 : 0} / ${max}`; }
    
    btnFramePrev.addEventListener('click', () => { 
        const anim = currentMotionObj.animations[activeAnimName];
        if (anim?.frames && anim.frames.length > 0) {
            if (currentFrameIndex > 0) { currentFrameIndex--; } else { currentFrameIndex = anim.frames.length - 1; }
            refreshTimelineLabel(); renderMotionFrames(); reselectCurrentPart(); 
        }
    });

    btnFrameNext.addEventListener('click', () => { 
        const anim = currentMotionObj.animations[activeAnimName]; 
        if (anim?.frames && anim.frames.length > 0) { 
            if (currentFrameIndex < anim.frames.length - 1) { currentFrameIndex++; } else { currentFrameIndex = 0; }
            refreshTimelineLabel(); renderMotionFrames(); reselectCurrentPart(); 
        } 
    });

    btnFrameDel.addEventListener('click', function() {
        const anim = currentMotionObj.animations[activeAnimName];
        if (!anim || !anim.frames || anim.frames.length === 0) return;
        if (anim.frames.length <= 1) { alert('これ以上コマを削除できません。'); return; }

        if (confirm(`現在選択中のコマ（${currentFrameIndex + 1}番目のコマ）を削除しますか？`)) {
            anim.frames.splice(currentFrameIndex, 1);
            if (currentFrameIndex >= anim.frames.length) { currentFrameIndex = anim.frames.length - 1; }
            refreshTimelineLabel(); updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart();
        }
    });
    
    btnFrameAdd.addEventListener('click', () => { initAnimFrameNodePath(); currentMotionObj.animations[activeAnimName].frames.splice(currentFrameIndex + 1, 0, { parts: {} }); currentFrameIndex++; refreshTimelineLabel(); updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart(); });
    btnFrameCopy.addEventListener('click', () => { initAnimFrameNodePath(); const clonedFrame = JSON.parse(JSON.stringify(currentMotionObj.animations[activeAnimName].frames[currentFrameIndex])); currentMotionObj.animations[activeAnimName].frames.splice(currentFrameIndex + 1, 0, clonedFrame); currentFrameIndex++; refreshTimelineLabel(); updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart(); });

    btnAddNewAnim.addEventListener('click', function() {
        const newName = prompt('新規登録するモーション名を入力してください:'); if (!newName || newName.trim() === '') return; initMotionJsonStructure(); if (currentMotionObj.animations[newName.trim()]) { alert('既に存在します。'); return; }
        currentMotionObj.animations[newName.trim()] = { fps: 6, loop: true, frames: [{ parts: {} }] }; updateMotionTextarea(); syncAnimationSelectOptions(); animSelect.value = newName.trim(); animSelect.dispatchEvent(new Event('change'));
    });
    
    inpAnimFps.addEventListener('input', function() {
        if (activeAnimName === 'setup' || !currentMotionObj) return;
        const anim = currentMotionObj.animations[activeAnimName];
        if (anim) { anim.fps = Math.max(1, parseInt(this.value) || 6); updateMotionTextarea(); }
    });

    btnDeleteSetup.addEventListener('click', function() {
        if (!targetPartData) { alert('削除するパーツが選択されていません。'); return; }

        if (activeAnimName === 'setup') {
            if (confirm(`現在の向き [${activeForm}] から部位 [${targetPartData.name}] を削除しますか？\n(他の向きに登録されている場合は残ります)`)) {
                if (!currentMotionObj.forms) currentMotionObj.forms = { right:{}, left:{}, front:{} };
                if (!currentMotionObj.forms[activeForm]) currentMotionObj.forms[activeForm] = {};
                
                currentMotionObj.forms[activeForm][targetPartData.name] = 'transparent';

                // 🌟【厳密判定】他の向きで基本画像表示中(undefined)か個別画像が入っているかを正しく探す
                const allForms = ['right', 'left', 'front'];
                const isUsedInOtherForm = allForms.some(form => {
                    if (form === activeForm) return false; 
                    const imgName = currentMotionObj.forms?.[form]?.[targetPartData.name];
                    return imgName !== 'transparent';
                });

                if (!isUsedInOtherForm) {
                    currentMotionObj.setup.parts = currentMotionObj.setup.parts.filter(p => p.name !== targetPartData.name);
                    allForms.forEach(form => {
                        if (currentMotionObj.forms?.[form]?.[targetPartData.name]) {
                            delete currentMotionObj.forms[form][targetPartData.name]; 
                        }
                    });
                }
                targetPartData = null; resetFields(); 
            }
        } else {
            if (confirm(`このモーションの現在のコマから [${targetPartData.name}] を削除して消去しますか？`)) {
                initAnimFrameNodePath();
                const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts;
                if (!partsNode[targetPartData.name]) partsNode[targetPartData.name] = {};
                partsNode[targetPartData.name].frame = "transparent";
            }
        }
        updateMotionTextarea(); renderMotionFrames(); reselectCurrentPart();
    });

    btnAnimPlay.addEventListener('click', () => {
        const anim = currentMotionObj.animations[activeAnimName]; if (!anim?.frames?.length) return;
        btnAnimPlay.classList.add('d-none'); btnAnimStop.classList.remove('d-none'); timelineBox.classList.add('opacity-50'); propBox.className = 'd-none';
        animPlaybackInterval = setInterval(() => { currentFrameIndex++; if (currentFrameIndex >= anim.frames.length) { if (anim.loop !== false) { currentFrameIndex = 0; } else { stopAnimationPlayback(); return; } } refreshTimelineLabel(); renderMotionFrames(); }, Math.round(1000 / (anim.fps || 4)));
    });
    btnAnimStop.addEventListener('click', stopAnimationPlayback);
    function stopAnimationPlayback() { if (animPlaybackInterval) { clearInterval(animPlaybackInterval); animPlaybackInterval = null; } btnAnimPlay.classList.remove('d-none'); btnAnimStop.classList.add('d-none'); timelineBox.classList.remove('opacity-50'); if (activeAnimName !== 'setup') { propBox.className = 'p-3 bg-light rounded border shadow-inner'; } currentFrameIndex = 0; refreshTimelineLabel(); renderMotionFrames(); reselectCurrentPart(); }

    img.onload = () => { initMotionJsonStructure(); buildPartPalette(); syncAnimationSelectOptions(); renderMotionFrames(); };
    if (img.complete) { initMotionJsonStructure(); buildPartPalette(); syncAnimationSelectOptions(); renderMotionFrames(); }
    txtMotion.addEventListener('input', () => { initMotionJsonStructure(); syncAnimationSelectOptions(); renderMotionFrames(); });
});
</script>

<style>
.motion-spawned-part { box-sizing: content-box !important; }
.motion-spawned-part div { pointer-events: none; }
#motion-used-list div { transition: background-color 0.15s ease, border-color 0.15s ease; }
#motion-used-list div:hover { background-color: rgba(0, 0, 0, 0.04) !important; }
</style>