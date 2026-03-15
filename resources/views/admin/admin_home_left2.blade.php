<div class="menu_section2">
    @if(isset($tab2))
        {{---------------- デバイスメニュー ------------------}}
        @if($tab1 == "iotdevice")
            @if($tab2 == "search")       @include('admin.admin_iotdevice_search_left') @endif
        @endif

        {{---------------- リモコンメニュー ------------------}}
        @if($tab1 == "virtualremote-blade")
            @if($tab2 == "search")          @include('admin.admin_virtualremoteblade_search_left')      @endif
        @endif

        {{---------------- ユーザーメニュー ------------------}}
        @if($tab1 == "user")
            @if($tab2 == "search")          @include('admin.admin_user_search_left')                    @endif
            @if($tab2 == "request")
                @if($tab3 == "search")      @include('admin.admin_request_search_left')                 @endif
            @endif
        @endif     

        {{---------------- 広告メニュー ------------------}}
        @if($tab1 == "adv")
            @if($tab2 == "search")          @include('admin.admin_adv_search_left')                     @endif
        @endif

        {{---------------- 通知メニュー ------------------}}
        @if($tab1 == "notification")
            @if($tab2 == "search")          @include('admin.admin_notification_left')                  @endif
        @endif

        {{---------------- その他メニュー ------------------}}
        @if($tab1 == "another")
            @if($tab2 == "memo")
                @if($tab3 == "search")      @include('admin.admin_memo_search_left')                    @endif
            @endif
        @endif
        
    @endif
        
</div>