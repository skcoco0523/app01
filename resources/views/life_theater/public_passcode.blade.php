@extends('layouts.app')

@section('content')
<div class="container py-5 text-center">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 420px;">
        <div class="card-body p-4">
            <div class="mb-3 text-warning">
                <i class="fa-solid fa-lock fa-3x"></i>
            </div>
            <h5 class="fw-bold mb-2">合言葉が必要です</h5>
            <p class="text-muted small mb-4">
                この作品を再生するには発行者から共有された合言葉を入力してください。
            </p>

            @if (session('error_msg'))
                <div class="alert alert-danger small py-2 mb-3">
                    {{ session('error_msg') }}
                </div>
            @endif

            <form action="{{ route('life_theater.public_play.verify', ['token' => $token]) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <div class="input-group mb-3">
                        {{-- デフォルトで -webkit-text-security: disc; を適用して伏せ字にする --}}
                        <input type="text" id="passcodeInput" name="password" class="form-control text-center" placeholder="合言葉を入力" required autofocus maxlength="20" style="-webkit-text-security: disc;">
                        
                        {{-- 切り替えボタン --}}
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasscodeVisibility(this)" title="合言葉を表示/非表示">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">
                    再生画面を開く
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
<script>
function togglePasscodeVisibility(btn) {
    const input = document.getElementById('passcodeInput');
    const icon = document.getElementById('toggleIcon');
    
    // 伏せ字（disc）が設定されている場合は解除し、設定されていない場合は伏せ字にする
    if (input.style.webkitTextSecurity === 'disc') {
        // 表示状態（漢字変換候補が見える）にする
        input.style.webkitTextSecurity = 'none';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        // 伏せ字（●●●）にする
        input.style.webkitTextSecurity = 'disc';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>