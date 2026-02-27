@extends('layouts.app')

@section('content')
<div class="card">
        
    <div class="card-header">メールアドレス認証</div>

    <div class="card-body">
        <div class="alert alert-info mt-3" style="font-size:0.85rem; line-height:1.2;">
            新規登録、もしくはメールアドレスが変更されています。<br>
            続行するには、メールに送信された確認リンクをご確認ください。<br>
            もしメールが届いていない場合は以下より再送の上、<br>
            再度ご確認ください。<br>
        </div>
        
        <div class="mb-3">
            <strong>送信先: {{ Auth::user()->email }}</strong>
        </div>
        <form id="verification-form" class="d-inline" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" id="send-btn" class="btn btn-primary" disabled>送信</button>
        </form>

    </div>
               
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // IDをHTMLと完全に一致させる。または btn.form で取得する
    const btn = document.getElementById('send-btn');
    const KEY = 'v_wait';
    if (!btn) return;

    const tick = (exp) => {
        btn.disabled = true;
        const itv = setInterval(() => {
            const left = Math.ceil((exp - Date.now()) / 1000);
            if (left > 0) return btn.innerText = `再送可能まで ${left}秒`;
            clearInterval(itv);
            (btn.disabled = false, btn.innerText = "送信", localStorage.removeItem(KEY));
        }, 1000);
    };

    // 初回(null)はNumber(null)=0となり、必ず下のelse側（有効化）が実行される
    const wait = Number(localStorage.getItem(KEY));
    (wait > Date.now()) ? tick(wait) : (btn.disabled = false, btn.innerText = "送信");

    // フォームIDに依存せず、ボタンが属するフォームにイベントを付ける
    btn.form.onsubmit = () => {
        localStorage.setItem(KEY, Date.now() + 15000);
        (btn.disabled = true, btn.innerText = "送信中...");
    };
});
</script>
@endsection

