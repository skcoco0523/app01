<div class="remote-body mx-auto" style="max-width: 300px; background: #f8f9fa; border-radius: 28px; padding: 18px 16px 20px; border: 2px solid #e0e0e0; box-shadow: 0 10px 25px rgba(0,0,0,0.08); font-family: 'Helvetica Neue', Arial, sans-serif;">

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

    {{-- LCD表示画面パネル (実機風の黒枠＆レトロ緑液晶) --}}
    <div class="mb-3" style="background-color: #1a1a1a; border-radius: 8px; padding: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);">
        <div class="p-2" style="background-color: #acc1a7; border-radius: 4px; color: #1a2e1d; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);">
            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px; font-weight: bold; border-bottom: 1px solid #94a88f; padding-bottom: 3px;">
                <span id="lcd-mode">冷房</span>
                <span id="lcd-fan">風量 自動</span>
                <span id="lcd-swing">スイング</span>
                <span id="lcd-clean" style="display: none;">クリーン</span>
            </div>
            <div id="lcd-power-off" style="display: none; font-size: 20px; font-weight: bold; color: #4a5c4c; margin: 10px 0; text-align: center;">停止中</div>
            <div id="lcd-main-display" class="d-flex justify-content-center align-items-baseline my-1">
                <span id="lcd-temp" style="font-size: 42px; font-weight: 700; line-height: 1; font-family: 'Courier New', monospace; letter-spacing: -2px;">25</span>
                <span style="font-size: 20px; font-weight: bold; margin-left: 2px;">℃</span>
            </div>
        </div>
    </div>

    {{-- ボタン配置エリア --}}
    <div class="d-flex flex-column gap-3">

        {{-- 1段目: 運転切換 (実機風パステルカラーボタン) --}}
        <div class="row g-2 text-center">
            <div class="col-4">
                <button type="button" class="btn w-100 py-2" 
                        style="background: #eef7ff; border: 2px solid #4299e1; border-radius: 8px; color: #2b6cb0; font-weight: bold; font-size: 14px; box-shadow: 0 2px 0 #3182ce;" 
                        data-lib-protocol="PANASONIC_AC" data-action="mode-change" data-value="cool">
                    冷房
                </button>
            </div>
            <div class="col-4">
                <button type="button" class="btn w-100 py-2" 
                        style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 8px; color: #2f855a; font-weight: bold; font-size: 14px; box-shadow: 0 2px 0 #38a169;" 
                        data-lib-protocol="PANASONIC_AC" data-action="mode-change" data-value="dry">
                    除湿
                </button>
            </div>
            <div class="col-4">
                <button type="button" class="btn w-100 py-2" 
                        style="background: #fff5f0; border: 2px solid #ed8936; border-radius: 8px; color: #c05621; font-weight: bold; font-size: 14px; box-shadow: 0 2px 0 #dd6b20;" 
                        data-lib-protocol="PANASONIC_AC" data-action="mode-change" data-value="heat">
                    暖房
                </button>
            </div>
        </div>

        {{-- 2段目: 風向・温度調整・風量 --}}
        <div class="row g-2 align-items-center text-center">
            <div class="col-4">
                <button type="button" class="btn w-100 py-2" 
                        style="background: #ffffff; border: 1.5px solid #a0aec0; border-radius: 8px; font-weight: bold; color: #2d3748; font-size: 13px; box-shadow: 0 2px 0 #cbd5e0;" 
                        data-lib-protocol="PANASONIC_AC" data-action="swing-change">
                    風向
                </button>
                <button type="button" class="btn w-100 py-2 mt-2" 
                        style="background: #ffffff; border: 1.5px solid #a0aec0; border-radius: 8px; font-weight: bold; color: #2d3748; font-size: 13px; box-shadow: 0 2px 0 #cbd5e0;" 
                        data-lib-protocol="PANASONIC_AC" data-action="clean-toggle">
                    内部ｸﾘｰﾝ
                </button>
            </div>
            {{-- 中央: 温度ボタン（実機風の縦型操作パネル） --}}
            <div class="col-4">
                <div style="background: #ffffff; border: 1.5px solid #a0aec0; border-radius: 10px; padding: 4px; box-shadow: 0 2px 0 #cbd5e0;">
                    <button type="button" class="btn btn-sm w-100 py-1 mb-1" 
                            style="background: #f7fafc; border: 1px solid #cbd5e0; border-radius: 6px; font-weight: bold; color: #2d3748;" 
                            data-lib-protocol="PANASONIC_AC" data-action="temp-up">
                        <i class="fa-solid fa-caret-up"></i>
                    </button>
                    <div style="font-size: 11px; font-weight: bold; color: #4a5568; margin: -2px 0;">温度</div>
                    <button type="button" class="btn btn-sm w-100 py-1 mt-1" 
                            style="background: #f7fafc; border: 1px solid #cbd5e0; border-radius: 6px; font-weight: bold; color: #2d3748;" 
                            data-lib-protocol="PANASONIC_AC" data-action="temp-down">
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                </div>
            </div>
            <div class="col-4">
                <button type="button" class="btn w-100 py-2" 
                        style="background: #ffffff; border: 1.5px solid #a0aec0; border-radius: 8px; font-weight: bold; color: #2d3748; font-size: 13px; box-shadow: 0 2px 0 #cbd5e0;" 
                        data-lib-protocol="PANASONIC_AC" data-action="fan-change">
                    風量
                </button>
                <button type="button" class="btn w-100 py-2 mt-2" 
                        style="background: #ffffff; border: 1.5px solid #a0aec0; border-radius: 8px; font-weight: bold; color: #2d3748; font-size: 13px; box-shadow: 0 2px 0 #cbd5e0;" 
                        data-lib-protocol="PANASONIC_AC" data-action="quiet-toggle">
                    しずか
                </button>
            </div>
        </div>

        {{-- 3段目: 停止・運転 --}}
        <div class="row g-2 justify-content-center text-center mt-1">
            <div class="col-5">
                {{-- 実機風の赤枠「停止」ボタン --}}
                <button type="button" class="btn w-100 py-2" 
                        style="background: #ffffff; border: 2px solid #e53e3e; border-radius: 8px; font-weight: bold; color: #c53030; font-size: 14px; box-shadow: 0 2px 0 #9b2c2c;" 
                        data-lib-protocol="PANASONIC_AC" data-action="power-off">
                    停止
                </button>
            </div>
        </div>

    </div>

    {{-- Panasonic ブランドロゴ & エアコン表記 --}}
    <div class="text-center mt-4">
        <div style="font-weight: 800; letter-spacing: 1.5px; color: #1a202c; font-size: 16px; font-family: sans-serif;">
            Panasonic
        </div>
        <div style="font-size: 11px; color: #718096; font-weight: bold; margin-top: -2px;">
            エアコン
        </div>
    </div>

</div>