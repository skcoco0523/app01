@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 800px;">
    <div class="title-text mb-3">
        <h3 class="fs-5 fw-bold">スマートリモコン・IoT活用ガイド</h3>
        <p class="text-secondary small mb-0">{{ config('app.name', 'Application') }}の機能活用法や、スマートリモコンの接続・設定トラブル解決手順を解説します。</p>
    </div>

    <div class="row g-3">
        {{-- 記事1 --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" onclick="window.location.href='{{ route('guide.show', ['id' => 1]) }}'" style="cursor: pointer;">
                <div class="card-body p-3">
                    <span class="badge bg-primary mb-2">初期設定</span>
                    <h5 class="card-title fs-6 fw-bold text-dark">
                        スマートリモコンの初期設定手順とWi-Fi接続・PIN登録のコツ
                    </h5>
                    <p class="card-text small text-secondary mb-0">
                        スマートリモコンを自宅のWi-Fiに接続し、キャプティブポータルからPINコードを発行してアプリに本登録する手順を解説します。
                    </p>
                </div>
            </div>
        </div>

        {{-- 記事2 --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" onclick="window.location.href='{{ route('guide.show', ['id' => 2]) }}'" style="cursor: pointer;">
                <div class="card-body p-3">
                    <span class="badge bg-info text-dark mb-2">アプリ活用</span>
                    <h5 class="card-title fs-6 fw-bold text-dark">
                        {{ config('app.name', 'Application') }}をPWAアプリとしてスマホに追加・利用する方法
                    </h5>
                    <p class="card-text small text-secondary mb-0">
                        iOS（iPhone）やAndroidでWebアプリをホーム画面に追加し、ネイティブアプリ感覚で高速起動・利用する手順を解説します。
                    </p>
                </div>
            </div>
        </div>

        {{-- 記事3 --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" onclick="window.location.href='{{ route('guide.show', ['id' => 3]) }}'" style="cursor: pointer;">
                <div class="card-body p-3">
                    <span class="badge bg-success mb-2">機能ガイド</span>
                    <h5 class="card-title fs-6 fw-bold text-dark">
                        赤外線リモコン信号の学習とボタン登録のベストプラクティス
                    </h5>
                    <p class="card-text small text-secondary mb-0">
                        エアコンやテレビの赤外線信号を学習させる際の照射位置や、38kHzキャリア周波数の認識率を高める工夫を紹介します。
                    </p>
                </div>
            </div>
        </div>

        {{-- 記事4 --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" onclick="window.location.href='{{ route('guide.show', ['id' => 4]) }}'" style="cursor: pointer;">
                <div class="card-body p-3">
                    <span class="badge bg-secondary mb-2">便利機能</span>
                    <h5 class="card-title fs-6 fw-bold text-dark">
                        フレンド機能を使ったメモやスマートリモコンの共有・権限設定
                    </h5>
                    <p class="card-text small text-secondary mb-0">
                        ユーザーID（フレンドコード）で家族や友人と繋がり、メモやスマートリモコンを安全に共有・共同編集する手順です。
                    </p>
                </div>
            </div>
        </div>

        {{-- 記事5 --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100" onclick="window.location.href='{{ route('guide.show', ['id' => 5]) }}'" style="cursor: pointer;">
                <div class="card-body p-3">
                    <span class="badge bg-warning text-dark mb-2">トラブルシューティング</span>
                    <h5 class="card-title fs-6 fw-bold text-dark">
                        家電が反応しない・オフライン表示になる場合の対処法
                    </h5>
                    <p class="card-text small text-secondary mb-0">
                        通信エラーが発生した際のチェックリスト（2.4GHz帯の確認、IP固定化、デバイス再起動手順など）をまとめています。
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection