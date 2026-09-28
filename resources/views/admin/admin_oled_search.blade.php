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
                            <th>タイトル</th>
                            <th>イベントキー</th>
                            <th>コマ速度 (ms)</th>
                            <th>フレーム数</th>
                            <th>ポイント</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($oled_faces ?? [] as $face)
                        <tr>
                            <td>{{ $face->id }}</td>
                            <td>{{ $face->title }}</td>
                            <td><code>{{ $face->event_type }}</code></td>
                            <td>{{ $face->interval_ms }} ms</td>
                            <td>{{ count($face->compiled_bitmaps ?? []) }} コマ</td>
                            <td>{{ $face->point_cost }} pt</td>
                            <td>
                                <a href="{{ route('admin.oled.edit', $face->id) }}" class="btn btn-sm btn-outline-primary">編集</a>
                                <form action="{{ route('admin.oled.destroy', $face->id) }}" method="POST" class="d-inline" onsubmit="return confirm('本当に削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">登録されたOLEDフェイスはありません。</td>
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
