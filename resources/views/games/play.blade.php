@extends('layouts.game')

@section('content')
@php
    $isLandscape = $game->orientation === 'landscape';
@endphp

<!-- スマホを縦に持っている時の警告画面 (Landscape設定時のみ有効) -->
@if($isLandscape)
<div id="orientation-warning">
    <div class="msg-box">
        <i class="fas fa-mobile-alt rotate-icon"></i>
        <p>画面を横にしてください</p>
    </div>
</div>
@endif

<div id="game-wrapper">
    <div id="game-container">
        <!-- Phaser will inject canvas here -->
    </div>
</div>

<style>
    /* 警告画面のスタイル */
    #orientation-warning {
        display: none; /* 初期は非表示 */
        position: fixed;
        top: 0; left: 0; width: 100vw; height: 100vh;
        background-color: #222;
        color: white;
        z-index: 10000;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .rotate-icon {
        font-size: 80px;
        margin-bottom: 20px;
        animation: rotate-anim 2s infinite ease-in-out;
    }
    @keyframes rotate-anim {
        0% { transform: rotate(0deg); }
        50% { transform: rotate(90deg); }
        100% { transform: rotate(90deg); }
    }

    #game-wrapper {
        width: 100vw;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #111;
        overflow: hidden;
    }
    
    #game-container {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #game-container canvas {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* 縦画面かつLandscape設定の場合に警告を出す */
    @media screen and (orientation: portrait) {
        .is-landscape-config #orientation-warning {
            display: flex;
        }
        .is-landscape-config #game-wrapper {
            display: none;
        }
    }
</style>

<script>
    // 設定値をBodyのクラスに反映してCSS制御に使用
    if ("{{ $game->orientation }}" === 'landscape') {
        document.body.classList.add('is-landscape-config');
    }

    window.GAME_CONFIG = {
        gameKey: "{{ $gameKey }}",
        viewMode: "{{ $game->view_mode }}",
        orientation: "{{ $game->orientation }}",
        user: {
            isLoggedIn: {{ Auth::check() ? 'true' : 'false' }},
            isAdmin: {{ (Auth::user() && Auth::user()->admin_flag) ? 'true' : 'false' }}
        },
        versions: {
            characters: "{{ @filemtime(public_path("storage/games/{$gameKey}/characters.json")) ?: time() }}",
            stages: "{{ @filemtime(public_path("storage/games/{$gameKey}/stages.json")) ?: time() }}",
            atlas: "{{ @filemtime(public_path("storage/games/{$gameKey}/atlas_sheets.json")) ?: time() }}"
        },
        apiUrl: "{{ url('api/games/' . $gameKey) }}",
        assetBaseUrl: "{{ asset('storage/games/' . $gameKey) }}",
        globalAssetBaseUrl: "{{ asset('storage/sprite_sheet') }}",
        editor: {
            originX: {{ config('game.editor.origin_x', 300) }},
            originY: {{ config('game.editor.origin_y', 220) }}
        }
    };
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/phaser@3.60.0/dist/phaser.min.js"></script>
@vite(['resources/js/games/game_engine/main.js'])

<script>
    // デバッグ用: Viteのビルド状況や設定値の確認
    console.log('Game play view loaded with config:', window.GAME_CONFIG);
</script>
@endsection
