<div class="menu_section">
    @if(isset($tab1))
        @if($tab1 == "iotdevice")
            デバイス
            <li><a href="{{ route('admin.iotdevice.index') }}">            検索/変更/削除</a></li>
        @endif

        @if($tab1 == "virtualremote-blade")
            リモコン
            <br>
            <br>
            デザイン
            <li><a href="{{ route('admin.virtualremote.blade.create') }}">               新規登録</a></li>
            <li><a href="{{ route('admin.virtualremote.blade.index') }}">            検索/変更/削除</a></li>
            <br>
            ユーザー別
            <li><a href="{{ route('admin.virtualremote.blade.create') }}">               新規登録</a></li>
            <li><a href="{{ route('admin.virtualremote.blade.index') }}">            検索/変更/削除</a></li>
        @endif
        
        @if($tab1 == "user")
            ユーザー
            <li><a href="{{ route('admin.user.index') }}">                             ユーザー</a></li>
            <li><a href="{{ route('admin.user.request.index') }}">                          要望・問い合わせ</a></li>
        @endif
        @if($tab1 == "adv")
            広告
            <li><a href="{{ route('admin.adv.create') }}">                                 新規登録</a></li>
            <li><a href="{{ route('admin.adv.index') }}">                              検索/変更/削除</a></li>
        @endif
        
        @if($tab1 == "notification")
            通知
            <li><a href="{{ route('admin.notification.index', ['send_type' => 'mail']) }}">   メール通知</a></li>
            <li><a href="{{ route('admin.notification.index', ['send_type' => 'push']) }}">   プッシュ通知</a></li>
        @endif

        @if($tab1 == "another")
            その他
            <li><a href="{{ route('admin.memo.index') }}">                             メモ</a></li>

        @endif    
    @endif

</div>
