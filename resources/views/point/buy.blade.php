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
                    <div class="text-white-50 small" style="font-size: 11px;">CMを見て無料ポイントを獲得</div>
                </div>
            </div>
            <div>
                <a href="{{ route('point.ad') }}" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm text-dark" style="font-size: 12px;">
                    視聴する
                </a>
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
                        // 価格と付与ポイントの差分（お得ポイント数）を計算
                        $bonus = $pack->value2 - $pack->value1;
                    @endphp
                    <div class="col-12">
                        {{-- input と label を並列にし、for 属性で紐付ける --}}
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
                                {{-- 左側：パック名、お得バッジ、ポイント数 --}}
                                <div>
                                    <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                        <span class="fw-bold text-dark">{{ $pack->description }}</span>
                                        
                                        @if(!empty($pack->badge))
                                            <span class="badge bg-warning text-dark rounded-pill" style="font-size: 10px;">{{ $pack->badge }}</span>
                                        @endif
                                        
                                        {{-- 差分お得バッジ --}}
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

                                {{-- 右側：価格と選択状態表示 --}}
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

            {{-- 決済ボタンエリア --}}
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

{{-- ラジオボタン選択時の枠線強調用スタイル --}}
<style>
    .btn-check:checked + .card-select-option {
        border-color: #0d6efd !important;
        background-color: #f8f9fa;
    }
    .btn-check:checked + .card-select-option .selection-label {
        background-color: #0d6efd;
        color: #fff;
    }
    /* 選択時に「選択中」へ表示切り替え */
    .btn-check:checked + .card-select-option .selection-label::after {
        content: "中";
    }
</style>
@endsection