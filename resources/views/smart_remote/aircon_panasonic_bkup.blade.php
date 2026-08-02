<div class="remote-body mx-auto" style="max-width: 320px; background: #f4f5f7; border-radius: 24px; padding: 20px 16px; border: 1px solid #d0d5dd; box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-family: sans-serif;">
    
    {{-- Panasonic ブランドロゴ --}}
    <div class="text-center mb-2" style="font-weight: 700; letter-spacing: 2px; color: #222222; font-size: 15px;">
        Panasonic
    </div>

<script src="{{ asset('js/smart-remote/aircon.js') }}"></script>
<script>
    (function() {
        const initialSettings = {!! json_encode($virtual_remote->settings ?? [
            'power' => true,
            'temp'  => 25.0,
            'mode'  => 'cool',
            'fan'   => 'auto',
            'swingv' => 'auto'
        ]) !!};

        // DOMContentLoaded後に実行されるようにする
        const initAircon = () => {
            if (window.smartRemoteInstance) {
                new AirconRemote(window.smartRemoteInstance, initialSettings);
            } else {
                setTimeout(initAircon, 100);
            }
        };
        initAircon();
    })();
</script>

    {{-- LCD表示画面パネル --}}
    <div class="p-3 mb-3 text-center" style="background-color: #dbe4d8; border-radius: 8px; border: 2px solid #b2c0af; color: #2d3748; box-shadow: inset 0 2px 4px rgba(0,0,0,0.08);">
        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px; font-weight: bold; color: #4a5568;">
            <span id="lcd-mode">冷房</span>
            <span id="lcd-fan">風量 自動</span>
            <span id="lcd-swing">スイング</span>
        </div>
        <div id="lcd-power-off" style="display: none; font-size: 20px; font-weight: bold; color: #718096; margin: 10px 0;">停止中</div>
        <div id="lcd-main-display" class="d-flex justify-content-center align-items-baseline">
            <span id="lcd-temp" style="font-size: 38px; font-weight: 700; line-height: 1; font-family: 'Courier New', monospace; color: #1a202c;">25</span>
            <span style="font-size: 18px; font-weight: bold; margin-left: 2px;">℃</span>
        </div>
    </div>

    <div class="row justify-content-center g-2">
        <div class="col-12">

            {{-- メイン操作（停止 / 運転） --}}
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <button type="button" class="remote-button remote-button-h50 w-100 btn" 
                            style="background: linear-gradient(180deg, #ffffff 0%, #e9ecef 100%); border: 1px solid #ced4da; border-bottom: 3px solid #adb5bd; border-radius: 12px; font-weight: bold; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" 
                            data-lib-protocol="PANASONIC_AC" data-action="power-off">
                        <div class="remote-button-text remote-button-str2" style="color: #dc3545;">
                            停止
                        </div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="remote-button remote-button-h50 w-100 btn" 
                            style="background: linear-gradient(180deg, #ff9800 0%, #f57c00 100%); border: 1px solid #e65100; border-bottom: 3px solid #b26a00; border-radius: 12px; font-weight: bold; color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.15);" 
                            data-lib-protocol="PANASONIC_AC" data-action="power-on">
                        <div class="remote-button-text remote-button-str2">
                            運転
                        </div>
                    </button>
                </div>
            </div>

            {{-- 温度調整 --}}
            <div class="remote-dial-container p-2 mb-3 mx-auto text-center" style="background-color: #eaeeeff0; border-radius: 14px; border: 1px solid #d0d5dd;">
                <div class="text-muted mb-1" style="font-size: 11px; font-weight: bold; color: #6c757d;">温度</div>
                <div class="d-flex flex-column gap-1">
                    {{-- 上のボタン（温度上げる） --}}
                    <button type="button" class="remote-dial-button remote-dial-button-top btn w-100 d-flex align-items-center justify-content-center py-2" 
                            style="background: #ffffff; border: 1px solid #ced4da; border-radius: 8px; font-weight: bold; color: #333; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" 
                            data-lib-protocol="PANASONIC_AC" data-action="temp-up">
                        <span class="remote-dial-text remote-button-str1 me-1">高</span>
                        <i class="fa-solid fa-caret-up"></i>
                    </button>
                    {{-- 下のボタン（温度下げる） --}}
                    <button type="button" class="remote-dial-button remote-dial-button-bottom btn w-100 d-flex align-items-center justify-content-center py-2" 
                            style="background: #ffffff; border: 1px solid #ced4da; border-radius: 8px; font-weight: bold; color: #333; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" 
                            data-lib-protocol="PANASONIC_AC" data-action="temp-down">
                        <span class="remote-dial-text remote-button-str1 me-1">低</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                </div>
            </div>

            {{-- 運転切換 --}}
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <button type="button" class="remote-button remote-button-h40 w-100 btn" 
                            style="background: #ffffff; border: 1px solid #b8c2cc; border-bottom: 2px solid #9aa5b1; border-radius: 8px; color: #0056b3; font-weight: bold;" 
                            data-lib-protocol="PANASONIC_AC" data-action="mode-change" data-value="cool">
                        <div class="remote-button-text remote-button-str1">
                            冷房
                        </div>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="remote-button remote-button-h40 w-100 btn" 
                            style="background: #ffffff; border: 1px solid #b8c2cc; border-bottom: 2px solid #9aa5b1; border-radius: 8px; color: #c82333; font-weight: bold;" 
                            data-lib-protocol="PANASONIC_AC" data-action="mode-change" data-value="heat">
                        <div class="remote-button-text remote-button-str1">
                            暖房
                        </div>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="remote-button remote-button-h40 w-100 btn" 
                            style="background: #ffffff; border: 1px solid #b8c2cc; border-bottom: 2px solid #9aa5b1; border-radius: 8px; color: #17a2b8; font-weight: bold;" 
                            data-lib-protocol="PANASONIC_AC" data-action="mode-change" data-value="dry">
                        <div class="remote-button-text remote-button-str1">
                            除湿
                        </div>
                    </button>
                </div>
            </div>

            {{-- 風量・風向 --}}
            <div class="row g-2">
                <div class="col-6">
                    <button type="button" class="remote-button remote-button-h20 w-100 btn py-2" 
                            style="background: #ffffff; border: 1px solid #b8c2cc; border-radius: 20px; font-weight: bold; color: #495057; font-size: 13px;" 
                            data-lib-protocol="PANASONIC_AC" data-action="fan-change">
                        <span class="remote-label-text remote-button-str1">風量</span>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="remote-button remote-button-h20 w-100 btn py-2" 
                            style="background: #ffffff; border: 1px solid #b8c2cc; border-radius: 20px; font-weight: bold; color: #495057; font-size: 13px;" 
                            data-lib-protocol="PANASONIC_AC" data-action="swing-change">
                        <span class="remote-label-text remote-button-str1">風向</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>