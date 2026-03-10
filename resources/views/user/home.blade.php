@extends('layouts.app')

@section('content')

<?//広告バナー?> 
@include('layouts.adv_banner')



<div class="py-3">
    <div class="title-text">
        <h3>アプリリスト</h3>
    </div>
    <table class="table table-borderless table-data-center" style="table-layout: fixed;">
        <tbody>
            <colgroup>
                <col style="width: 20%; min-width: 70px;">
                <col style="width: 80%">
            </colgroup>
            <tr class="table-row" onclick="window.location.href='{{ route('remote.index') }}'" style="cursor: pointer;">
                <td><img src="{{ asset('img/icon/smartremote_icon_64_64.png') }}" alt="アイコン" class="icon-55"></td>
                <td>スマートリモコン</td>
            </tr>
            <tr class="table-row" onclick="window.location.href='{{ route('note.index') }}'" style="cursor: pointer;">
                <td><img src="{{ asset('img/icon/note_icon_64_64.png') }}" alt="アイコン" class="icon-55"></td>
                <td>メモ</td>
            </tr>
            <tr class="table-row" onclick="window.location.href='{{ route('roulette.show') }}'" style="cursor: pointer;">
                <td><img src="{{ asset('img/icon/roulette_icon_64_64.png') }}" alt="アイコン" class="icon-55"></td>
                <td>ルーレット</td>
            </tr>
        </tbody>
    </table>
</div>


<?//グーグルテキスト内広告?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1093408896428535"
     crossorigin="anonymous"></script>
<ins class="adsbygoogle"
     style="display:block; text-align:center;"
     data-ad-layout="in-article"
     data-ad-format="fluid"
     data-ad-client="ca-pub-1093408896428535"
     data-ad-slot="3688910712"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>


<?//おすすめ：プレイリスト?>
@if(isset($category_list))
    <div class="title-text">
        <h3>カテゴリ別ランキング</h3>
    </div>
    <div class="category-container">
        @foreach ($category_list as $category)
        <a href="{{ route('category-ranking', ['id' => $category->id]) }}" class="no-decoration category-box">
            <div class="category-top-icon">
                    <p class="category-top-text">{{ $category->name }}</p>
            </div>
        </a>
        @endforeach
    </div>

@endif

@endsection
