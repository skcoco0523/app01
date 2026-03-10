@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">{{ __('Profile') }}</div>

    <div class="card-body">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            <input type="hidden"  name="id" value="{{ $profile->id }}">
            <div class="row mb-3">
                <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>

                <div class="col-md-6">
                    <input id="name" type="text" class="form-control" name="name" value="{{ $profile->name }}" required autocomplete="name" autofocus>

                </div>
            </div>

            <div class="row mb-3">
                <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                <div class="col-md-6">
                    <input id="email" type="email" class="form-control" name="email" value="{{ $profile->email}}" autocomplete="email">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4" style="display: flex; align-items: center;">
                    <label for="friend_code" class="col-form-label text-md-end">ユーザーID　</label>
                    <script>
                        const params = {
                            //「&」が「%amp;」に変換されてしまう問題を回避するため、{!!" "!!}
                            url: "{!! route('friend.index', ['friend_code' => $profile->friend_code, 'table' => 'search']) !!}",
                            
                            
                        }
                    </script>
                    <p onclick="openModal('share-modal',params)" class="mb-0">
                        <i class="fa-regular fa-share-from-square icon-20"></i>
                    </p>
                </div>
                <div class="col-md-6">
                    <input id="friend_code" type="text" class="form-control" name="friend_code" value="{{ $profile->friend_code }}" disabled autocomplete="friend_code" autofocus>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-md-4 col-form-label text-md-end">{{ __('Gender') }}</label>

                <div class="col-md-6">
                    <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="gender" id="man" value="0" {{ $profile->gender === 0 ? 'checked' : '' }} required>
                        <label class="form-check-label" for="man">{{ __('Man') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="woman" value="1" {{ $profile->gender === 1 ? 'checked' : '' }} required>
                        <label class="form-check-label" for="woman">{{ __('Woman') }}</label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <label for="birthdate" class="col-md-4 col-form-label text-md-end">{{ __('Birthdate') }}</label>

                <div class="col-md-6">
                    <input id="birthdate" type="date" max="9999-12-31" class="form-control" name="birthdate" 
                        value="{{ $profile->birthdate ? \Carbon\Carbon::parse($profile->birthdate)->format('Y-m-d') : '' }}"  
                        required autocomplete="birthdate" autofocus>
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
                        <option value="" {{ ($profile->prefectures ?? '') == '' ? 'selected' : '' }}></option>
                        @foreach ($prefectures as $prefecture)
                            <option value="{{ $prefecture }}" {{ (isset($profile->prefectures) && $profile->prefectures == $prefecture) ? 'selected' : '' }}>
                                {{ $prefecture }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6 offset-md-4">
                    <div class="form-check">
                        <input type="hidden" name="release_flag" value="0">
                        <input class="form-check-input" type="checkbox" name="release_flag" value="1" {{ $profile->release_flag ? 'checked' : '' }}>

                        <label class="form-check-label" for="release_flag">
                            {{ __('Restrict disclosure to friends') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 offset-md-4">
                    <div class="form-check">
                        <input type="hidden" name="mail_flag" value="0">
                        <input class="form-check-input" type="checkbox" name="mail_flag" value="1" {{ $profile->mail_flag ? 'checked' : '' }}>

                        <label class="form-check-label" for="mail_flag">
                            {{ __('Restricting email delivery') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Update') }}
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<?//ェアポップアップモーダル?>  
@include('modals.share-modal')

@endsection
