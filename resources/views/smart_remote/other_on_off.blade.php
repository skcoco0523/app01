<div class="remote-body">
    <div class="row justify-content-center">
        <div class="col-md-12 col-sm-12 col-12">

            <div class="row g-3 mt-3">
                <div class="col-6">
                    <button type="button" class="remote-button remote-button-h50" style="background-color: #6c757d;" data-button-num="1">
                        <div class="remote-button-text remote-button-str3"></div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="remote-button remote-button-h50" style="background-color: #fd7e14;" data-button-num="2">
                        <div class="remote-button-text remote-button-str3"></div>
                    </button>
                </div>
            </div>

            {{-- ダイヤルデザイン --}}
            <div class="remote-dial-container mt-3" style="background-color: #f0f0f0;" >
                {{-- 左のボタン --}}
                <button type="button" class="remote-dial-button remote-dial-button-left" style="background-color: #f0f0f0;" data-button-num="4">         
                    <i class="fa-solid fa-caret-left"></i>
                </button>
                {{-- 右のボタン --}}
                <button type="button" class="remote-dial-button remote-dial-button-right" style="background-color: #f0f0f0;" data-button-num="5">
                    <i class="fa-solid fa-caret-right"></i>
                </button>
                {{-- 上のボタン --}}
                <button type="button" class="remote-dial-button remote-dial-button-top" style="background-color: #f0f0f0;" data-button-num="6">
                    <i class="fa-solid fa-caret-up"></i>
                </button>
                {{-- 下のボタン --}}
                <button type="button" class="remote-dial-button remote-dial-button-bottom" style="background-color: #f0f0f0;" data-button-num="7">
                    <i class="fa-solid fa-caret-down"></i>
                </button>

                {{-- 中央のボタン --}}
                <button type="button" class="remote-dial-button remote-dial-button-center" style="background-color: #fd7e14;" data-button-num="8">
                    <span class="remote-button-text remote-button-str3"></span>
                </button>
            </div>


            <div class="row g-3 mt-3">
                <div class="col-6">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <div class="remote-label-text remote-button-str1"></div>
                        <button type="button" class="remote-button remote-button-h20" style="background-color: #f0f0f0;" data-button-num="9"></button>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <div class="remote-label-text remote-button-str1"></div>
                        <button type="button" class="remote-button remote-button-h20" style="background-color: #f0f0f0;" data-button-num="10"></button>
                    </div>
                </div>
            </div>


        </div>
    </div>
    
</div>

@if (!isset($preview) || !$preview)
    <script>
        
    </script>
@endif