
{{-- メール通知処理 --}}
@if($input['send_type'] == 'mail' && count($user_list) > 0)
    <form id="mail-send_form" method="POST" action="{{ route('admin.mail.send') }}">
        @csrf
        {{--検索条件--}}
        <input type="hidden" name="search_name" value="{{$input['search_name'] ?? ''}}">
        <input type="hidden" name="search_email" value="{{$input['search_email'] ?? ''}}">
        <input type="hidden" name="search_friendcode" value="{{$input['search_friendcode'] ?? ''}}">
        <input type="hidden" name="search_gender" value="{{$input['search_gender'] ?? ''}}">
        <input type="hidden" name="search_release_flag" value="{{$input['search_release_flag'] ?? ''}}">
        <input type="hidden" name="search_mail_flag" value="{{$input['search_mail_flag'] ?? ''}}">
        <input type="hidden" name="search_admin_flag" value="{{$input['search_admin_flag'] ?? ''}}">
        <input type="hidden" name="send_type" value="{{$input['send_type'] ?? ''}}">
        <input type="hidden" name="send_target" value="{{$input['send_target'] ?? ''}}">
        
        <input type="hidden" name="page" value="{{request()->input('page') ?? $input['page'] ?? '' }}">
        {{--対象データ--}}
        
        メール通知
        <div class="row g-3 align-items-stretch mb-3">
            <div class="col-12 col-md-6">
                <label for="inputname" class="form-label">件名</label>
                <input type="text" name="title" class="form-control" placeholder="subject" value="{{$input['title'] ?? ''}}">
            </div>
            <div class="col-12 col-md-12">
                <label for="inputname" class="form-label">本文</label>
                <textarea name="content" class="form-control" placeholder="本文" rows="5">{{$input['content'] ?? ''}}</textarea>
            </div>
        </div>
        
        <div class="text-end mb-3">
            <input type="submit" value="送信" class="btn btn-primary">
        </div>
        
    </form>
@endif

{{-- プッシュ通知処理 --}}
@if($input['send_type'] == 'push' && count($user_list) > 0)
    <form id="push-send_form" method="POST" action="{{ route('admin.push.send') }}">
        @csrf
        {{--検索条件--}}
        <input type="hidden" name="search_name" value="{{$input['search_name'] ?? ''}}">
        <input type="hidden" name="search_email" value="{{$input['search_email'] ?? ''}}">
        <input type="hidden" name="search_friendcode" value="{{$input['search_friendcode'] ?? ''}}">
        <input type="hidden" name="search_gender" value="{{$input['search_gender'] ?? ''}}">
        <input type="hidden" name="search_release_flag" value="{{$input['search_release_flag'] ?? ''}}">
        <input type="hidden" name="search_mail_flag" value="{{$input['search_mail_flag'] ?? ''}}">
        <input type="hidden" name="search_admin_flag" value="{{$input['search_admin_flag'] ?? ''}}">
        <input type="hidden" name="send_type" value="{{$input['send_type'] ?? ''}}">
        <input type="hidden" name="send_target" value="{{$input['send_target'] ?? ''}}">

        <input type="hidden" name="page" value="{{request()->input('page') ?? $input['page'] ?? '' }}">
        {{--対象データ--}}
        
        プッシュ通知
        <div class="row g-3 align-items-stretch mb-3">
            <div class="col-12 col-md-6">
                <label for="inputname" class="form-label">タイトル</label>
                <input type="text" name="title" class="form-control" placeholder="title" value="{{$input['title'] ?? ''}}">
            </div>
            <div class="col-12 col-md-6">
                <label for="inputname" class="form-label">URL「route(XXXXX)」</label>
                <input type="text" name="route" class="form-control" placeholder="profile.show" value="{{$input['route'] ?? ''}}">
            </div>

            <div class="col-12 col-md-12">
                <label for="inputname" class="form-label">内容</label>
                <textarea name="content" class="form-control" placeholder="通知内容" rows="5">{{$input['content'] ?? ''}}</textarea>
            </div>
        </div>
        
        <div class="text-end mb-3">
            <input type="submit" value="送信" class="btn btn-primary">
        </div>
        
    </form>
@endif

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
                <th scope="col" class="fw-light">最終ﾛｸﾞｲﾝ日</th>
                <th scope="col" class="fw-light">データ登録日</th>
                <th scope="col" class="fw-light">データ更新日</th>
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
                    <td class="fw-light">{!! str_replace(' ', '<br>', $user->last_login_date) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $user->created_at) !!}</td>
                    <td class="fw-light">{!! str_replace(' ', '<br>', $user->updated_at) !!}</td>
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

});
</script>