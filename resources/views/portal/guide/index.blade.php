@extends('layouts.app')

@section('content')

<div class="title-text mb-3">
    <h3 class="fs-5 fw-bold">スマートリモコン・IoT活用ガイド</h3>
    <p class="text-secondary small mb-0">{{ config('app.name', 'Application') }}の機能活用法や、トラブル解決手順を解説します。</p>
</div>

{{-- ★ 記事リストの配列定義（バッジの背景色やスタイルもここで一元管理） --}}
@php
$guides = [
    [
        'slug'  => 'life-theater',
        'badge' => [
            'text'  => 'ライフシアター',
            'class' => 'text-white',
            'style' => 'background-color: #6f42c1;', // 紫
        ],
        'title' => 'ライフシアターで思い出の写真をスライドショー・限定共有する方法',
        'desc'  => '写真や動画をまとめて映画のように自動再生するシアター上映や、家族・友人とのプライベート共有手順を解説します。'
    ],
    [
        'slug'  => 'smart-remote',
        'badge' => [
            'text'  => 'サービス概要',
            'class' => 'bg-primary text-white',      // 青（メインカラー）
            'style' => '',
        ],
        'title' => config('app.name', 'SK_HOME') . 'でできること（3大特徴と活用シーン）',
        'desc'  => 'AI音声操作、遠隔操作、リモコン一元管理など、本アプリの主要な機能と便利な活用シーンを紹介します。'
    ],
    [
        'slug'  => 'setup',
        'badge' => [
            'text'  => '初期設定',
            'class' => 'text-white',
            'style' => 'background-color: #0d9488;', // ティール（青緑）
        ],
        'title' => 'スマートリモコンの初期設定手順とWi-Fi接続・PIN登録のコツ',
        'desc'  => 'スマートリモコンを自宅のWi-Fiに接続し、キャプティブポータルからPINコードを発行して本登録する手順です。'
    ],
    [
        'slug'  => 'pwa',
        'badge' => [
            'text'  => 'アプリ活用',
            'class' => 'bg-info text-dark',          // 水色
            'style' => '',
        ],
        'title' => config('app.name', 'Application') . 'をPWAアプリとしてスマホに追加・利用する方法',
        'desc'  => 'iOS（iPhone）やAndroidでWebアプリをホーム画面に追加し、高速起動・利用する手順を解説します。'
    ],
    [
        'slug'  => 'ir-learning',
        'badge' => [
            'text'  => '機能ガイド',
            'class' => 'bg-success text-white',      // 緑
            'style' => '',
        ],
        'title' => '赤外線リモコン信号の学習とボタン登録のベストプラクティス',
        'desc'  => 'エアコンやテレビの赤外線信号を学習させる際の照射位置や、認識率を高める工夫を紹介します。'
    ],
    [
        'slug'  => 'friend-share',
        'badge' => [
            'text'  => '便利機能',
            'class' => 'bg-secondary text-white',    // グレー
            'style' => '',
        ],
        'title' => 'フレンド機能を使ったメモやスマートリモコンの共有・権限設定',
        'desc'  => 'ユーザーIDで家族や友人と繋がり、メモやスマートリモコンを安全に共有・共同編集する手順です。'
    ],
    [
        'slug'  => 'trouble',
        'badge' => [
            'text'  => 'トラブル解決',
            'class' => 'bg-warning text-dark',       // 黄色
            'style' => '',
        ],
        'title' => '家電が反応しない・オフライン表示になる場合の対処法',
        'desc'  => '通信エラーが発生した際のチェックリスト（IP固定化、デバイス再起動手順など）をまとめています。'
    ],
];
@endphp

<div class="row g-3">
    {{-- 上記の配列を元にカードを自動生成 --}}
    @foreach($guides as $guide)
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100" onclick="window.location.href='{{ route('guide.show', ['slug' => $guide['slug']]) }}'" style="cursor: pointer;">
            <div class="card-body p-3">
                {{-- ★ style属性も確実に出力するように修正 --}}
                <span class="badge {{ $guide['badge']['class'] ?? '' }} mb-2" style="{{ $guide['badge']['style'] ?? '' }}">
                    {{ $guide['badge']['text'] }}
                </span>
                <h5 class="card-title fs-6 fw-bold text-dark">
                    {{ $guide['title'] }}
                </h5>
                <p class="card-text small text-secondary mb-0">
                    {{ $guide['desc'] }}
                </p>
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection