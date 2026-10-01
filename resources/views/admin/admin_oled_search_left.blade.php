{{-- OLEDフェイス検索フォーム --}}
<form method="GET" action="{{ route('admin.oled.index') }}">
    <div class="row g-3 align-items-end">
        <div class="col-4 col-md-12">
            ・タイトル
            <input type="text" name="search_title" class="form-control" value="{{ $input['search_title'] ?? '' }}">
        </div>
        <div class="col-4 col-md-12">
            ・イベントキー
            <input type="text" name="search_event_type" class="form-control" value="{{ $input['search_event_type'] ?? '' }}">
        </div>
        
        <div class="d-flex justify-content-center mt-3">
            <button type="submit" class="btn btn-success w-100">検索</button>
        </div>
        <div class="d-flex justify-content-center mt-2">
            <a href="{{ route('admin.oled.create') }}" class="btn btn-primary w-100">新規作成</a>
        </div>
    </div>
</form>
