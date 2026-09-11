@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 600px;">

{{-- ポイント残高 --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                <span class="fw-bold small text-secondary">
                    ポイント残高
                    @if($free_point_reset_flag)
                        (毎月<?=$service_free_point?>ptにリセット)
                    @endif
                </span>
                {{-- さりげない追加リンク --}}
                <a href="#" class="small text-decoration-none text-primary" style="font-size: 12px;">
                    追加はこちらから <i class="fa-solid fa-chevron-right" style="font-size: 10px;" class="ms-1"></i>
                </a>
            </div>
            
            <div class="d-flex justify-content-between align-items-center px-1">
                {{-- 左側：無償pt / 有償pt --}}
                <div class="lh-base">
                    <div class="small text-secondary">
                        無償pt：<span class="fw-bold text-dark fs-6">{{ number_format($profile->free_point ?? 0) }}</span>
                    </div>
                    <div class="small text-secondary">
                        有償pt：<span class="fw-bold text-primary fs-6">{{ number_format($profile->pay_point ?? 0) }}</span>
                    </div>
                </div>

                {{-- 右側：合計ポイント --}}
                <div class="text-end bg-light rounded-3 px-3 py-2 border">
                    <div class="text-secondary small mb-1" style="font-size: 11px;">合計ポイント</div>
                    <div class="fw-bold text-primary fs-5 lh-1">
                        {{ number_format(($profile->free_point ?? 0) + ($profile->pay_point ?? 0)) }}<span class="fs-6 fw-normal ms-1 text-dark">pt</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- プロフィール編集フォーム --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <div class="fw-bold small text-secondary mb-3 border-bottom pb-2">プロフィール設定</div>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                <input type="hidden" name="id" value="{{ $profile->id }}">

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold small">お名前</label>
                    <input id="name" type="text" class="form-control" name="name" value="{{ $profile->name }}" required autocomplete="name">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold small">メールアドレス</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ $profile->email }}" autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="friend_code" class="form-label fw-bold small">ユーザーID</label>
                    <div class="input-group">
                        <input id="friend_code" type="text" class="form-control bg-light" value="{{ $profile->friend_code }}" disabled>
                        <script>
                            const params = {
                                url: "{!! route('friend.index', ['friend_code' => $profile->friend_code, 'table' => 'search']) !!}",
                            }
                        </script>
                        <button class="btn btn-outline-secondary px-3" type="button" onclick="openModal('share-modal', params)">
                            <i class="fa-regular fa-share-from-square"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small d-block">性別</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="man" value="0" {{ $profile->gender === 0 ? 'checked' : '' }} required>
                        <label class="form-check-label" for="man">男性</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="woman" value="1" {{ $profile->gender === 1 ? 'checked' : '' }} required>
                        <label class="form-check-label" for="woman">女性</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="birthdate" class="form-label fw-bold small">生年月日</label>
                    <input id="birthdate" type="date" max="9999-12-31" class="form-control" name="birthdate" 
                        value="{{ $profile->birthdate ? \Carbon\Carbon::parse($profile->birthdate)->format('Y-m-d') : '' }}" required>
                </div>

                @php
                    $prefectures = [
                        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県','茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
                        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県','静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
                        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県','徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
                        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
                    ];
                @endphp
                <div class="mb-3">
                    <label for="inputPrefectures" class="form-label fw-bold small">都道府県</label>
                    <select name="prefectures" id="inputPrefectures" class="form-select" required>
                        <option value="" {{ ($profile->prefectures ?? '') == '' ? 'selected' : '' }}>選択してください</option>
                        @foreach ($prefectures as $prefecture)
                            <option value="{{ $prefecture }}" {{ (isset($profile->prefectures) && $profile->prefectures == $prefecture) ? 'selected' : '' }}>
                                {{ $prefecture }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-2">
                    <div class="form-check">
                        <input type="hidden" name="release_flag" value="0">
                        <input class="form-check-input" type="checkbox" name="release_flag" id="release_flag" value="1" {{ $profile->release_flag ? 'checked' : '' }}>
                        <label class="form-check-label small text-secondary" for="release_flag">
                            フレンドへの公開を制限する
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input type="hidden" name="mail_flag" value="0">
                        <input class="form-check-input" type="checkbox" name="mail_flag" id="mail_flag" value="1" {{ $profile->mail_flag ? 'checked' : '' }}>
                        <label class="form-check-label small text-secondary" for="mail_flag">
                            メール配信を停止する
                        </label>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary py-2 fw-bold">
                        更新する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@include('modals.share-modal')
@endsection