<form action="{{ route('admin.game.asset.update') }}" method="POST">
    @csrf
    <input type="hidden" name="character_id" value="{{ $character->id ?? '' }}">
    <input type="hidden" name="game_key" value="{{ $gameKey ?? 'twin_facer' }}">
    <input type="hidden" name="mode" value="motion">
    <input type="hidden" name="filename" value="{{ $activeFile }}">
    <input type="hidden" name="atlas_content" value="{{ $atlasContent }}">

    <div class="row m-0">
        {{-- 🎨 左半分：編集キャンバスエリア --}}
        <div class="col-md-8 ps-0 pe-2">
            <div class="card mb-3 shadow-sm border-secondary">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <h6 class="mb-0 small fw-bold">
                        <span id="editor-badge-mode" class="badge bg-primary me-2">セットアップモード</span><code>{{ $activeFile }}</code>
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                        <span class="btn btn-outline-light disabled text-white fw-bold py-0 small" id="lbl-zoom" style="opacity: 1; min-width: 55px; font-size:11px;">100%</span>
                        <button type="button" class="btn btn-outline-light px-2 py-0" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                    </div>
                </div>
                <div class="card-body bg-light border-bottom p-2 d-flex align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-secondary font-monospace" style="font-size:10px;">向き</span>
                        <div class="btn-group btn-group-sm" role="group" id="direction-btn-group">
                            @if(($game->view_mode ?? '') === 'side_view_flip')
                                <button type="button" class="btn btn-xs btn-outline-primary active fw-bold btn-dir-toggle" data-form="right">右(ベース編集)</button>
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold btn-dir-toggle" data-form="left">左(反転プレビュー)</button>
                            @elseif(($game->view_mode ?? '') === 'side_view_separate')
                                <button type="button" class="btn btn-xs btn-outline-primary active fw-bold btn-dir-toggle" data-form="right">右向き定義</button>
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold btn-dir-toggle" data-form="left">左向き定義</button>
                            @elseif(($game->view_mode ?? 'side_view') === 'top_down')
                                <button type="button" class="btn btn-xs btn-outline-primary active fw-bold btn-dir-toggle" data-form="front">前</button>
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold btn-dir-toggle" data-form="back">後</button>
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold btn-dir-toggle" data-form="side">横</button>
                            @else
                                <button type="button" class="btn btn-xs btn-outline-primary active fw-bold btn-dir-toggle" data-form="default">共通</button>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-grow-1 flex-wrap">
                        <select id="sim-anim-select" class="form-select form-select-sm font-monospace w-auto py-0" style="min-width: 130px; height:24px; font-size:11px;">
                            <option value="setup">⚙️ 0. setupポーズ</option>
                        </select>
                        <div id="anim-fps-box" class="d-flex align-items-center gap-1 border rounded bg-white px-1 shadow-sm" style="display: none !important; height: 24px;">
                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 9px; padding:2px 4px;">FPS</span>
                            <input type="number" id="anim-fps" class="form-control form-control-sm text-center border-0 p-0 m-0 fw-bold text-primary font-monospace" style="width: 25px; box-shadow: none; font-size:11px;" min="1" max="60" value="6">
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-dark px-1 py-0 fw-bold" id="btn-add-new-anim" title="新規モーションを追加" style="height:24px;">+追加</button>
                        <div class="btn-group btn-group-sm" id="anim-play-controls" style="display:none;">
                            <button type="button" class="btn btn-xs btn-success px-2 font-weight-bold" id="btn-anim-play" style="height:24px;"><i class="bi bi-play-fill"></i> 再生</button>
                            <button type="button" class="btn btn-xs btn-danger px-2 font-weight-bold d-none" id="btn-anim-stop" style="height:24px;"><i class="bi bi-stop-fill"></i> 停止</button>
                        </div>
                        <div class="btn-group btn-group-sm border rounded bg-white p-0 shadow-sm" id="timeline-step-box" style="display:none; height:24px; align-items:center;">
                            <button type="button" class="btn btn-link btn-sm p-0 px-2 text-dark border-0" id="btn-frame-prev" title="前のコマへ"><i class="bi bi-chevron-left"></i> ◀</button>
                            <span class="font-monospace small fw-bold text-primary" id="lbl-frame-index" style="min-width: 55px; text-align:center; font-size:10px;">1 / 1</span>
                            <button type="button" class="btn btn-link btn-sm p-0 px-2 text-dark border-0" id="btn-frame-next" title="次のコマへ"> ▶<i class="bi bi-chevron-right"></i></button>
                            <button type="button" class="btn btn-link btn-sm p-0 px-1 text-primary border-0 ms-1" id="btn-frame-copy" title="複製"><i class="bi bi-copy"></i> 複製</button>
                            <button type="button" class="btn btn-link btn-sm p-0 px-1 text-success border-0" id="btn-frame-add" title="挿入"><i class="bi bi-file-earmark-plus"></i> 挿入</button>
                            <button type="button" class="btn btn-link btn-sm p-0 px-1 text-danger border-0" id="btn-frame-del" title="削除"><i class="bi bi-file-earmark-minus"></i> 削除</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 text-center position-relative bg-secondary" style="height: 450px; overflow: auto;">
                    <div id="motion-canvas-container" class="position-relative m-2 shadow text-start d-inline-block" style="width: 600px; height: 420px; background-color: #e5e5e5; background-image: linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, #e5e5e5 25%, #e5e5e5 75%, #ccc 75%); background-size: 20px 20px; background-position: 0 0, 10px 10px; overflow: hidden; user-select: none;">
                        <div id="guide-cross-v" style="position: absolute; left: 300px; top: 0; width: 1px; height: 100%; background: rgba(0,0,255,0.15); pointer-events: none; z-index: 9990;"></div>
                        <div id="guide-cross-h" style="position: absolute; left: 0; top: 220px; width: 100%; height: 1px; background: rgba(0,0,255,0.15); pointer-events: none; z-index: 9990;"></div>
                        <div id="guide-hitbox" style="position: absolute; border: 2px dashed rgba(255, 0, 0, 0.4); background: rgba(255,0,0,0.01); pointer-events: none; z-index: 9991; display: none;"></div>
                        <div id="guide-foot-y" style="position: absolute; left: 0; width: 100%; height: 0; border-top: 2px solid rgba(255, 193, 7, 0.8); pointer-events: none; z-index: 9992; display: none;"></div>
                        <span id="lbl-guide-foot" style="position: absolute; left: 10px; color: #ffc107; font-size: 9px; font-weight: bold; pointer-events: none; z-index: 9992; display: none;">👠 足元床面 (Foot Y)</span>
                        <div id="guide-top-y" style="position: absolute; left: 0; width: 100%; height: 0; border-top: 2px dashed rgba(220, 53, 69, 0.6); pointer-events: none; z-index: 9992; display: none;"></div>
                        <span id="lbl-guide-top" style="position: absolute; left: 10px; color: #dc3545; font-size: 9px; font-weight: bold; pointer-events: none; z-index: 9992; display: none;">👑 頭上上限</span>
                        <div id="guide-wall-l" style="position: absolute; top: 0; width: 0; height: 100%; border-left: 2px dashed rgba(25, 135, 84, 0.6); pointer-events: none; z-index: 9992; display: none;"></div>
                        <div id="guide-wall-r" style="position: absolute; top: 0; width: 0; height: 100%; border-left: 2px dashed rgba(25, 135, 84, 0.6); pointer-events: none; z-index: 9992; display: none;"></div>
                        <span id="lbl-guide-wall" style="position: absolute; top: 10px; color: #198754; font-size: 9px; font-weight: bold; pointer-events: none; z-index: 9992; display: none;">🚧 壁判定 (Wall X)</span>
                        <div id="motion-character-root" style="position: absolute; left: 300px; top: 220px; width: 0; height: 0; transform-origin: 0px 0px; z-index: 100;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🛠️ 右半分：プロパティ・セッティング＆構造データ出力エリア (6/12列) --}}
        <div class="col-md-4 ps-2 pe-0">
            <div class="card mb-3 shadow-sm border-dark">
                <div class="card-header bg-dark text-white py-2 small fw-bold"><i class="bi bi-sliders"></i> コントロール ＆ パーツプロパティ</div>
                <div class="card-body p-2 bg-light">
                    {{-- 物理設定セクション --}}
                    <div class="p-2 mb-2 bg-dark text-white rounded border" style="font-size:11px;">
                        <span class="text-info font-monospace fw-bold"><i class="bi bi-shield-fill"></i> 選択中の向きの物理設定 (physics)</span>
                        <div class="row g-1 mt-1">
                            <div class="col-4">
                                <label class="form-label m-0 p-0 font-monospace text-info fw-bold" style="font-size:9px;">Hitbox 幅</label>
                                <input type="number" id="phys-hb-w" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4">
                                <label class="form-label m-0 p-0 font-monospace text-danger fw-bold" style="font-size:9px;">Hitbox 高</label>
                                <input type="number" id="phys-hb-h" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4">
                                <label class="form-label m-0 p-0 font-monospace text-warning fw-bold" style="font-size:9px;">Foot Y</label>
                                <input type="number" id="phys-foot-y" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-6 mt-1">
                                <label class="form-label m-0 p-0 font-monospace" style="font-size:9px;">OffsetX</label>
                                <input type="number" id="phys-offset-x" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-6 mt-1">
                                <label class="form-label m-0 p-0 font-monospace text-warning" style="font-size:9px;">globalPartScale</label>
                                <input type="number" id="phys-scale" class="form-control form-control-sm bg-secondary text-white border-0 py-0 text-center" step="0.05" style="height:22px; font-size:11px;">
                            </div>
                        </div>
                    </div>

                    {{-- パーツ詳細設定セクション --}}
                    <div id="part-properties-box" class="p-2 bg-white rounded border border-secondary shadow-sm mb-2">
                        <div class="fw-bold text-secondary font-monospace small mb-1 d-flex justify-content-between align-items-center" style="font-size:11px;">
                            <span><i class="bi bi-pencil-square"></i> 選択中の部位設定</span>
                            <button type="button" id="btn-delete-setup-part" class="btn btn-xs btn-outline-danger font-monospace py-0 px-2 text-xs" style="font-size:10px; height:18px;">キャンバスから削除</button>
                        </div>
                        <div class="row g-1">
                            <div class="col-6">
                                <label class="form-label small font-monospace text-success m-0" style="font-size:10px;">部位名 (name)</label>
                                <input type="text" id="part-name" class="form-control form-control-sm font-monospace py-0" readonly style="background-color: #e9ecef; height:22px; font-size:11px;">
                            </div>
                            <div class="col-6">
                                <label class="form-label small font-monospace text-muted m-0" style="font-size:10px;">描画画像 (frame)</label>
                                <input type="text" id="part-frame" class="form-control form-control-sm font-monospace py-0" readonly style="background-color: #e9ecef; height:22px; font-size:11px;">
                            </div>
                            <div class="col-4">
                                <label id="lbl-part-x" class="form-label small font-monospace text-primary fw-bold m-0" style="font-size:10px;">相対 X</label>
                                <input type="number" id="part-x" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4">
                                <label id="lbl-part-y" class="form-label small font-monospace text-primary fw-bold m-0" style="font-size:10px;">相対 Y</label>
                                <input type="number" id="part-y" class="form-control form-control-sm py-0 text-center" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4" id="box-part-angle">
                                <label class="form-label small font-monospace text-warning fw-bold m-0" style="font-size:10px;">角度 (°)</label>
                                <input type="number" id="part-angle" class="form-control form-control-sm py-0 text-center" min="-360" max="360" value="0" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4" id="box-part-depth">
                                <label class="form-label small font-monospace m-0" style="font-size:10px;">重なり (Z)</label>
                                <input type="number" id="part-depth" class="form-control form-control-sm py-0 text-center" min="0" max="999" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4" id="box-part-ox">
                                <label class="form-label small font-monospace text-danger m-0" style="font-size:10px;">軸 ox</label>
                                <input type="number" id="part-ox" class="form-control form-control-sm py-0 text-center" step="0.05" min="0" max="1" style="height:22px; font-size:11px;">
                            </div>
                            <div class="col-4" id="box-part-oy">
                                <label class="form-label small font-monospace text-danger m-0" style="font-size:10px;">軸 oy</label>
                                <input type="number" id="part-oy" class="form-control form-control-sm py-0 text-center" step="0.05" min="0" max="1" style="height:22px; font-size:11px;">
                            </div>
                        </div>
                    </div>

                    {{-- 構造データ＆保存ボタンセクション --}}
                    <div class="border rounded bg-white p-2 shadow-inner">
                        <div class="mb-1 small font-monospace fw-bold text-success d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span style="font-size:11px;"><i class="bi bi-filetype-json"></i> ③ モーション構造データ JSON</span>
                            <div class="d-flex gap-1 align-items-center">
                                <button type="submit" class="btn btn-success text-white fw-bold"><i class="bi bi-save me-1"></i>保存</button>
                                @if(!empty($character))
                                    <button type="button" class="btn btn-danger text-white fw-bold"
                                            onclick="if(confirm('現在DBに保存されている「このキャラクターのみ」を抽出してゲームに反映します。よろしいですか？')) { window.location.href='{{ route('admin.game.publish', ['gameKey' => $gameKey, 'type' => 'character', 'targetKey' => $character->character_key]) }}'; }">
                                        <i class="bi bi-trash"></i> 反映
                                    </button>
                                @endif
                            </div>
                        </div>
                        <textarea name="motion_content" id="motion-textarea" class="form-control font-monospace small" rows="6" style="font-size: 10px; tab-size: 2; height:150px;">{{ $motionContent }}</textarea>
                        <textarea id="all-atlases-json" class="d-none">{!! json_encode($atlasesMap ?? [], JSON_UNESCAPED_UNICODE) !!}</textarea>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>

