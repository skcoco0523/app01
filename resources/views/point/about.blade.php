@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 600px;">

    {{-- ヘッダー --}}
    <div class="d-flex align-items-center mb-3">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fa-solid fa-arrow-left"></i> 戻る
        </a>
        <h5 class="fw-bold m-0 text-dark">ポイントの仕組みについて</h5>
    </div>

    {{-- 1. 有償ポイントと無償ポイントの違い --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold text-primary mb-2">
                <i class="fa-solid fa-coins me-1"></i> 2種類のポイントについて
            </h6>
            <div class="row g-2 text-center my-2">
                <div class="col-6">
                    <div class="p-2 border rounded bg-light">
                        <div class="fw-bold small text-dark">無償ポイント</div>
                        <div class="text-muted" style="font-size: 11px;">
                            @if($free_point_reset_flag)
                            毎月リセット・
                            @endif
                            広告視聴など
                        </div>
                        <span class="badge bg-secondary mt-1">有効期限あり</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 border rounded bg-light">
                        <div class="fw-bold small text-primary">有償ポイント</div>
                        <div class="text-muted" style="font-size: 11px;">ショップで購入した分</div>
                        <span class="badge bg-success mt-1">無期限</span>
                    </div>
                </div>
            </div>
            
            {{-- ★ 将来の画像差し込みエリア --}}
            <div class="my-3 text-center bg-light border rounded p-3 text-muted" style="font-size: 12px;">
                <i class="fa-regular fa-image fs-4 d-block mb-1"></i>
                【ポイント構成イメージ画像をここに配置】
            </div>
            
            <p class="text-muted small mb-0" style="font-size: 11px; line-height: 1.5;">
                サービス利用時は、<strong>無償ポイントから優先して消費</strong>されます。無償ポイントが不足した場合にのみ、有償ポイントが消費されます。
            </p>
        </div>
    </div>

    {{-- 2. スマートリモコン・デバイス操作ルール --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold text-primary mb-2">
                <i class="fa-solid fa-toggle-on me-1"></i> スマートリモコンの利用制限
            </h6>
            
            {{-- アプリからの操作 --}}
            <div class="p-2 border-start border-4 border-success bg-light mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="fw-bold text-dark small">アプリからの操作</div>
                    <span class="badge bg-success">無制限（0pt）</span>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    スマホアプリ画面からの操作は、回数制限なくいつでも無料でご利用いただけます。
                </div>
            </div>

            {{-- スマートボット（名前未定）からの操作 --}}
            <div class="p-2 border-start border-4 border-warning bg-light mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="fw-bold text-dark small">スマートボットからの操作</div>
                    <span class="badge bg-warning text-dark">1日 50回まで無料</span>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    1日{{ $whisper_free }}回まで無料（0pt）で利用可能です（毎日午前0時にリセット）。<br>
                    {{ $whisper_free }}回を超過した場合は、1回の操作につき <strong>{{ $whisper_po }}pt</strong> 消費されます。
                </div>
            </div>

            {{-- ★ 将来の画像差し込みエリア --}}
            <div class="mt-3 text-center bg-light border rounded p-3 text-muted" style="font-size: 12px;">
                <i class="fa-regular fa-image fs-4 d-block mb-1"></i>
                【アプリ操作とスマートボット操作の違いの解説画像をここに配置】
            </div>
        </div>
    </div>

    {{-- 3. 月一リセットと広告付与ルール --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold text-primary mb-2">
                <i class="fa-solid fa-calendar-check me-1"></i> 無償ポイントの獲得
                @if($free_point_reset_flag)
                    とリセット
                @endif
            </h6>
            <ul class="text-muted ps-3 mb-0" style="font-size: 11px; line-height: 1.6;">
                
                @if($free_point_reset_flag)
                    <li><strong>毎月1日のリセット：</strong> 無償ポイントが{{ $service_free_point }}pt未満の場合、
                        {{ $service_free_point }}ptまで自動補充されます（{{ $service_free_point }}pt以上保持している場合は減少・変更されません）。</li>
                @endif
                <li><strong>広告視聴：</strong> 1日午前・午後各5回まで視聴でき、1回につき {{ $ad_reward_point }}pt の無償ポイントを獲得できます。</li>
            </ul>
        </div>
    </div>

    {{-- 購入画面へ戻るボタン --}}
    <div class="text-center mt-4 mb-3">
        <a href="{{ route('point.buy') }}" class="btn btn-primary rounded-pill px-4 fw-bold">
            ポイント購入画面へ
        </a>
    </div>

</div>
@endsection