@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 800px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="fs-5 mb-3 text-center text-dark fw-bold">プライバシーポリシー</h2>
            <p class="text-muted text-center mb-4 small">制定日：2025年12月5日<br>最終改定日：2026年9月24日</p>

            <section class="mb-4">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">1. 個人情報保護の方針</h5>
                <p class="small text-secondary mb-1">当サイト（{{ config('app.base_domain', 'skcoco.com') }} およびそのサブドメインで提供されるサービス、例：{{ config('app.sub_domain', 'app01.skcoco.com') }}）は、提供するサービス（以下、「本サービス」といいます）におけるユーザーの個人情報の取扱いについて、以下のとおりプライバシーポリシー（以下、「本ポリシー」といいます）を定めます。</p>
                <p class="small text-secondary mb-0">運営者名：{{ config('app.name', 'Application') }} 開発チーム</p>
            </section>

            <section class="mb-4">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">2. 広告配信サービスについて（Google AdSense）</h5>
                <p class="small text-secondary mb-2">当サイトでは、第三者配信の広告サービス「<strong>Google AdSense</strong>」を利用しています。</p>
                <ul class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                    <li>Google AdSenseは、ユーザーの興味に応じた商品やサービスの広告を表示するため、<strong>Cookie</strong>（クッキー）を使用することがあります。</li>
                    <li>Cookieを使用することで、当サイトや他のサイトへのアクセス情報に基づき、適切な広告をユーザーに表示できます。</li>
                    <li>Cookieには氏名、住所、メールアドレス、電話番号などの<strong>個人を特定する情報は含まれません</strong>。</li>
                    <li>ユーザーは、ご自身のブラウザ設定からCookieの利用を<strong>無効</strong>にすることができます。</li>
                    <li>Googleが広告目的でCookieを使用する方法に関する詳細は、<a href="https://policies.google.com/technologies/ads?hl=ja" target="_blank" rel="noopener">Googleのポリシーと規約</a>をご確認ください。</li>
                </ul>
            </section>

            <section class="mb-4">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">3. アクセス解析ツールについて</h5>
                <p class="small text-secondary mb-0">当サイトでは、利用状況の分析のために「<strong>Google Analytics</strong>」を利用しています。Google Analyticsは、トラフィックデータ収集のためにCookieを使用します。このトラフィックデータは匿名で収集されており、個人を特定するものではありません。</p>
            </section>

            <section class="mb-4">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">4. 本サービスにおける個人情報の取得と利用目的</h5>
                <p class="small text-secondary mb-2">本サービス（スマートリモコンなど）を利用する際に、以下の情報を取得し、それぞれの目的で利用します。</p>
                <ul class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                    <li><strong>取得情報:</strong> ユーザー登録時またはお問い合わせ時のメールアドレス、パスワード</li>
                    <li><strong>利用目的:</strong> ログイン認証、サービス利用時の通信、お問い合わせへの回答、不正利用防止のため</li>
                    <li><strong>取得情報:</strong> スマートリモコンの設定データ（機器情報、操作ログなど）</li>
                    <li><strong>利用目的:</strong> サービス提供、機能改善、トラブルシューティングのため</li>
                </ul>
            </section>

            <section class="mb-4">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">5. 免責事項</h5>
                <p class="small text-secondary mb-0">当サイトからのリンクやバナーなどで移動した外部サイトで提供される情報、サービス等について、一切の責任を負いません。また、当サイトのコンテンツについて、可能な限り正確な情報を掲載するよう努めていますが、誤情報が入り込んだり、情報が古くなっている可能性もあります。当サイトに掲載された内容によって生じた損害等の一切の責任を負いかねます。</p>
            </section>

            <section class="mb-4">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">6. 著作権</h5>
                <p class="small text-secondary mb-0">当サイトに掲載されているコンテンツ（文章・画像・デザイン・システム構造等）の著作権は、{{ config('app.name', 'Application') }} 開発チーム（菅野）に帰属します。無断での転載、複製、販売等の行為は固く禁じます。</p>
            </section>

            <section class="mb-0">
                <h5 class="fw-bold fs-6 border-bottom pb-2 text-secondary">7. お問い合わせ</h5>
                <p class="small text-secondary mb-0">本ポリシーに関するお問い合わせ、または個人情報の取り扱いに関するご質問は、<a href="{{ route('contact.index') }}">お問い合わせフォーム</a>よりご連絡ください。<br>連絡先：skcoco0523@gmail.com</p>
            </section>
        </div>
    </div>
</div>
@endsection