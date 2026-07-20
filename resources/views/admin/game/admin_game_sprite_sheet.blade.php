{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif

@php
    $segments = request()->segments();
    $tab2 = $segments[2] ?? null;
    $activeFile = $activeFile ?? '';
    $parts_mode = $parts_mode ?? null;
@endphp

{{-- 📁 上段：スプライトシート画像の新規アップロード登録フォーム（クリックで開閉トグル化） --}}
@if(!$parts_mode)
<form action="{{ route('admin.game.sprite_sheet.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="card mb-3 shadow-sm border-primary animate__animated animate__fadeIn">
        <div class="card-header bg-primary text-white py-2 small fw-bold d-flex justify-content-between align-items-center" 
             data-bs-toggle="collapse" 
             data-bs-target="#uploadFormCollapse" 
             role="button" 
             style="user-select: none; cursor: pointer;">
            <span><i class="bi bi-cloud-arrow-up-fill me-1"></i> 📤 共通スプライトシート画像を新規アップロード</span>
            <span class="small font-monospace text-white-50" style="font-size:11px;">クリックして展開 <i class="bi bi-chevron-expand ms-1"></i></span>
        </div>
        
        <div id="uploadFormCollapse" class="collapse">
            <div class="card-body bg-light py-2 px-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-bold text-danger mb-1" style="font-size:11px;">📁 収納先カテゴリフォルダ（必須）</label>
                        <select name="category" class="form-select form-select-sm border-danger fw-bold font-monospace text-danger" required>
                            <option value="">-- 分類を選択してください --</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-bold mb-1" style="font-size:11px;">🖼️ 対象のPNG画像ファイル（背景透過PNG推奨）</label>
                        <input type="file" name="sprite_file" class="form-control form-control-sm border-secondary" accept=".png" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold shadow-sm" style="height: 31px;">
                            <i class="bi bi-upload me-1"></i> アップロード実行
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endif

{{-- メッセージ表示通知 --}}
@if(session('msg'))
    <div class="alert alert-info shadow-sm py-2 small animate__animated animate__flash mb-3">
        <i class="bi bi-info-circle-fill me-1"></i> {!! session('msg') !!}
    </div>
@endif

<div class="row m-0 p-0">
    {{-- 📊 下段左側：登録済み画像ファイルの一覧目録 ＆ 定義パーツ目録 (3/12列) --}}
    <div class="col-md-3 ps-0 pe-2">
        @include('admin.game.parts.sprite_sheet_list')

        {{-- 定義済みパーツ一覧セクション (確認用) --}}
        @if($activeFile && $activeSpriteSheet && !empty($activeSpriteSheet->atlas_data))
            @php
                $frames = $activeSpriteSheet->pixel_data['textures'][0]['frames'] ?? [];
            @endphp
            
            <div class="card shadow-sm border-info animate__animated animate__fadeIn">
                <div class="card-header bg-info text-white py-2 small fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-tags-fill me-1"></i> 定義済みパーツの確認</span>
                    <span class="badge bg-white text-info fw-bold font-monospace" style="font-size:10px;">{{ count($frames) }} 件</span>
                </div>
                <div class="card-body bg-light p-1" style="max-height: 380px; overflow-y: auto;">
                    @if(count($frames) > 0)
                        <div class="d-flex flex-column gap-1">
                            @foreach($frames as $f)
                                <div class="d-flex align-items-center border border-secondary p-1 px-2 rounded bg-white text-dark shadow-sm" 
                                     style="width: 100%; text-align: left;">
                                    
                                    <div style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background-color: #f0f0f0; background-image: linear-gradient(45deg, #e0e0e0 25%, transparent 25%, transparent 75%, #e0e0e0 75%), linear-gradient(45deg, #e0e0e0 25%, #f0f0f0 25%, #f0f0f0 75%, #e0e0e0 75%); background-size: 6px 6px; border: 1px solid #dee2e6; border-radius: 4px; overflow: hidden;" class="me-2">
                                        <canvas class="part-thumb-canvas" 
                                                data-x="{{ $f['frame']['x'] }}" 
                                                data-y="{{ $f['frame']['y'] }}" 
                                                data-w="{{ $f['frame']['w'] }}" 
                                                data-h="{{ $f['frame']['h'] }}" 
                                                style="max-width: 26px; max-height: 26px; object-fit: contain;">
                                        </canvas>
                                    </div>
                                    
                                    <div class="flex-grow-1 min-w-0 font-monospace" style="font-size: 11px; line-height: 1.2;">
                                        @php
                                            $pName = $f['name'] ?? $f['filename'] ?? 'undefined';
                                        @endphp
                                        <div class="text-info fw-bold text-truncate" style="font-size:11px;" title="{{ $pName }}">
                                            📌 {{ $pName }}
                                        </div>
                                        <div class="text-muted" style="font-size: 9px; scale: 0.95; transform-origin: left center;">
                                            X:<code>{{ $f['frame']['x'] }}</code> Y:<code>{{ $f['frame']['y'] }}</code> W:<code>{{ $f['frame']['w'] }}</code> H:<code>{{ $f['frame']['h'] }}</code>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3 text-muted small border border-dashed rounded bg-white" style="font-size:11px;">
                            まだパーツが切り出されていません。
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>


    {{-- 🎯 下段右側：各エディタ本体 (9/12列) --}}
    <div class="col-md-9 animate__animated animate__fadeIn ps-2 pe-0">
        @if($activeFile)
            
            {{-- リネーム・削除 --}}
            <div class="card-footer p-2 bg-white border-top border-secondary">
                <div class="d-flex align-items-center justify-content-between gap-2 border rounded p-2">
                    
                    <form action="{{ route('admin.game.sprite_sheet.rename') }}" method="POST" class="d-flex align-items-center gap-1 flex-grow-1" onsubmit="return confirm('この画像のファイル名を変更しますか？（この画像から定義されたパーツも影響を受けます）')">
                        @csrf
                        <input type="hidden" name="filename" value="{{ $activeFile }}">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text py-0" style="font-size:10px;">新名称</span>
                            <input type="text" name="new_filename" value="{{ $activeFile }}" class="form-control form-control-sm font-monospace fw-bold text-primary py-0" style="height:26px; font-size:11px;" required>
                            <button type="submit" class="btn btn-sm btn-warning py-0 fw-bold" style="font-size: 10px;">
                                <i class="bi bi-pencil-square"></i> リネーム
                            </button>
                        </div>
                    </form>

                    <div class="vr mx-1"></div>

                    <form action="{{ route('admin.game.sprite_sheet.destroy') }}" method="POST" onsubmit="return confirm('この画像を完全に削除しますか？（この画像から定義されたパーツも使用できなくなります）')">
                        @csrf
                        <input type="hidden" name="filename" value="{{ $activeFile }}">
                        <button type="submit" class="btn btn-sm btn-danger py-0 fw-bold" style="font-size: 10px;">
                            <i class="bi bi-trash-fill"></i> 画像を削除
                        </button>
                    </form>

                </div>
            </div>

            {{-- スプライトシート管理では画像のみ表示 --}}
            <div class="card shadow-sm border-secondary">
                <div class="card-header bg-dark text-white py-2 small fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-image me-1"></i> プレビュー: <code>{{ $activeFile }}</code></span>
                </div>
                <div class="card-body bg-secondary p-2 text-center" style="max-height: 600px; overflow: auto;">
                    <img id="sprite-target-img" src="{{ asset('storage/sprite_sheet/' . $activeFileCategory . '/' . $activeFile) }}" class="img-fluid shadow">
                </div>
            </div>
        @else
            <div class="card py-5 text-center text-muted border-dashed bg-white shadow-sm">
                <div class="fs-1 text-primary"><i class="bi bi-arrow-left-circle-fill"></i></div>
                <h5 class="mt-2 fw-bold text-dark">
                    @if(!$parts_mode) 画像管理ルーム @else パーツ定義ルーム @endif
                </h5>
                <p class="text-muted small mb-0">左側の画像目録から画像行をクリックして「選択」してください。</p>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function drawAtlasThumbnails() {
        const mainImg = document.getElementById('sprite-target-img');
        if (!mainImg) return;

        const executeDraw = () => {
            document.querySelectorAll('.part-thumb-canvas').forEach(canvas => {
                const x = parseInt(canvas.dataset.x) || 0;
                const y = parseInt(canvas.dataset.y) || 0;
                const w = parseInt(canvas.dataset.w) || 0;
                const h = parseInt(canvas.dataset.h) || 0;

                if (w === 0 || h === 0) return;
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, w, h);
                ctx.drawImage(mainImg, x, y, w, h, 0, 0, w, h);
            });
        };

        if (mainImg.complete) { executeDraw(); } else { mainImg.addEventListener('load', executeDraw); }
    }
    drawAtlasThumbnails();
    window.refreshAtlasThumbnails = drawAtlasThumbnails;
});
</script>
