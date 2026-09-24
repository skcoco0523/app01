{{-- 一番下までスクロールした時に表示されるポータルリンクエリア --}}
<div class="container text-center pt-3 pb-2 border-top" style="padding-bottom: 75px;">
    {{-- ★ グループ小見出し --}}
    <div class="text-muted fw-bold mb-2" style="font-size: 11px;">サポートメニュー</div>

    <div class="d-flex justify-content-center flex-wrap gap-2 text-secondary" style="font-size: 11px;">
        <a href="{{ route('guide.index') }}" class="text-secondary text-decoration-none">ガイド</a> |
        <a href="{{ route('contact.index') }}" class="text-secondary text-decoration-none">お問い合わせ</a> |
        <a href="{{ route('privacy') }}" class="text-secondary text-decoration-none">プライバシーポリシー</a> |
        <a href="{{ route('terms') }}" class="text-secondary text-decoration-none">利用規約</a> |
        <a href="{{ route('tokushoho') }}" class="text-secondary text-decoration-none">特定商取引法</a> |
        <a href="{{ route('about') }}" class="text-secondary text-decoration-none">運営者情報</a>
    </div>
    <div class="mt-2 text-muted" style="font-size: 10px;">
        &copy; 2026 {{ config('app.name', 'SK_HOME') }} All rights reserved.
    </div>
</div>

{{-- 画面最下部に常時固定されるアイコンナビ --}}
<div class="fixed-bottom">
    <div class="fixed-bottom-menu container-fluid">
        <nav class="nav nav-pills nav-justified">
            <a href="{{ route('home') }}" class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center">
                <img src="{{ asset('img/icon/home.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">トップ</span>
            </a>
            <div class="border-right"></div>
            <a href="{{ route('friend.index') }}" class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center">
                <img src="{{ asset('img/icon/friend.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">フレンド</span>
            </a>
        </nav>
        <div class="border-bottom"></div>
    </div>
</div>