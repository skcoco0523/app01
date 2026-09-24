@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 800px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="fs-5 mb-4 text-center text-dark fw-bold">特定商取引法に基づく表記</h2>

            <div class="d-flex flex-column gap-3 small">
                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">事業者名</div>
                    <div class="text-dark">{{ config('app.name', 'Application') }} 開発チーム</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">代表者・運営責任者</div>
                    <div class="text-dark">菅野</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">所在地</div>
                    <div class="text-dark">請求があった場合に遅滞なく開示します。</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">お問い合わせ先</div>
                    <div class="text-dark">
                        メールアドレス：skcoco0523@gmail.com<br>
                        または<a href="{{ route('contact.index') }}">お問い合わせフォーム</a>よりご連絡ください。
                    </div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">販売価格</div>
                    <div class="text-dark">ポイント購入画面に表示する価格（消費税込み）に準じます。</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">商品代金以外の必要料金</div>
                    <div class="text-dark">サイト閲覧、サービス利用、通信に必要となるインターネット接続代金および通信会社所定の通信料。</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">お支払い方法</div>
                    <div class="text-dark">クレジットカード決済（その他購入画面にて案内する決済手段）</div>
                </div>

                <div class="border-bottom pb-2">
                    <div class="fw-bold text-secondary mb-1">引き渡し時期</div>
                    <div class="text-dark">購入手続き完了後、即時アカウントにポイントが反映されます。</div>
                </div>

                <div>
                    <div class="fw-bold text-secondary mb-1">返品・キャンセルについて</div>
                    <div class="text-dark">デジタルコンテンツの特性上、購入完了後の返品・返金・キャンセルには応じられません。</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection