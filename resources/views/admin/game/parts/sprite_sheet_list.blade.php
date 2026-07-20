<div class="card shadow-sm border-dark mb-2">
    <div class="card-header bg-dark text-white py-2 small fw-bold">
        <i class="bi bi-images me-1"></i> 定義の元データ
    </div>
    <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 12px;">
            <thead class="table-light position-sticky top-0" style="z-index: 10;">
                <tr>
                    <th style="width: 80px;">カテゴリ</th>
                    <th>画像ファイル名</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                    @php
                        $route = route('admin.game.sprite_sheet.index');
                        if (($parts_mode ?? null) === 'pixel') $route = route('admin.game.pixel_parts.index');
                        if (($parts_mode ?? null) === 'grid')  $route = route('admin.game.grid_parts.index');
                    @endphp
                    <tr class="{{ $activeFile === $asset->filename ? 'table-primary fw-bold' : '' }}" 
                        style="cursor: pointer;"
                        onclick="window.location.href='{{ $route }}?file={{ $asset->filename }}&search={{ request('search') }}&tag={{ request('tag') }}'">
                        <td>
                            <span class="badge bg-secondary font-monospace" style="font-size: 9px;">{{ $asset->category }}</span>
                        </td>
                        <td class="text-break">
                            <code class="text-dark font-monospace" style="font-size:11px;">{{ $asset->filename }}</code>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center py-3 text-muted small">アセット画像がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
