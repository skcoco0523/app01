@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 600px;">

    {{-- 現在のポイント残高ミニカード --}}
    <div class="card border-0 shadow-sm mb-3 bg-light">
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
                <button type="button" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm text-dark" style="font-size: 12px;" id="btn-start-ad">
                    視聴する
                </button>
            </div>
        </div>
    </div>

    {{-- プラン選択エリア --}}
    <div class="mb-3">
        <h6 class="fw-bold text-dark mb-1">ポイントパックを選択</h6>
        <p class="text-muted small mb-3" style="font-size: 12px;">購入した有償ポイントに有効期限はありません。</p>

        <form method="POST" action="{{ route('point.checkout') }}">
            @csrf
            
            <div class="row g-2 mb-4">
                @foreach($packs as $pack)
                    @php
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
    <div class="card border-0 bg-light rounded p-3 mb-4">
        <div class="fw-bold text-secondary mb-1" style="font-size: 12px;">ご購入時のご注意</div>
        <ul class="text-secondary ps-3 mb-0" style="font-size: 11px; line-height: 1.6;">
            <li>購入完了後、ポイントは即時反映されます。</li>
            <li>有償ポイントに有効期限はありません。無料ポイントから優先して消費されます。</li>
            <li>お客様都合による購入後のキャンセル・返金はできません。</li>
        </ul>
    </div>

</div>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/js/bootstrap.bundle.min.js"></script>
{{-- Google IMA SDK の読み込み --}}
<script src="https://imasdk.googleapis.com/js/sdk/v3/ima3.js"></script>

{{-- 広告動画再生用モーダル --}}
<div class="modal fade" id="adModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-0">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btn-close-ad"></button>
      </div>
      <div class="modal-body p-0 text-center">
        {{-- IMA SDK 描画領域 --}}
        <div id="ad-container-wrapper" style="position: relative; width: 100%; min-height: 250px; background: #000;">
          <video id="contentElement" style="width:100%; height:100%; display:none;"></video>
          <div id="adContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 text-center text-white-50 small" id="ad-status-text">
        動画広告を最後まで視聴するとポイントが付与されます
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('btn-start-ad');
    const statusText = document.getElementById('ad-status-text');
    const closeBtn = document.getElementById('btn-close-ad');
    const adModal = new bootstrap.Modal(document.getElementById('adModal'));

    let adDisplayContainer;
    let adsLoader;
    let adsManager;
    let isRequesting = false;

    // Google公式 テスト用 VAST タグ（Ad Manager 承認後に自社のものへ変更）
    const VAST_TAG_URL = 'https://pubads.g.doubleclick.net/gampad/ads?sz=640x480&iu=/12431908/external/single_ad_samples&ciu_szs=300x250&impl=s&gdfp_req=1&env=vp&output=vast&unviewed_position_start=1&cust_params=deployment%3Ddevsite%26sample_ct%3Dlinear&correlator=';

    // IMA SDK 初期化
    function initIMA() {
        const adContainer = document.getElementById('adContainer');
        const contentElement = document.getElementById('contentElement');

        adDisplayContainer = new google.ima.AdDisplayContainer(adContainer, contentElement);
        adsLoader = new google.ima.AdsLoader(adDisplayContainer);

        adsLoader.addEventListener(
            google.ima.AdsManagerLoadedEvent.Type.ADS_MANAGER_LOADED,
            onAdsManagerLoaded,
            false
        );
        adsLoader.addEventListener(
            google.ima.AdErrorEvent.Type.AD_ERROR,
            onAdError,
            false
        );
    }

    // 広告リクエスト
    function requestAds() {
        adDisplayContainer.initialize();
        const adsRequest = new google.ima.AdsRequest();
        adsRequest.adTagUrl = VAST_TAG_URL;
        adsRequest.linearAdSlotWidth = document.getElementById('ad-container-wrapper').clientWidth;
        adsRequest.linearAdSlotHeight = 250;
        adsLoader.requestAds(adsRequest);
    }

    // 広告ロード成功
    function onAdsManagerLoaded(adsManagerLoadedEvent) {
        const adsRenderingSettings = new google.ima.AdsRenderingSettings();
        adsManager = adsManagerLoadedEvent.getAdsManager(document.getElementById('contentElement'), adsRenderingSettings);

        adsManager.addEventListener(google.ima.AdErrorEvent.Type.AD_ERROR, onAdError);
        
        // 広告完了イベントのハンドリング
        adsManager.addEventListener(google.ima.AdEvent.Type.COMPLETE, onAdComplete);

        try {
            adsManager.init(
                document.getElementById('ad-container-wrapper').clientWidth,
                250,
                google.ima.ViewMode.NORMAL
            );
            adsManager.start();
        } catch (adError) {
            onAdError(adError);
        }
    }

    // 広告再生エラー
    function onAdError(adErrorEvent) {
        console.error('Ad Error:', adErrorEvent);
        if (adsManager) adsManager.destroy();
        statusText.innerHTML = '<span class="text-danger">広告の読み込みに失敗しました。時間をおいて再試行してください。</span>';
        closeBtn.style.display = 'block';
    }

    // 広告視聴完了時の処理
    function onAdComplete() {
        if (isRequesting) return;
        isRequesting = true;

        statusText.innerHTML = '<span class="text-warning"><i class="fa-solid fa-spinner fa-spin"></i> ポイントを獲得しています...</span>';
        closeBtn.style.display = 'none';

        fetch("{{ route('point.ad') }}", {
            method: 'POST',
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
                statusText.innerHTML = '<span class="text-success fw-bold">' + data.message + '</span>';
                setTimeout(() => {
                    location.reload();
                }, 1200);
            } else {
                statusText.innerHTML = '<span class="text-danger">' + (data.message || 'ポイントの付与に失敗しました') + '</span>';
                closeBtn.style.display = 'block';
            }
        })
        .catch(error => {
            isRequesting = false;
            console.error('Error:', error);
            statusText.innerHTML = '<span class="text-danger">通信エラーが発生しました。</span>';
            closeBtn.style.display = 'block';
        });
    }

    // ボタン押下時
    startBtn.addEventListener('click', function() {
        adModal.show();
        statusText.innerText = "動画広告を最後まで視聴するとポイントが付与されます";
        closeBtn.style.display = 'block';
        if (!adDisplayContainer) {
            initIMA();
        }
        requestAds();
    });

    // モーダルが閉じられた際の後処理
    document.getElementById('adModal').addEventListener('hidden.bs.modal', function () {
        if (adsManager) {
            adsManager.destroy();
        }
    });
});
</script>
@endsection