<img id="sprite-target-img" src="{{ asset('storage/sprite_sheet/' . $activeFileCategory . '/' . $activeFile) }}" class="d-none">

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 🌟 親ゲームで設定された視点モードをJS側へ同期
    const currentViewMode = "{{ $game->view_mode ?? 'side_view' }}";

    // 視点モードに存在する正しい方向のリストを取得　allForms:設定可能な全方向、activeForm:初期アクティブな方向
    let allForms;
    let activeForm;
    if(currentViewMode === 'side_view') {
        allForms = ['right', 'left'];
        activeForm = 'right';
    } else if(currentViewMode === 'top_down') {
        allForms = ['front', 'back', 'side'];
        activeForm = 'front';
    } else {
        allForms = ['default'];
        activeForm = 'default';
    }


    const motionContainer = document.getElementById('motion-canvas-container');
    const charRoot = document.getElementById('motion-character-root'); 
    const txtAtlases = document.getElementById('all-atlases-json');
    const txtMotion = document.getElementById('motion-textarea');
    const paletteFileSelect = document.getElementById('motion-palette-file-select'); 
    
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

    //キャンバス設定定数 (Configファイル/Controller経由でPHPから注入)
    const CANVAS_W = {{ $editorConfig['canvas_w'] ?? 600 }};
    const CANVAS_H = {{ $editorConfig['canvas_h'] ?? 420 }};
    const ORIGIN_X = {{ $editorConfig['origin_x'] ?? 300 }};
    const ORIGIN_Y = {{ $editorConfig['origin_y'] ?? 220 }};

    // 初期スタイル適用
    motionContainer.style.width = CANVAS_W + 'px';
    motionContainer.style.height = CANVAS_H + 'px';
    charRoot.style.left = ORIGIN_X + 'px';
    charRoot.style.top = ORIGIN_Y + 'px';
    const gCrossV = document.getElementById('guide-cross-v');
    const gCrossH = document.getElementById('guide-cross-h');
    if (gCrossV) gCrossV.style.left = ORIGIN_X + 'px';
    if (gCrossH) gCrossH.style.top = ORIGIN_Y + 'px';

    // 🌟 親ビューのJS側からも安全にGET参照できるよう、windowスコープに公開・バインドする
    window.SPRITE_SHEET_BASE_URL = "{{ asset('storage/sprite_sheet') }}/";
    let zoomLevel = 1.0; let mode = 'idle'; let startX = 0, startY = 0;
    
    // 🌟 データの共有不整合を防ぐため、メインデータオブジェクト類をすべてwindow直下に配置
    window.currentMotionObj = null;
    window.targetPartData = null;
    window.activeForm = activeForm;
    window.activeAnimName = 'setup';
    
    let activeMotionElement = null;
    let partInitialX = 0, partInitialY = 0;
    let activeAnimName = 'setup';
    let animPlaybackInterval = null; let currentFrameIndex = 0;

    function changeZoom(newZoom) {
        zoomLevel = Math.max(0.5, Math.min(4.0, newZoom));
        motionContainer.style.zoom = zoomLevel;
        document.getElementById('lbl-zoom').textContent = Math.round(zoomLevel * 100) + '%';
    }
    
    document.getElementById('btn-zoom-in').addEventListener('click', () => changeZoom(zoomLevel + 0.25));
    document.getElementById('btn-zoom-out').addEventListener('click', () => changeZoom(zoomLevel - 0.25));
    
    // 🌟【バグ修正】モーション画面側にボタンが存在しない場合でも、JSがTypeError即死を起こさないよう安全ガードを追加
    const btnZoomReset = document.getElementById('btn-zoom-reset');
    if (btnZoomReset) { btnZoomReset.addEventListener('click', () => changeZoom(1.0)); }
    
    motionContainer.addEventListener('wheel', function(e) {
        if (e.ctrlKey) { e.preventDefault(); changeZoom(e.deltaY < 0 ? zoomLevel + 0.25 : zoomLevel - 0.25); }
    }, { passive: false });

    function updateMotionTextarea() {
        if (!currentMotionObj) return;
        let jsonStr = JSON.stringify(currentMotionObj, null, 2);
        jsonStr = jsonStr.replace(/\{\s*\n\s*"name":\s*("[^"]+"),\s*(\n\s*"image":\s*"[^"]+",\s*)?\n\s*"frame":\s*("[^"]+"),\s*\n\s*"x":\s*(-?\d+),\s*\n\s*"y":\s*(-?\d+),\s*\n\s*"depth":\s*(-?\d+),\s*\n\s*"originX":\s*([0-9.]+),\s*\n\s*"originY":\s*([0-9.]+)\s*\n\s*\}/g, function(match, p1, p2, p3, p4, p5, p6, p7, p8) {
            const imgLine = p2 ? p2.replace(/\n\s*/, ' ') : ' ';
            return `{ "name": ${p1},${imgLine}"frame": ${p3}, "x": ${p4}, "y": ${p5}, "depth": ${p6}, "originX": ${p7}, "originY": ${p8} }`;
        });
        txtMotion.value = jsonStr;
    }

    // 🌟【最適化】パレット生成処理
    function buildPartPalette() {
        if (!paletteList || !paletteFileSelect) return; paletteList.innerHTML = '';
        try {
            const selectedImgFile = paletteFileSelect.value; 
            const allAtlases = JSON.parse(txtAtlases.value);
            const currentAtlasObj = allAtlases[selectedImgFile]; // 二重パースを廃止
            
            if (!currentAtlasObj || !currentAtlasObj.textures || !currentAtlasObj.textures[0]) {
                paletteList.innerHTML = '<div class="text-muted small p-2">アトラスデータがありません</div>';
                return;
            }
            const textureMeta = currentAtlasObj.textures[0];

            // 🌟 もしフレームデータ（切り出し枠）が一個もない場合の親切アナウンス
            if (!textureMeta.frames || textureMeta.frames.length === 0) {
                paletteList.innerHTML = '<div class="text-muted text-center small p-3 text-warning border rounded bg-warning bg-opacity-10 style="font-size:12px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>パーツがまだありません。<br><span class="text-muted small" style="font-size:11px;">先に上部タブの「① 切り出し座標設定」から、この画像に対して赤い切り出し枠を登録・保存してください。</span></div>';
                return;
            }

            textureMeta.frames.forEach(f => {
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
                thumb.style.width = thumbW + 'px'; thumb.style.height = thumbH + 'px'; 
                // 💡 画像のカテゴリ（character等）をアトラスから取得してURLに挟む
                const imgCategory = currentAtlasObj.category || 'character';
                thumb.style.backgroundImage = `url(${SPRITE_SHEET_BASE_URL}${imgCategory}/${selectedImgFile})`;
                thumb.style.backgroundPosition = `-${f.frame.x * thumbScale}px -${f.frame.y * thumbScale}px`;
                thumb.style.backgroundSize = `${(textureMeta.size?.w || 512) * thumbScale}px ${(textureMeta.size?.h || 512) * thumbScale}px`;
                thumb.style.backgroundRepeat = 'no-repeat'; thumb.style.border = '1px solid #dee2e6'; thumb.style.flexShrink = '0';
                
                const label = document.createElement('span');
                label.className = 'text-truncate font-monospace small flex-grow-1'; label.style.minWidth = '0'; label.textContent = f.filename;
                
                thumbWrapper.appendChild(thumb); btn.appendChild(thumbWrapper); btn.appendChild(label);

                //画像からパーツを選択して登録
                btn.addEventListener('click', () => {
                    initMotionJsonStructure();
                    
                    const defaultPartName = f.filename;
                    const partKeyName = prompt(`この画像を表示する「部位名(name)」を入力してください:\n(既存の部位名を入れるとそのコマの画像が差し替わります)`, defaultPartName);
                    if (!partKeyName) return;
                    const cleanName = partKeyName.trim();

                    if (activeAnimName === 'setup') {
                        if (!currentMotionObj.forms) currentMotionObj.forms = { right:{}, left:{}, front:{} };
                        const existingPart = currentMotionObj.setup.parts.find(p => p.name === cleanName);

                        if (existingPart) {
                            existingPart.image = selectedImgFile; 
                            if (!currentMotionObj.forms[activeForm]) currentMotionObj.forms[activeForm] = {};
                            currentMotionObj.forms[activeForm][cleanName] = f.filename;
                        } else {
                            const newPart = { name: cleanName, image: selectedImgFile, frame: f.filename, x: 0, y: 0, depth: currentMotionObj.setup.parts.length + 1, originX: 0.5, originY: 0.5 };
                            currentMotionObj.setup.parts.push(newPart);

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
                        const setupPart = currentMotionObj.setup.parts.find(p => p.name === cleanName);
                        const defaultDepth = setupPart ? (setupPart.depth ?? 1) : 1;

                        if (!partsNode[cleanName]) {
                            partsNode[cleanName] = { frame: f.filename, x: 0, y: 0, angle: 0, depth: defaultDepth };
                        } else {
                            partsNode[cleanName].frame = f.filename;
                            if (partsNode[cleanName].depth === undefined) partsNode[cleanName].depth = defaultDepth;
                        }
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
        } catch(e) { paletteList.innerHTML = '<div class="text-danger small">アトラスデータの解析エラー</div>'; }
    }

    if(paletteFileSelect) {
        paletteFileSelect.addEventListener('change', buildPartPalette);
    }

    function renderUsedPartsPalette() {
        if (!usedList) return; usedList.innerHTML = '';
        if (!currentMotionObj || !currentMotionObj.setup || !currentMotionObj.setup.parts) return;

        // 🌟 画像選択側（admin_game_sprite_sheet）と同様に、アトラスから矩形情報を引くためパース
        let allAtlases = {};
        try { allAtlases = JSON.parse(txtAtlases.value); } catch(e) { allAtlases = {}; }

        currentMotionObj.setup.parts.forEach(part => {
            const item = document.createElement('div');
            const isActive = (targetPartData && targetPartData.name === part.name);
            item.className = `d-flex align-items-center justify-content-between p-1 px-2 mb-1 rounded border ${isActive ? 'bg-success bg-opacity-25 border-success fw-bold text-success shadow-sm' : 'bg-white text-dark'}`;
            item.style.cursor = 'pointer'; item.style.fontSize = '12px';

            const pSrcImg = part.image || "{{ $activeFile }}"; 
            
            // 🌟 現在の向きやアニメ等で上書きされている最新のフレーム（パーツ名）を安全に特定
            let frameName = part.frame;
            if (currentMotionObj.forms?.[activeForm]?.[part.name]) { frameName = currentMotionObj.forms[activeForm][part.name]; }
            if (activeAnimName !== 'setup' && currentMotionObj.animations[activeAnimName]?.frames[currentFrameIndex]?.parts?.[part.name]?.frame) {
                frameName = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts[part.name].frame;
            }

            // 🌟 画像選択側と100%同じ仕様：アトラス定義からx, y, w, hを逆引きしてサムネイル用の器を生成
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
                thumb.style.backgroundImage = `url(${SPRITE_SHEET_BASE_URL}${partCategory}/${pSrcImg})`;
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

            // 🌟 サムネイルとテキストを横並びにブレンド結合するコンテナ
            const contentDiv = document.createElement('div');
            contentDiv.className = 'd-flex align-items-center flex-grow-1 min-w-0';
            contentDiv.appendChild(thumbWrapper);
            contentDiv.appendChild(leftDiv);

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

            item.appendChild(contentDiv); item.appendChild(delBtn); usedList.appendChild(item);
        });

        if (currentMotionObj.setup.parts.length === 0) {
            usedList.innerHTML = '<div class="text-muted text-center py-2" style="font-size:11px;">配置されているパーツはありません。</div>';
        }
    }

    function resetFields() {
        inpName.value = ''; inpFrame.value = ''; inpX.value = ''; inpY.value = ''; inpAngle.value = 0; inpDepth.value = 1; inpOx.value = 0.5; inpOy.value = 0.5;
    }

   function initMotionJsonStructure() {

        try { 
            let rawData = txtMotion.value.trim();
            currentMotionObj = JSON.parse(rawData || '{}'); 
            
            // 🌟【安全弁】DBのデータ状況により、万が一二重JSON文字列になっていた場合はもう一度自動でパースして確実にオブジェクトにする
            if (typeof currentMotionObj === 'string') {
                currentMotionObj = JSON.parse(currentMotionObj);
            }
            if (!currentMotionObj) throw new Error();
            
            if (Array.isArray(currentMotionObj.animations)) { currentMotionObj.animations = {}; }
            if (!currentMotionObj.forms || Array.isArray(currentMotionObj.forms)) { currentMotionObj.forms = {}; }
            
            // 🌟【クラッシュ防止】既存データに setup や parts 配列が欠落している場合、安全に空の器で自動初期化してJavaScriptの即死を防ぐ
            if (!currentMotionObj.setup || typeof currentMotionObj.setup !== 'object') { currentMotionObj.setup = { parts: [] }; }
            if (!Array.isArray(currentMotionObj.setup.parts)) { currentMotionObj.setup.parts = []; }
            
            // 🌟【お掃除】現在の視点モードに含まれない不要な方向キー（古いゴミデータ）があれば綺麗に自動削除
            Object.keys(currentMotionObj.forms).forEach(key => {
                if (!allForms.includes(key)) { delete currentMotionObj.forms[key]; }
            });
            if (currentMotionObj.physics && !Array.isArray(currentMotionObj.physics)) {
                Object.keys(currentMotionObj.physics).forEach(key => {
                    if (key !== 'default' && !allForms.includes(key)) { delete currentMotionObj.physics[key]; }
                });
            }

            if (!currentMotionObj.physics || Array.isArray(currentMotionObj.physics)) { currentMotionObj.physics = { default: {} }; }
            
            allForms.forEach(f => {
                if (!currentMotionObj.forms[f] || Array.isArray(currentMotionObj.forms[f])) currentMotionObj.forms[f] = {};
                if (!currentMotionObj.physics[f] || Array.isArray(currentMotionObj.physics[f])) currentMotionObj.physics[f] = {};
            });

            // 🌟【追加】お掃除＆初期化されたクリーンな構造をテキストエリアに即時反映させる
            updateMotionTextarea();
        }
        catch(e) { 
            // 完全な新規作成時に自動生成されるクリーンな初期JSON構造
            let defaultForms = {};
            let defaultPhysics = { default: { hitboxWidth: 118, hitboxHeight: 326, footY: 90, offsetX: 0, globalPartScale: 0.8 } };
            
            // 🌟【バグ修正】新規キャラ読み込み時、各方向の物理設定オブジェクトも漏れなく初期化する
            allForms.forEach(f => { 
                defaultForms[f] = {}; 
                defaultPhysics[f] = {};
            });

            currentMotionObj = { 
                physics: defaultPhysics, 
                setup: { parts: [] }, 
                forms: defaultForms, 
                animations: {} 
            }; 
            
            // 🌟【追加】ここでもテキストエリアに即時反映させる
            updateMotionTextarea();
        }
    }
    function syncAnimationSelectOptions() {
        // 🌟【修正】不要な再パースを削除し、メモリ上の最新データをそのままプルダウンに反映します
        while(animSelect.options.length > 1) { animSelect.remove(1); }
        if (currentMotionObj && currentMotionObj.animations) {
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

    // 🌟【最適化】キャンバス描画処理
    function renderMotionFrames() {
        charRoot.querySelectorAll('.motion-spawned-part').forEach(el => el.remove());

        if (!currentMotionObj) return; // 🌟オブジェクト自体が空の時は処理を安全にスキップ
        if (!currentMotionObj.physics) currentMotionObj.physics = { default: {} };
        if (!currentMotionObj.physics.default) currentMotionObj.physics.default = { hitboxWidth: 118, hitboxHeight: 326, footY: 90, offsetX: 0, globalPartScale: 0.8 };
        if (!currentMotionObj.physics[activeForm]) currentMotionObj.physics[activeForm] = {};

        const defPhys = currentMotionObj.physics.default;
        // 🌟【バグ修正】データが空の時でも絶対にTypeErrorクラッシュを起こさないよう、フォールバックの空オブジェクト「|| {}」を保証
        const formPhys = currentMotionObj.physics[activeForm] || {};

        const phys = {
            hitboxWidth: formPhys.hitboxWidth ?? defPhys.hitboxWidth ?? 118,
            hitboxHeight: formPhys.hitboxHeight ?? defPhys.hitboxHeight ?? 326,
            footY: formPhys.footY ?? defPhys.footY ?? 90,
            offsetX: formPhys.offsetX ?? defPhys.offsetX ?? 0,
            globalPartScale: formPhys.globalPartScale ?? defPhys.globalPartScale ?? 0.8
        };

        inpHbW.placeholder = defPhys.hitboxWidth ?? 118;
        inpHbH.placeholder = defPhys.hitboxHeight ?? 326;
        inpFootY.placeholder = defPhys.footY ?? 90;
        inpOffsetX.placeholder = defPhys.offsetX ?? 0;
        inpPhysScale.placeholder = defPhys.globalPartScale ?? 0.8;

        if (document.activeElement !== inpHbW) inpHbW.value = formPhys.hitboxWidth !== undefined ? formPhys.hitboxWidth : '';
        if (document.activeElement !== inpHbH) inpHbH.value = formPhys.hitboxHeight !== undefined ? formPhys.hitboxHeight : '';
        if (document.activeElement !== inpFootY) inpFootY.value = formPhys.footY !== undefined ? formPhys.footY : '';
        if (document.activeElement !== inpOffsetX) inpOffsetX.value = formPhys.offsetX !== undefined ? formPhys.offsetX : '';
        if (document.activeElement !== inpPhysScale) inpPhysScale.value = formPhys.globalPartScale !== undefined ? formPhys.globalPartScale : '';

        const scale = phys.globalPartScale;
        const absoluteFootY = ORIGIN_Y + (phys.footY || 0);
        const absoluteTopY = absoluteFootY - phys.hitboxHeight;
        const absoluteHitboxLeft = ORIGIN_X + (phys.offsetX || 0) - (phys.hitboxWidth / 2);

        gHitbox.style.display = 'block'; gHitbox.style.width = phys.hitboxWidth + 'px'; gHitbox.style.height = phys.hitboxHeight + 'px'; gHitbox.style.left = absoluteHitboxLeft + 'px'; gHitbox.style.top = absoluteTopY + 'px'; 
        gFootY.style.display = 'block'; gFootY.style.top = absoluteFootY + 'px'; lblFoot.style.display = 'block'; lblFoot.style.top = (absoluteFootY + 4) + 'px';
        gTopY.style.display = 'block'; gTopY.style.top = absoluteTopY + 'px'; lblTop.style.display = 'block'; lblTop.style.top = (absoluteTopY - 16) + 'px';
        const absoluteWallL = ORIGIN_X + (phys.offsetX || 0) - (phys.hitboxWidth / 2); const absoluteWallR = ORIGIN_X + (phys.offsetX || 0) + (phys.hitboxWidth / 2); gWallL.style.display = 'block'; gWallL.style.left = absoluteWallL + 'px'; gWallR.style.display = 'block'; gWallR.style.left = absoluteWallR + 'px'; lblWall.style.display = 'block'; lblWall.style.left = (absoluteWallR + 6) + 'px';

        charRoot.style.transform = (activeForm === 'left') ? 'scaleX(-1)' : 'scaleX(1)';

        let allAtlases = {};
        try { allAtlases = JSON.parse(txtAtlases.value); } catch(e) { allAtlases = {}; }

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

            // 🌟【修正】フレーム名から所属するアトラス画像を自動で逆引き検索！
            let partImageFile = part.image || "{{ $activeFile }}";
            let textureMeta = null;
            let srcMeta = null;

            // 1. まずはパーツが本来所属している画像内を探す
            if (allAtlases[partImageFile] && allAtlases[partImageFile].textures?.[0]) {
                const tMeta = allAtlases[partImageFile].textures[0];
                const sMeta = tMeta.frames.find(f => f.name === frameName);
                if (sMeta) { textureMeta = tMeta; srcMeta = sMeta; }
            }

            // 2. 見つからない場合は、全アトラスを総検索して該当フレームを自動探索する（つまみ食い対応）
            if (!srcMeta) {
                for (const imgKey in allAtlases) {
                    if (allAtlases[imgKey].textures?.[0]) {
                        const tMeta = allAtlases[imgKey].textures[0];
                        const sMeta = tMeta.frames.find(f => f.name === frameName);
                        if (sMeta) {
                            partImageFile = imgKey;
                            textureMeta = tMeta;
                            srcMeta = sMeta;
                            break;
                        }
                    }
                }
            }

            // それでも見つからない、または非表示(transparent)なら描画をスキップ
            if (!srcMeta) return;

            const w = srcMeta.frame.w * scale; const h = srcMeta.frame.h * scale;
            const ox = part.originX !== undefined ? part.originX : 0.5; const oy = part.originY !== undefined ? part.originY : 0.5;

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

            const imgNaturalW = textureMeta.size?.w || 512;
            const imgNaturalH = textureMeta.size?.h || 512;

            const pEl = document.createElement('div');
            pEl.className = 'position-absolute motion-spawned-part'; pEl.dataset.name = part.name; 
            pEl.style.width = w + 'px'; pEl.style.height = h + 'px'; 
            // 💡 パーツ画像のカテゴリを全アトラスマップから逆引きしてURLに挟む
            const partCategory = allAtlases[partImageFile]?.category || 'character';
            pEl.style.backgroundImage = `url(${SPRITE_SHEET_BASE_URL}${partCategory}/${partImageFile})`;
            pEl.style.backgroundPosition = `-${srcMeta.frame.x * scale}px -${srcMeta.frame.y * scale}px`;
            pEl.style.backgroundSize = `${imgNaturalW * scale}px ${imgNaturalH * scale}px`; 
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
                    // コンテナの基準点を使わず、純粋なマウスの画面座標を起点にする
                    startX = e.clientX;
                    startY = e.clientY;
                    
                    // 🌟 1. 掴んだ瞬間の「見た目の現在位置(CSS)」をそのままピクセル単位で正確に記憶
                    partInitialX = parseFloat(pEl.style.left) || 0;
                    partInitialY = parseFloat(pEl.style.top) || 0;
                    
                    // 🌟 2. 並行して、内部データ上のドラッグ開始数値を専用の広域変数に退避
                    if (activeAnimName === 'setup') { 
                        window.dataInitialX = part.x; 
                        window.dataInitialY = part.y; 
                    } else { 
                        initAnimFrameNodePath(); 
                        const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts; 
                        window.dataInitialX = partsNode[part.name]?.x !== undefined ? partsNode[part.name].x : 0; 
                        window.dataInitialY = partsNode[part.name]?.y !== undefined ? partsNode[part.name].y : 0; 
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

    /**
     * 🌟 外部（マテリアルパレット等）からパーツがクリックされた際の連動処理
     */
    window.handlePalettePartClick = function(frame, name) {
        // パレットからパーツが追加・更新された後、プレビュー上の該当要素を探して選択状態にする
        setTimeout(() => {
            // 名前の入力ダイアログが出る場合があるため、少し待ってから要素を探す
            // 実際には addLayerFromPalette 側で追加処理が行われるが、アセット管理では
            // buildPartPalette 内のロジックが動いている可能性がある。
            
            // 全パーツを再レンダリング
            renderMotionFrames();
            
            // 該当するパーツ名を探す（frame.name と一致するもの、または最後に追加されたもの）
            const parts = currentMotionObj?.setup?.parts || [];
            if (parts.length > 0) {
                // 最後に追加されたパーツを選択
                const lastPart = parts[parts.length - 1];
                const targetEl = charRoot.querySelector(`.motion-spawned-part[data-name="${lastPart.name}"]`);
                if (targetEl) {
                    selectSetupPart(targetEl, lastPart);
                }
            }
        }, 500); // プロンプト入力を考慮して少し長めに待機
    };

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
            // マウスの純粋な画面移動距離をズーム率で割って正確な移動量を出す
            let mouseDeltaX = (e.clientX - startX) / zoomLevel;
            let mouseDeltaY = (e.clientY - startY) / zoomLevel;
            
            // 左向き（scaleX(-1)）の時は横方向の移動軸が反転するため符号を逆にする
            if (activeForm === 'left') mouseDeltaX = -mouseDeltaX;

            // 🌟 1. 見た目の初期位置にマウス移動量をダイレクトに加算（100%ワープもカクつきも起きない！）
            activeMotionElement.style.left = (partInitialX + mouseDeltaX) + 'px';
            activeMotionElement.style.top = (partInitialY + mouseDeltaY) + 'px';

            // 🌟 2. 描画を邪魔しないように裏側でスケールを計算し、インプット数値をリアルタイム同期
            if (!currentMotionObj.physics) currentMotionObj.physics = { default: {} };
            if (!currentMotionObj.physics.default) currentMotionObj.physics.default = { globalPartScale: 0.8 };
            if (!currentMotionObj.physics[activeForm]) currentMotionObj.physics[activeForm] = {};
            
            // 現在のレンダリングエンジンと完全に一致する正しい物理スケールを取得
            const scale = currentMotionObj.physics[activeForm].globalPartScale ?? currentMotionObj.physics.default.globalPartScale ?? 0.8;

            // 移動ピクセルをデータ空間のスケール幅に変換して足し算
            let dataDeltaX = mouseDeltaX / scale;
            let dataDeltaY = mouseDeltaY / scale;
            
            inpX.value = Math.round(window.dataInitialX + dataDeltaX);
            inpY.value = Math.round(window.dataInitialY + dataDeltaY);
        }
    });
    window.addEventListener('mouseup', function () {
        if (mode === 'part-dragging' && targetPartData) {
            // インプット欄に入っている、ドラッグ終了時の綺麗な整数データを取得
            const xVal = parseInt(inpX.value) || 0; 
            const yVal = parseInt(inpY.value) || 0;
            
            // 手を離した瞬間にデータを確定保存する
            if (activeAnimName === 'setup') { 
                targetPartData.x = xVal; 
                targetPartData.y = yVal; 
            } else { 
                initAnimFrameNodePath(); 
                const partsNode = currentMotionObj.animations[activeAnimName].frames[currentFrameIndex].parts; 
                if (!partsNode[targetPartData.name]) partsNode[targetPartData.name] = {}; 
                partsNode[targetPartData.name].x = xVal; 
                partsNode[targetPartData.name].y = yVal; 
            }
            
            if (activeMotionElement) activeMotionElement.style.cursor = 'grab';
            
            // 綺麗な整数値で全体を1回だけ美しく再レンダリングして同期
            updateMotionTextarea(); 
            renderMotionFrames(); 
            reselectCurrentPart();
        }
        mode = 'idle'; // ドラッグ状態を正常に解除して確実に画像を離せるようにする
    });

    [inpHbW, inpHbH, inpFootY, inpOffsetX, inpPhysScale].forEach(input => {
        input.addEventListener('input', function() {
            if (!currentMotionObj) return;
            if (!currentMotionObj.physics) currentMotionObj.physics = { default: {} };
            if (!currentMotionObj.physics[activeForm]) currentMotionObj.physics[activeForm] = {};
            
            const valStr = this.value.trim();
            let key = '';
            if (this === inpHbW) key = 'hitboxWidth';
            if (this === inpHbH) key = 'hitboxHeight';
            if (this === inpFootY) key = 'footY';
            if (this === inpOffsetX) key = 'offsetX';
            if (this === inpPhysScale) key = 'globalPartScale';

            if (valStr === '') {
                delete currentMotionObj.physics[activeForm][key];
            } else {
                currentMotionObj.physics[activeForm][key] = (key === 'globalPartScale') ? parseFloat(valStr) : parseInt(valStr);
            }

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

    // 🌟 何方向のボタンが生成されても1つのロジックで綺麗に切り替える汎用イベント
    document.querySelectorAll('.btn-dir-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-dir-toggle').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeForm = this.dataset.form;
            renderMotionFrames();
            reselectCurrentPart();
        });
    });

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
                
                // 1. 選択された現在の向き（例：右向き）の設定だけを確実に非表示（transparent）にする
                currentMotionObj.forms[activeForm][targetPartData.name] = 'transparent';
                
                const isStillVisibleAnywhere = allForms.some(form => {
                    const fName = currentMotionObj.forms?.[form]?.[targetPartData.name];
                    if (fName === 'transparent') {
                        return false; // 明示的に非表示指定されている向きはスキップ
                    }
                    if (fName && fName !== 'transparent') {
                        return true; // 他の向きで、固有の別画像パーツが割り当てられていれば維持
                    }
                    // 設定が空(undefined)の向きはデフォルト画像で描画されるため、ベース画像が存在するなら維持
                    return targetPartData.frame && targetPartData.frame !== 'transparent';
                });

                // 3. 全ての向きで完全に非表示（どこからも見えない状態）になった場合のみ、大元のパーツ配列から完全に抹殺する
                if (!isStillVisibleAnywhere) {
                    currentMotionObj.setup.parts = currentMotionObj.setup.parts.filter(p => p.name !== targetPartData.name);
                    allForms.forEach(form => {
                        if (currentMotionObj.forms?.[form]) {
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

    // 🌟 親ビューのボタンから呼び出せるように各セッター・関数をwindowへマウント
    window.updateMotionTextarea = updateMotionTextarea;
    window.renderMotionFrames = renderMotionFrames;
    window.resetFields = resetFields;
    window.selectSetupPart = selectSetupPart;
    
    window.handlePalettePartClick = function(f, selectedImgFile) {
        initMotionJsonStructure();
        const defaultPartName = f.name;
        const partKeyName = prompt(`この画像を表示する「部位名(name)」を入力してください:\n(既存の部位名を入れるとそのコマの画像が差し替わります)`, defaultPartName);
        if (!partKeyName) return;
        const cleanName = partKeyName.trim();

        if (window.activeAnimName === 'setup') {
            if (!window.currentMotionObj.forms) window.currentMotionObj.forms = { right:{}, left:{}, front:{} };
            const existingPart = window.currentMotionObj.setup.parts.find(p => p.name === cleanName);

            if (existingPart) {
                existingPart.image = selectedImgFile; 
                if (!window.currentMotionObj.forms[window.activeForm]) window.currentMotionObj.forms[window.activeForm] = {};
                window.currentMotionObj.forms[window.activeForm][cleanName] = f.name;
            } else {
                const newPart = { name: cleanName, image: selectedImgFile, frame: f.name, x: 0, y: 0, depth: window.currentMotionObj.setup.parts.length + 1, originX: 0.5, originY: 0.5 };
                window.currentMotionObj.setup.parts.push(newPart);

                const allForms = "{{ $game->view_mode ?? 'side_view' }}" === 'side_view' ? ['right', 'left'] : ("{{ $game->view_mode ?? 'side_view' }}" === 'top_down' ? ['front', 'back', 'side'] : ['default']);
                allForms.forEach(form => {
                    if (!window.currentMotionObj.forms[form]) window.currentMotionObj.forms[form] = {};
                    if (form !== window.activeForm) {
                        window.currentMotionObj.forms[form][cleanName] = 'transparent';
                    } else {
                        window.currentMotionObj.forms[form][cleanName] = f.name;
                    }
                });
            }
        } else {
            initAnimFrameNodePath();
            const partsNode = window.currentMotionObj.animations[window.activeAnimName].frames[currentFrameIndex].parts;
            const setupPart = window.currentMotionObj.setup.parts.find(p => p.name === cleanName);
            const defaultDepth = setupPart ? (setupPart.depth ?? 1) : 1;

            if (!partsNode[cleanName]) {
                partsNode[cleanName] = { frame: f.name, x: 0, y: 0, angle: 0, depth: defaultDepth };
            } else {
                partsNode[cleanName].frame = f.name;
                if (partsNode[cleanName].depth === undefined) partsNode[cleanName].depth = defaultDepth;
            }
        }

        updateMotionTextarea();
        renderMotionFrames();
    };

    // 🌟 ロード初期化処理（window経由で親ビュー側のパレット群も安全にトリガー）
    initMotionJsonStructure(); 
    if (window.buildPartPalette) window.buildPartPalette(); 
    syncAnimationSelectOptions(); 
    renderMotionFrames();
    
    txtMotion.addEventListener('input', () => { initMotionJsonStructure(); syncAnimationSelectOptions(); renderMotionFrames(); });
});
</script>

<style>
.motion-spawned-part { box-sizing: content-box !important; }
.motion-spawned-part div { pointer-events: none; }
#motion-used-list div { transition: background-color 0.15s ease, border-color 0.15s ease; }
#motion-used-list div:hover { background-color: rgba(0, 0, 0, 0.04) !important; }
.motion-spawned-part.border-success {
    border-width: 3px !important;
    border-style: solid !important;
    box-shadow: 0 0 15px rgba(40, 167, 69, 0.7) !important;
    z-index: 9999 !important;
}
</style>
