
<div class="fixed-bottom">
    <div class="fixed-bottom-menu container-fluid">
        <nav class="nav nav-pills nav-justified">
            <a href="{{ route('home') }}" class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center" href="#">
                <img src="{{ asset('img/icon/home.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">トップ</span>
            </a>
            {{-- ランキング機能は現在ないため無効化
            <div class="border-right"></div>
            <a class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center" href="#">
                <img src="{{ asset('img/icon/ranking.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">ランキング</span>
            </a>
            <div class="border-right"></div>
            <a href="{{ route('search-show') }}" class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center" href="#">
                <img src="{{ asset('img/icon/search.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">検索</span>
            </a>
            <div class="border-right"></div>
            <a href="{{ route('favorite-show') }}" class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center" href="#">
                <img src="{{ asset('img/icon/fav1.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">お気に入り</span>
            </a>
            --}}
            <div class="border-right"></div>
            <a href="{{ route('friend.show') }}" class="flex-sm-fill text-sm-center nav-link p-2 d-flex flex-column align-items-center" href="#">
                <img src="{{ asset('img/icon/friend.png') }}" alt="アイコン" class="icon-top">
                <span style="font-size: 0.75rem;">フレンド</span>
            </a>
        </nav>
        <div class="border-bottom"></div>
    </div>
</div>