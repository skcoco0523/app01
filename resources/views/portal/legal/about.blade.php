@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 800px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="fs-5 mb-4 text-center text-dark fw-bold">当サービスについて</h2>
            <p class="small text-secondary mb-4">{{ config('app.name', 'Application') }}は、スマートリモコンをはじめとするIoT機器の操作や、日常に役立つ各種Webツールを提供するプラットフォームです。</p>

            <div class="d-flex flex-column gap-3 small">
                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">サービス名</div>
                    <div class="text-dark fw-semibold">{{ config('app.name', 'Application') }}</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">運営者</div>
                    <div class="text-dark">{{ config('app.name', 'Application') }} 開発チーム（代表：菅野）</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">事業内容</div>
                    <div class="text-dark">IoT機器（スマートリモコン等）制御システムの開発・運営、Webアプリケーションの提供</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">URL</div>
                    <div class="text-dark">
                        <a href="https://{{ config('app.base_domain', 'skcoco.com') }}" target="_blank" class="text-decoration-none me-2">https://{{ config('app.base_domain', 'skcoco.com') }}</a><br>
                        <a href="https://{{ config('app.sub_domain', 'app01.skcoco.com') }}" target="_blank" class="text-decoration-none">https://{{ config('app.sub_domain', 'app01.skcoco.com') }}</a>
                    </div>
                </div>

                <div>
                    <div class="fw-bold text-secondary mb-1">お問い合わせ</div>
                    <div class="text-dark">
                        <a href="{{ route('contact.index') }}">お問い合わせフォーム</a>よりご連絡ください。
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection