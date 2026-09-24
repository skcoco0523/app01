@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 800px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="fs-5 mb-4 text-center text-dark fw-bold">利用規約</h2>
            <p class="small text-secondary mb-4">本規約は、{{ config('app.name', 'Application') }}（以下、「当サービス」）が提供する各種サービスの利用条件を定めるものです。ユーザーの皆様は、本規約に同意の上で当サービスをご利用ください。</p>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第1条（適用）</h6>
            <p class="small text-secondary">本規約は、ユーザーと当サービスとの間のサービスの利用に関わる一切の関係に適用されます。</p>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第2条（ユーザー登録およびアカウント管理）</h6>
            <p class="small text-secondary">1. 登録希望者が当サービスの定める方法によってユーザー登録を申請し、当サービスがこれを承認することによってユーザー登録が完了します。<br>
            2. ユーザーは、自己の責任においてアカウントおよびパスワードを厳重に管理するものとします。</p>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第3条（禁止事項）</h6>
            <p class="small text-secondary mb-2">ユーザーは、本サービスの利用にあたり、以下の行為を行ってはなりません。</p>
            <ul class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                <li>法令または公序良俗に違反する行為</li>
                <li>犯罪行為に関連する行為</li>
                <li>当サービスのサーバーまたはネットワークの機能を破壊したり、妨害したりする行為</li>
                <li>当サービスの配信するシステム、通信データへの不正アクセスまたはクラッキング行為</li>
                <li>他のユーザーに関する個人情報等を収集または蓄積する行為</li>
                <li>その他、当サービスが不適切と判断する行為</li>
            </ul>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第4条（サービスの停止・中断）</h6>
            <p class="small text-secondary">当サービスは、システムの保守点検・更新、停電、天災地変などの不可抗力により、事前通知なく本サービスの全部または一部の提供を停止または中断することがあります。</p>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第5条（免責事項）</h6>
            <p class="small text-secondary">1. 当サービスは、提供するシステムおよびコンテンツに事実上または法律上の瑕疵がないことを保証するものではありません。<br>
            2. 当サービスは、本サービスの利用によりユーザーに生じた損害について、一切の責任を負いません。</p>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第6条（利用規約の変更）</h6>
            <p class="small text-secondary">当サービスは、必要と判断した場合には、ユーザーに通知することなくいつでも本規約を変更することができるものとします。</p>

            <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">第7条（準拠法・裁判管轄）</h6>
            <p class="small text-secondary mb-0">本規約の解釈にあたっては、日本法を準拠法とします。本サービスに関して紛争が生じた場合、当サービスの運営元所在地を管轄する裁判所を専属的合意管轄とします。</p>
        </div>
    </div>
</div>
@endsection