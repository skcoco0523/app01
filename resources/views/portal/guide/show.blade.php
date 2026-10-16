@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        {{-- 一覧へ戻る --}}
        <div class="mb-3">
            <a href="{{ route('guide.index') }}" class="small text-decoration-none text-primary">
                <i class="fa-solid fa-chevron-left"></i> ガイド一覧へ戻る
            </a>
        </div>

        @php
            // slugのハイフンをアンダーバーに変換（例: life-theater -> life_theater_detail、smart-remote -> smart_remote_detail）
            if ($slug === 'life-theater') {
                $detailView = 'portal.guide.detail.life_theater_detail';
            } elseif ($slug === 'smart-remote') {
                $detailView = 'portal.guide.detail.smart_remote_detail';
            } else {
                $detailView = 'portal.guide.detail.' . str_replace('-', '_', $slug);
            }
        @endphp

        @if(View::exists($detailView))
            @include($detailView)
        @else
            <div class="alert alert-warning text-center py-4">
                <h4 class="fw-bold">記事が見つかりませんでした</h4>
                <p class="text-secondary small mb-3">お探しのガイド記事が存在しないか、URLが間違っている可能性があります。</p>
                <a href="{{ route('guide.index') }}" class="btn btn-primary btn-sm px-3">ガイド一覧へ戻る</a>
            </div>
        @endif

        {{-- 共通サポート案内 --}}
        <div class="mt-5 p-3 bg-white border rounded shadow-sm text-center">
            <p class="mb-2 fw-bold text-dark">設定についてご不明な点がある場合</p>
            <p class="text-secondary small mb-3">操作や設定が上手くいかない場合は、お気軽にお問い合わせください。</p>
            <a href="{{ route('contact.index') }}" class="btn btn-outline-primary btn-sm px-4 fw-bold">
                <i class="fa-regular fa-envelope me-1"></i>お問い合わせフォームへ
            </a>
        </div>

    </div>
</div>
@endsection









