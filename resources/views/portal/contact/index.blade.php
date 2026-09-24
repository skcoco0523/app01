@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 600px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="fw-bold mb-0 text-dark fs-6">お問い合わせ</h5>
        </div>

        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('contact.send') }}">
                @csrf

                {{-- 未ログインユーザー（ゲスト）の場合のみメールアドレス欄を表示 --}}
                @guest
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold small">
                            返信先メールアドレス <span class="badge bg-danger ms-1">必須</span>
                        </label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required placeholder="example@skcoco.com">
                        <div class="form-text small" style="font-size: 11px;">回答をご希望の場合は、受信可能なメールアドレスをご入力ください。</div>
                    </div>
                @endguest

                <div class="mb-3">
                    <label for="type" class="form-label fw-bold small">お問い合わせ種別</label>
                    <select name="type" class="form-select" required>
                        <option value="0" {{ isset($input['type']) && $input['type'] == '0' ? 'selected' : '' }}>ご要望・ご意見</option>
                        <option value="1" {{ isset($input['type']) && $input['type'] == '1' ? 'selected' : '' }}>サービスに関するお問い合わせ</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label fw-bold small">お問い合わせ内容</label>
                    <textarea class="form-control" name="message" rows="5" required placeholder="具体的な内容をご記入ください">{{ $input['message'] ?? '' }}</textarea>
                    <div class="form-text small" style="font-size: 11px;">※通常24時間〜48時間以内に担当（菅野）より返答させていただきます。内容によっては回答を差し控えさせていただく場合もございますので、予めご了承ください。</div>
                </div>

                {{-- 同意文言 --}}
                <div class="mb-3 text-center small text-secondary" style="font-size: 11px;">
                    送信いただく前に<a href="{{ route('privacy') }}" target="_blank">プライバシーポリシー</a>をご確認ください。
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold py-2">
                        同意して送信する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection