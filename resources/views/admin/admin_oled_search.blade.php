<div class="container-fluid py-2">
    <h3>OLED顔アニメーション管理 (SSD1306)</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(isset($msg))
        <div class="alert alert-info">{{ $msg }}</div>
    @endif

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>プレビュー</th> {{-- 🌟 追加 --}}
                            <th>タイトル</th>
                            <th>イベントキー</th>
                            <th>コマ速度</th>
                            <th>フレーム数</th>
                            <th>ポイント</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($oled_faces ?? [] as $face)
                        <tr>
                            <td>{{ $face->id }}</td>
                            
                            {{-- 🌟 プレビュー表示セル (128x64を1/2縮小の64x32サイズで省スペース表示) --}}
                            <td>
                                <div class="oled-list-preview border bg-black position-relative"
                                    style="width: 64px; height: 32px; overflow: hidden; cursor: pointer;"
                                    data-face-json="{{ json_encode($face->editor_json) }}"
                                    data-interval="{{ $face->interval_ms }}">
                                </div>
                            </td>

                            <td>{{ $face->title }}</td>
                            <td><code>{{ $face->event_type }}</code></td>
                            <td>{{ $face->interval_ms }} ms</td>
                            <td>{{ count($face->editor_json['frames'] ?? []) }} コマ</td>
                            <td>{{ $face->point_cost }} pt</td>
                            <td>
                                <a href="{{ route('admin.oled.edit', $face->id) }}" class="btn btn-sm btn-outline-primary">編集</a>
                                {{-- 削除フォーム... --}}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">登録されたOLEDフェイスはありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(isset($oled_faces) && method_exists($oled_faces, 'links'))
                <div class="d-flex justify-content-center mt-3">
                    {{ $oled_faces->links() }}
                </div>
            @endif
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = @json(asset(''));

    // 各プレビュー要素の初期化
    document.querySelectorAll('.oled-list-preview').forEach(function (container) {
        let timer = null;
        let frameIndex = 0;
        let frames = [];

        try {
            const rawJson = container.getAttribute('data-face-json');
            const data = typeof rawJson === 'string' ? JSON.parse(rawJson) : rawJson;
            frames = (data && Array.isArray(data.frames)) ? data.frames : [];
        } catch (e) {
            return;
        }

        if (frames.length === 0) return;

        const interval = Math.max(20, Number(container.getAttribute('data-interval')) || 150);

        // 指定フレームの描画（128x64で組み立てて scale(0.5) で64x32枠に収める）
        function drawFrame(idx) {
            container.innerHTML = '';
            const frame = frames[idx];
            if (!frame || !Array.isArray(frame.layers)) return;

            // 128x64 の実寸ラッパーを作成し、全体を 0.5倍に縮小
            const wrapper = document.createElement('div');
            wrapper.style.cssText = `
                width: 128px;
                height: 64px;
                position: relative;
                transform: scale(0.5);
                transform-origin: top left;
            `;

            frame.layers.forEach(function (layer) {
                const layerEl = document.createElement('div');
                const posX = Number(layer.x) || 0;
                const posY = Number(layer.y) || 0;
                const w = Number(layer.w) || Number(layer.src_width) || 16;
                const h = Number(layer.h) || Number(layer.src_height) || 16;

                layerEl.style.cssText = `
                    position: absolute;
                    left: ${posX}px;
                    top: ${posY}px;
                    width: ${w}px;
                    height: ${h}px;
                    z-index: ${layer.zIndex || 1};
                `;

                const path = layer.sheet_file_path || layer.file_path || '';
                if (path) {
                    const img = document.createElement('div');
                    const srcX = Number(layer.src_x) || 0;
                    const srcY = Number(layer.src_y) || 0;

                    img.style.cssText = `
                        width: ${w}px;
                        height: ${h}px;
                        background-image: url('${baseUrl + path.replace(/^\/+/, '')}');
                        background-position: -${srcX}px -${srcY}px;
                        background-repeat: no-repeat;
                        image-rendering: pixelated;
                    `;
                    layerEl.appendChild(img);
                }

                wrapper.appendChild(layerEl);
            });

            container.appendChild(wrapper);
        }

        // 初期表示（コマ #1 の静止画）
        drawFrame(0);

        // 🌟 マウスホバー時だけアニメーション再生
        container.addEventListener('mouseenter', function () {
            if (frames.length <= 1) return;
            frameIndex = 0;
            timer = setInterval(function () {
                frameIndex = (frameIndex + 1) % frames.length;
                drawFrame(frameIndex);
            }, interval);
        });

        // 🌟 マウスが外れたら停止して1コマ目に戻す
        container.addEventListener('mouseleave', function () {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            drawFrame(0);
        });
    });
});

</script>