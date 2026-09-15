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
                    <div class="fw-bold" style="font-size: 14px;">広告を見て{{ $po_ad_reward }}ptゲット</div>
                    <div class="text-white-50 small" style="font-size: 11px;">
                        ※無償ポイント（{{ $periodLabel === 'AM' ? '午前' : '午後' }}あと <span id="ad-remaining-badge" class="fw-bold text-warning">{{ $remainingAdCount }}</span> / {{ $maxDailyLimit }} 回）
                    </div>
                </div>
            </div>
            <div>
                @if($remainingAdCount > 0)
                    <button type="button" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm text-dark" id="btn-start-ad" style="font-size: 12px;">
                        視聴する
                    </button>
                @else
                    <button type="button" class="btn btn-secondary btn-sm fw-bold rounded-pill px-3 shadow-sm" disabled style="font-size: 12px;">
                        {{ $periodLabel === 'AM' ? '午前上限' : '午後上限' }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- プラン選択エリア --}}
    <div class="mb-3">
        
        
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="fw-bold text-dark mb-1">ポイントパックを選択</h6>
            <a href="{{ route('point.about') }}" class="text-primary text-decoration-none fw-bold" style="font-size: 11px;">
                ポイントの詳しい仕組み <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        <form method="POST" action="{{ route('point.checkout') }}">
            @csrf
            
            <div class="row g-2 mb-4">
                @foreach($packs as $pack)
                
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
                                        @if($pack->bonus > 0)
                                            <span class="badge bg-danger rounded-pill" style="font-size: 10px;">
                                                +{{ number_format($pack->bonus) }}ptお得
                                            </span>
                                        @endif
                                    </div>
                                    <div class="fs-5 fw-bold text-primary">
                                        {{ number_format($pack->value2) }} <span class="fs-6 fw-normal text-dark">pt</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        1回 10pt〜 / 最大 約 {{ number_format(floor($pack->value2 / 10)) }} 回のコンテンツが利用可能
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
        <ul class="text-secondary ps-3 mb-0" style="font-size: 11px; line-height: 1.6;">
            <li>購入完了後、ポイントは即時反映されます。</li>
            <li>有償ポイントに有効期限はありません。無償ポイントから優先して消費されます。</li>
            <li>スマートリモコンの操作は1日50回まで無料（0pt）です。超過後は1回 Xpt 消費されます。</li>
            <li>お客様都合による購入後のキャンセル・返金はできません。</li>
        </ul>
    </div>

</div>

{{-- 共通モーダルの読み込み --}}
@include('layouts.adv_popup')

{{-- Google Publisher Tag --}}
<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>

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
    window.googletag = window.googletag || {cmd: []};

    document.addEventListener('DOMContentLoaded', function() {
        const startBtn = document.getElementById('btn-start-ad');
        let remainingAdCount = {{ $remainingAdCount }};
        const periodText = "{{ $periodLabel === 'AM' ? '午前' : '午後' }}";
        const nextTimeText = "{{ $periodLabel === 'AM' ? '12:00以降に再度お試しください。' : 'また明日お試しください。' }}";
        
        let rewardedSlot = null;
        let rewardPayload = null;
        let isClickTriggered = false;
        let pendingResult = null;

        function resetButton() {
            if (!startBtn) return;
            startBtn.disabled = false;
            startBtn.innerHTML = '視聴する';
            isClickTriggered = false;
        }

        if (remainingAdCount > 0) {
            googletag.cmd.push(function() {
                rewardedSlot = googletag.defineOutOfPageSlot(
                    '/22639388115/rewarded_web_example',
                    googletag.enums.OutOfPageFormat.REWARDED
                );

                if (!rewardedSlot) return;

                rewardedSlot.addService(googletag.pubads());

                // 1. 在庫チェック
                googletag.pubads().addEventListener('slotRenderEnded', function(event) {
                    if (event.slot === rewardedSlot && event.isEmpty) {
                        if (isClickTriggered) {
                            openModal('common-modal', {
                                title: 'お知らせ',
                                mess: '現在視聴できる広告がありません。時間をおいて再試行してください。',
                                user_chk: false
                            });
                            resetButton();
                        }
                    }
                });

                // 2. 広告の準備完了
                googletag.pubads().addEventListener('rewardedSlotReady', function(event) {
                    rewardPayload = event;
                    if (isClickTriggered) {
                        rewardPayload.makeRewardedVisible();
                    }
                });

                // 3. 視聴完了時（サーバーへポイント付与リクエスト）
                googletag.pubads().addEventListener('rewardedSlotGranted', function(event) {
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
                        pendingResult = data;
                    })
                    .catch(error => {
                        console.error('API Error:', error);
                        pendingResult = { success: false, message: '通信エラーが発生しました' };
                    });
                });

                // 4. ユーザーが広告を閉じた後の処理
                googletag.pubads().addEventListener('rewardedSlotClosed', function() {
                    rewardPayload = null;
                    resetButton();

                    if (pendingResult) {
                        if (pendingResult.success) {
                            openModal('common-modal', {
                                title: 'ポイント獲得',
                                mess: pendingResult.message || 'ポイントを獲得しました！',
                                user_chk: false
                            });
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            openModal('common-modal', {
                                title: 'お知らせ',
                                mess: pendingResult.message || 'ポイントの付与に失敗しました',
                                user_chk: false
                            });
                        }
                        pendingResult = null;
                    }

                    googletag.pubads().refresh([rewardedSlot]);
                });

                googletag.enableServices();
                googletag.display(rewardedSlot);
            });
        }

        // 「視聴する」ボタン押下時
        if (startBtn) {
            startBtn.addEventListener('click', function() {
                if (remainingAdCount <= 0) {
                    openModal('common-modal', {
                        title: 'お知らせ',
                        mess: `${periodText}の広告視聴上限（5回）に達しました。${nextTimeText}`,
                        user_chk: false
                    });
                    return;
                }

                if (startBtn.disabled) return;

                isClickTriggered = true;
                startBtn.disabled = true;
                startBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 読み込み中...';

                if (rewardPayload) {
                    rewardPayload.makeRewardedVisible();
                } else {
                    googletag.cmd.push(function() {
                        googletag.pubads().refresh([rewardedSlot]);
                    });

                    setTimeout(function() {
                        if (startBtn.disabled && !rewardPayload) {
                            openModal('common-modal', {
                                title: '読み込みエラー',
                                mess: '広告の読み込みに時間がかかっています。再度お試しください。',
                                user_chk: false
                            });
                            resetButton();
                        }
                    }, 10000);
                }
            });
        }
});
</script>
@endsection