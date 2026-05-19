
{{-- ユーザー情報更新処理 --}}
<form id="user_chg_form" method="POST" action="{{ route('admin.user.update') }}">
    @csrf
    {{--検索条件--}}
    <input type="hidden" name="search_name"                 value="{{$input['search_name'] ?? ''}}">
    <input type="hidden" name="search_email"                value="{{$input['search_email'] ?? ''}}">
    <input type="hidden" name="search_friendcode"           value="{{$input['search_friendcode'] ?? ''}}">
    <input type="hidden" name="search_gender"               value="{{$input['search_gender'] ?? ''}}">
    <input type="hidden" name="search_release_flag"         value="{{$input['search_release_flag'] ?? ''}}">
    <input type="hidden" name="search_mail_flag"            value="{{$input['search_mail_flag'] ?? ''}}">
    <input type="hidden" name="search_admin_flag"           value="{{$input['search_admin_flag'] ?? ''}}">
    <input type="hidden" name="search_dev_reg_lock_flag"    value="{{$input['search_dev_reg_lock_flag'] ?? ''}}">
    <input type="hidden" name="page"                        value="{{request()->input('page') ?? $input['page'] ?? '' }}">
    {{--対象データ--}}
    <input type="hidden" name="id" value="{{$select->id ?? ''}}">
    
    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-6 col-md-3">
            <label for="inputname" class="form-label">ﾕｰｻﾞｰ名</label>
            <input type="text" name="name" class="form-control" placeholder="name" value="{{$select->name ?? ''}}">
        </div>
        <div class="col-6 col-md-3">
            <label for="" class="form-label">ｱﾄﾞﾚｽ</label>
            <input type="email" name="email" class="form-control" placeholder="XXX@gmail.com" value="{{$select->email ?? ''}}">
        </div>
        <div class="col-6 col-md-3">
            <label for="inputbirth" class="form-label">誕生日</label>
            <input type="date" max="9999-12-31" name="birthdate" class="form-control" value="{{$select->birthdate ?? ''}}">
        </div>
        @php
            $prefectures = [
                '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県','茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
                '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県','静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
                '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県','徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
                '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
            ];
        @endphp
        <div class="col-6 col-md-3">
            <label for="inputbirth" class="form-label">都道府県</label>
            <select name="prefectures" class="form-control">
                @foreach ($prefectures as $prefecture)
                    <option value="{{$prefecture}}" {{ (($select->prefectures ?? '') == $prefecture) ? 'selected' : '' }}>
                    {{$prefecture}}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    
    <div class="row g-3 align-items-stretch mb-3">
        <div class="col-6 col-md-3">
            <label for="inputsex" class="form-label">性別</label>
            <select name="gender" class="form-control">
                <option value="0" {{ ($select->gender ?? '') == '0' ? 'selected' : '' }}>男性</option>
                <option value="1" {{ ($select->gender ?? '') == '1' ? 'selected' : '' }}>女性</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="inputsex" class="form-label">公開</label>
            <select name="release_flag" class="form-control">
                <option value="0" {{ ($select->release_flag ?? '') == '0' ? 'selected' : '' }}>許可</option>
                <option value="1" {{ ($select->release_flag ?? '') == '1' ? 'selected' : '' }}>拒否</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="inputsex" class="form-label">メール送信</label>
            <select name="mail_flag" class="form-control">
                <option value="0" {{ ($select->mail_flag ?? '') == '0' ? 'selected' : '' }}>許可</option>
                <option value="1" {{ ($select->mail_flag ?? '') == '1' ? 'selected' : '' }}>拒否</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="inputsex" class="form-label">ﾃﾞﾊﾞｲｽ追加権限</label>
            <select name="dev_reg_lock" class="form-control">
                <option value="0" {{ ($select->dev_reg_lock ?? '') == '0' ? 'selected' : '' }}>許可</option>
                <option value="1" {{ ($select->dev_reg_lock ?? '') == '1' ? 'selected' : '' }}>拒否</option>
            </select>
        </div>
    </div>
    
    <div class="text-end mb-3">
        <input type="submit" value="更新" class="btn btn-primary">
    </div>
    
</form>

{{--エラー--}}
@if(isset($msg))
    <div class="alert alert-danger">
        {!! nl2br(e($msg)) !!}
    </div>
@endif


