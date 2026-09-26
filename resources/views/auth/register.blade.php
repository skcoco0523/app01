@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">{{ __('Register') }}</div>

    <div class="card-body">
        @if (session('line_error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof openModal === 'function') {
                        openModal('common-modal', {
                            title: 'お知らせ',
                            mess: @json(session('line_error')),
                            user_chk: false
                        });
                    }
                });
            </script>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="row mb-3">
                <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>

                <div class="col-md-6">
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                <div class="col-md-6">
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                <div class="col-md-6">
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                <div class="col-md-6">
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-md-4 col-form-label text-md-end">{{ __('Gender') }}</label>

                <div class="col-md-6">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="man" value="0" required>
                        <label class="form-check-label" for="man">{{ __('Man') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="woman" value="1" required>
                        <label class="form-check-label" for="woman">{{ __('Woman') }}</label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <label for="birthdate" class="col-md-4 col-form-label text-md-end">{{ __('Birthdate') }}</label>

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-4">
                            <select id="birth_year" name="birth_year" class="form-control" required>
                                <option value="">{{ __('Year') }}</option>
                                @for ($i = date('Y'); $i >= 1900; $i--)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-4">
                            <select id="birth_month" name="birth_month" class="form-control" required>
                                <option value="">{{ __('Month') }}</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-4">
                            <select id="birth_day" name="birth_day" class="form-control" required>
                                <option value="">{{ __('Day') }}</option>
                                @for ($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $prefectures = [
                    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県','茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
                    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県','静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
                    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県','徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
                    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
                ];
            @endphp
            <div class="row mb-3">
                <label for="prefectures" class="col-md-4 col-form-label text-md-end">{{ __('Prefectures') }}</label>

                <div class="col-md-6">
                    <select name="prefectures" id="inputPrefectures" class="form-control" required>
                        <option value=""></option>
                        @foreach ($prefectures as $prefecture)
                            <option value="{{ $prefecture }}" {{ (isset($select->prefectures) && $select->prefectures == $prefecture) ? 'selected' : '' }}>
                                {{ $prefecture }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <?//reCAPTCHA 不正登録対応?>
            <div class="row mb-0">
                <div class="col-md-8 offset-md-4">
                    <input type="hidden" id="recaptcha-token" name="g-recaptcha-response">
                    @error('g-recaptcha-response')
                        <strong style="color: red;">{{ $message }}</strong>
                    @enderror
                </div>
            </div>

            <div class="row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Register') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

<?//外部ログイン用?>
@include('layouts.social_login', ['loginAction' => 'register'])

@endsection



<!-- reCAPTCHAのスクリプト -->
<script src="https://www.google.com/recaptcha/api.js?render={{ config('captcha.sitekey') }}"></script>

<script>
    console.log("start-----------------"); // コンソールに出力
    var sitekey = @json(config('captcha.sitekey')); 
    console.log(sitekey); // コンソールに出力
    grecaptcha.ready(function() {
        grecaptcha.execute(sitekey, { action: 'register' }).then(function(token) {
            // トークンをフォームに追加
            document.getElementById('recaptcha-token').value = token;
        });
    });

    // フォーム送信時にトークンが確実にセットされているかチェック
    document.querySelector('form').addEventListener('submit', function(e) {
        var token = document.getElementById('recaptcha-token').value;
        if (!token) {
            e.preventDefault(); // トークンがない場合、フォーム送信を停止
            alert("reCAPTCHAの検証が完了していません。");
        }
    });
</script>