@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 600px;">

    {{-- 現在のポイント残高ミニカード --}}
    <div class="card border-0 shadow-sm mb-3 bg-light">
        <!-- (既存のコードそのまま) -->
        <div class="card-body p-3 d-flex justify-content-between align-items-center">
            <div class="text-end small text-muted" style="font-size: 11px;">
                <div>無償: {{ number_format($profile->free_point ?? 0) }} pt</div>
                <div>有償: {{ number_format($profile->pay_point ?? 0) }} pt</div>
            </div>
            <div>
                <div class="text-secondary small" style="font-size: 11px;">現在の所持ポイント</div>
                <div class="fw-bold text-dark">
                    合計 <span class="fs-5 text-primary">{{ number_format(($profile->free_point ?? 0) + ($profile->pay_point ?? 0)) }}</span> pt
                </div>
            </div>
        </div>
    </div>

    {{-- 広告視聴バナー --}}
    <div class="card border-0 shadow-sm mb-3 text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);">
        <div class="card-body p-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                    <i class="fa-solid fa-film text-primary fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size: 14px;">広告を見て10ptゲット</div>
                    <div class="text-white-50 small" style="font-size: 11px;">※無償ポイント</div>
                </div>
            </div>
            <div>
                {{-- 修正箇所: aタグからbuttonタグへ変更 --}}
                <button type="button" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm text-dark" style="font-size: 12px;" id="btn-start-ad">
                    視聴する
                </button>
            </div>
        </div>
    </div>

    {{-- プラン選択エリア --}}
    <!-- (既存のコードそのまま) -->
    <div class="mb-3">
        <h6 class="fw-bold text-dark mb-1">ポイントパックを選択</h6>
        <p class="text-muted small mb-3" style="font-size: 12px;">購入した有償ポイントに有効期限はありません。</p>

        <form method="POST" action="{{ route('point.checkout') }}">
            @csrf
            
            <div class="row g-2 mb-4">
                @foreach($packs as $pack)
                    @php
                        // 価格と付与ポイントの差分（お得ポイント数）を計算
                        $bonus = $pack->value2 - $pack->value1;
                    @endphp
                    <div class="col-12">
                        <input type="radio" 
                            name="config_name" 
                            value="{{ $pack->config_name }}" 
                            class="btn-check" 
                            id="pack_{{ $pack->config_name }}" 
                            {{ $loop->first ? 'checked' : '' }} 
                            autocomplete="off">
                        
                        <label class="card border-2 card-select-option h-100 shadow-sm p-3 w-100" 
                            for="pack_{{ $pack->config_name }}" 
                            style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                        <span class="fw-bold text-dark">{{ $pack->description }}</span>
                                        @if(!empty($pack->badge))
                                            <span class="badge bg-warning text-dark rounded-pill" style="font-size: 10px;">{{ $pack->badge }}</span>
                                        @endif
                                        @if($bonus > 0)
                                            <span class="badge bg-danger rounded-pill" style="font-size: 10px;">
                                                +{{ number_format($bonus) }}ptお得
                                            </span>
                                        @endif
                                    </div>
                                    <div class="fs-5 fw-bold text-primary">
                                        {{ number_format($pack->value2) }} <span class="fs-6 fw-normal text-dark">pt</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        約 {{ number_format($pack->value2) }} 回分の音声操作
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fs-5 fw-bold text-dark mb-1">
                                        ¥{{ number_format($pack->value1) }}
                                    </div>
                                    <span class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 selection-label" style="font-size: 12px;">
                                        選択する
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="sticky-bottom bg-white pt-2 pb-3 border-top">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm py-3">
                    購入手続きへ進む
                </button>
            </div>
        </form>
    </div>

    {{-- 注意事項 --}}
    <!-- (既存のコードそのまま) -->
    <div class="card border-0 bg-light rounded p-3 mb-4">
        <div class="fw-bold text-secondary mb-1" style="font-size: 12px;">ご購入時のご注意</div>
        <ul class="text-secondary ps-3 mb-0" style="font-size: 11px; line-height: 1.6;">
            <li>購入完了後、ポイントは即時反映されます。</li>
            <li>有償ポイントに有効期限はありません。無料ポイントから優先して消費されます。</li>
            <li>お客様都合による購入後のキャンセル・返金はできません。</li>
        </ul>
    </div>