{{--ユーザー一覧--}}
@if(isset($user_list))
    {{--ﾊﾟﾗﾒｰﾀ--}}
    @php
        $page_prm = $input ?? '';
    @endphp
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $user_list,'page_prm' => $page_prm,])
    <div style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered fs-6">
            <thead>
            <tr>
                <th scope="col" class="fw-light">ID</th>
                <th scope="col" class="fw-light">ﾕｰｻﾞｰ名</th>
                <th scope="col" class="fw-light">外部連携</th>
                <th scope="col" class="fw-light">ｱﾄﾞﾚｽ</th>
                <th scope="col" class="fw-light">ﾌﾚﾝﾄﾞｺｰﾄﾞ</th>
                <th scope="col" class="fw-light">誕生日</th>
                <th scope="col" class="fw-light">都道府県</th>
                <th scope="col" class="fw-light">性別</th>
                <th scope="col" class="fw-light">公開</th>
                <th scope="col" class="fw-light">ﾒｰﾙ送信</th>
                <th scope="col" class="fw-light">ﾛｸﾞｲﾝ数</th>
                <th scope="col" class="fw-light">ﾌﾚﾝﾄﾞ数</th>
                <th scope="col" class="fw-light">ﾃﾞﾊﾞｲｽ<br>追加権限</th>
                <th scope="col" class="fw-light">ﾛｯｸ<br>回数</th>
                <th scope="col" class="fw-light">最終<br>ﾛｸﾞｲﾝ日</th>
                <th scope="col" class="fw-light">データ<br>登録日</th>
                <th scope="col" class="fw-light">データ<br>更新日</th>
                <th scope="col" class="fw-light"></th>
            </tr>
            </thead>
            @foreach($user_list as $user)
                <tr>
                    <td class="fw-light">{{$user->id}}</td>
                    <td class="fw-light">{{$user->name}}</td>
                    <td class="fw-light">{{$user->provider}}</td>
                    <td class="fw-light">{{$user->email}}</td>
                    <td class="fw-light">{{$user->friend_code}}</td>
                    <td class="fw-light">{{$user->birthdate}}</td>
                    <td class="fw-light">{{$user->prefectures}}</td>
                    <td class="fw-light">{{$user->gender === null ? '' : ($user->gender == '0' ? '男性' : '女性')}}</td>
                    <td class="fw-light">{{$user->release_flag == '0' ? '許可' : '拒否' }}</td>
                    <td class="fw-light">{{$user->mail_flag == '0' ? '許可' : '拒否' }}</td>
                    <td class="fw-light">{{$user->login_cnt}}</td>
                    <td class="fw-light">{{$user->friend_cnt}}</td>
                    <td class="fw-light">{{$user->dev_reg_lock == '0' ? '許可' : '拒否' }}</td>
                    <td class="fw-light">{{$user->dev_reg_lock_cnt}}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $user->last_login_date) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $user->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $user->updated_at) !!}</td>
                    <td class="fw-light">
                        <input type="button" value="編集" class="btn btn-primary edit-btn">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{--ﾍﾟｰｼﾞｬｰ--}}
    @include('admin.layouts.pagination', ['paginator' => $user_list,'page_prm' => $page_prm,])

@endif



<script>
document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('user_chg_form');
    //更新フォームを非表示
    form.style.display = 'none';

    // 各行の編集ボタンにイベントリスナーを追加
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            // フォームを表示
            form.style.display = 'block';

            // ボタンの親要素（行）を取得
            const row   = this.closest('tr');
            const cells = row.querySelectorAll('td');
            
            const id            = cells[0].textContent;
            const name          = cells[1].textContent;
            const email         = cells[3].textContent;
            const birthdate     = cells[5].textContent;
            //const prefectures   = cells[5].textContent;

            // フォームの対応するフィールドにデータを設定
            form.querySelector('input[name="id"]').value        = id;
            form.querySelector('input[name="name"]').value      = name;
            form.querySelector('input[name="email"]').value     = email;
            form.querySelector('input[name="birthdate"]').value = birthdate;
            
            //都道府県を設定
            const prefectures = form.querySelector('select[name="prefectures"]');
            prefectures.value = cells[6].textContent;
            
            // 性別の選択肢を設定
            const gender = form.querySelector('select[name="gender"]');
            gender.value = (cells[7].textContent.trim() === '男性') ? '0' : '1';

            // リリースフラグの選択肢を設定
            const release_flag = form.querySelector('select[name="release_flag"]');
            release_flag.value = (cells[8].textContent.trim() === '許可') ? '0' : '1';

            // メールフラグの選択肢を設定
            const mail_flag = form.querySelector('select[name="mail_flag"]');
            mail_flag.value = (cells[9].textContent.trim() === '許可') ? '0' : '1';

            // デバイス追加権限の選択肢を設定
            const dev_reg_lock = form.querySelector('select[name="dev_reg_lock"]');
            dev_reg_lock.value = (cells[12].textContent.trim() === '許可') ? '0' : '1';

        });
    });
});
</script>