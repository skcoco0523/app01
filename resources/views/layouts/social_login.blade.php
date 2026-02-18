<div class="py-3">
    <div class="card">
        <div class="card-header">{{ __('Social Login') }}</div>

        <div class="card-body">
            <?//Lineログイン?>
            {{-- iOS向けメッセージ --}}
            <div id="ios-message" class="alert alert-info mt-3" style="display:none; font-size:0.85rem; line-height:1.2;">
                iOSをご利用の方へ<br>
                iOSの仕様上、SNSログイン後にアプリ上でログイン状態を保持できません。<br>
                ログイン後、メニュー→プロフィールよりメールアドレスを登録いただくと、<br>
                メールアドレスでログイン後にアプリ上にてログイン状態を保持されます。
            </div>
            <div class="login-container">
                <div class="line-login-container">
                    <a href="{{ route('linelogin') }}" class="login-button">
                        <img src="{{ asset('img/line/btn_login_base.png') }}" class="social-login-button-img" loading="eager">
                        <div class="overlay"></div>
                    </a>
                </div>
                {{--
                <div class="google-login-container py-3">
                    <a href="" class="login-button">
                        <img src="{{ asset('img/google/btn_login_base.png') }}" class="social-login-button-img" loading="eager">
                        <div class="overlay"></div>
                    </a>
                </div>
                --}}
            </div>
        </div>
    </div>
</div>

<script>

    document.addEventListener('DOMContentLoaded', function() {
        var os = getOS();
            console.log('getOS:', os);
        if(os == 'iOS') {
            // メッセージ表示
            console.log('iOS detected: Safariでログインしてください');
            document.getElementById('ios-message').style.display = 'block';

            // もし必要なら、LINEログインボタンも無効化
            // document.getElementById('line-login-btn').addEventListener('click', function(e) {
            //     e.preventDefault();
            //     alert('iOS PWAではSafariでログインしてください');
            // });
        }
    });
</script>