</div>

{{-- Bootstrap JSの読み込みが落ちている場合のフォールバック（画面上部またはモーダル直前に配置） --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- 広告動画再生用モーダル --}}
<div class="modal fade" id="adModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-0">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btn-close-ad"></button>
      </div>
      <div class="modal-body p-0 text-center">
        <video id="adVideo" width="100%" controls controlsList="nodownload">
          {{-- asset() を使用してサブディレクトリ（/app01/）のパスズレを解消 --}}
          {{-- ※動作テスト用として、ファイルが未用意でも動くオンライン動画URLを一時設定しています --}}
          <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4">
          {{-- 本番用の動画ファイルにする場合は以下を有効化してください --}}
          {{-- <source src="{{ asset('videos/sample_ad.mp4') }}" type="video/mp4"> --}}
          お使いのブラウザは動画再生に対応していません。
        </video>
      </div>
      <div class="modal-footer border-0 pt-0 text-center text-white-50 small" id="ad-status-text">
        動画を最後まで視聴するとポイントが付与されます
      </div>
    </div>
  </div>
</div>

<style>
    .btn-check:checked + .card-select-option {
        border-color: #0d6efd !important;
        background-color: #f8f9fa;
    }
    .btn-check:checked + .card-select-option .selection-label {
        background-color: #0d6efd;
        color: #fff;
    }
    .btn-check:checked + .card-select-option .selection-label::after {
        content: "中";
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 要素の取得
    const startBtn = document.getElementById('btn-start-ad');
    const adVideo = document.getElementById('adVideo');
    const statusText = document.getElementById('ad-status-text');
    const closeBtn = document.getElementById('btn-close-ad');
    
    // Bootstrapのモーダル初期化
    const adModal = new bootstrap.Modal(document.getElementById('adModal'));
    
    let isRequesting = false; // 二重送信防止フラグ

    // 「視聴する」ボタンを押したときの処理
    startBtn.addEventListener('click', function() {
        // モーダルを表示
        adModal.show();
        // 状態を初期化して動画を最初から再生
        statusText.innerText = "動画を最後まで視聴するとポイントが付与されます";
        adVideo.currentTime = 0;
        adVideo.play();
    });

    // モーダルが閉じられたときの処理（途中でやめた場合）
    document.getElementById('adModal').addEventListener('hidden.bs.modal', function () {
        adVideo.pause(); // 動画を停止
    });

    // 動画が最後まで再生された（完了した）ときの処理
    adVideo.addEventListener('ended', function() {
        if(isRequesting) return; // すでにリクエスト中なら処理しない
        
        isRequesting = true;
        statusText.innerHTML = '<span class="text-warning"><i class="fa-solid fa-spinner fa-spin"></i> ポイントを獲得しています...</span>';
        closeBtn.style.display = 'none'; // 処理中に閉じられないようにする

        // サーバー（コントローラー）にポイント付与のリクエストを送る
        fetch("{{ route('point.ad') }}", {
            method: 'POST', // GETではなくPOSTにする
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            isRequesting = false;
            if (data.success) {
                statusText.innerHTML = '<span class="text-success fw-bold">ポイントを獲得しました！</span>';
                // 1秒後に画面をリロードして残高を反映
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                statusText.innerHTML = '<span class="text-danger">エラー: ' + (data.message || 'ポイントの付与に失敗しました') + '</span>';
                closeBtn.style.display = 'block'; // エラー時は閉じられるように戻す
            }
        })
        .catch(error => {
            isRequesting = false;
            console.error('Error:', error);
            statusText.innerHTML = '<span class="text-danger">通信エラーが発生しました。</span>';
            closeBtn.style.display = 'block';
        });
    });
});
</script>
@endsection