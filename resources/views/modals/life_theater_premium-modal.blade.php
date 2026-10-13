{{-- プレミアム解放専用モーダル --}}
<div id="life_theater_premium-modal" class="notification-overlay" onclick="closeModal('life_theater_premium-modal')">
    <div class="notification-modal" style="max-width: 480px; width: 92%;" onclick="event.stopPropagation()">
        <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 18px;">
            
            <div class="text-white p-4 text-center position-relative" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" aria-label="Close" onclick="closeModal('life_theater_premium-modal')"></button>
                <div class="mb-2">
                    <span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-crown text-warning fs-3"></i>
                    </span>
                </div>
                <h4 class="fw-bold mb-1 tracking-wide">Premium アップグレード</h4>
                <p class="small text-white-50 mb-0">作品の枠制限を大幅解放＆限定演出が利用可能になります</p>
            </div>

            <div class="modal-body p-4 bg-light">
                <div class="bg-white p-3 rounded-3 border mb-3 shadow-sm">
                    <div class="small fw-bold text-muted mb-2 text-uppercase tracking-wider">解放されるアップグレード特典</div>
                    
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <div class="me-3 text-warning fs-5" style="width: 24px; text-align: center;"><i class="fa-solid fa-film"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small">スライド登録枠</div>
                            <small class="text-muted" style="font-size: 11px;">
                                <span id="prem_slide_current">-</span>コマ → <span class="fw-bold text-dark">最大<span id="prem_slide_max">-</span>コマ</span> に拡張
                            </small>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" id="prem_slide_add">+-コマ</span>
                    </div>

                    <div class="d-flex align-items-center py-2 border-bottom">
                        <div class="me-3 text-warning fs-5" style="width: 24px; text-align: center;"><i class="fa-solid fa-photo-film"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small">画像ライブラリ保存枚数</div>
                            <small class="text-muted" style="font-size: 11px;">
                                <span id="prem_media_current">-</span>枚 → <span class="fw-bold text-dark">最大<span id="prem_media_max">-</span>枚</span> に拡張
                            </small>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" id="prem_media_add">+-枚</span>
                    </div>

                    <div class="d-flex align-items-center py-2 border-bottom">
                        <div class="me-3 text-warning fs-5" style="width: 24px; text-align: center;"><i class="fa-solid fa-comments"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small">キャスト・吹き出し配置上限</div>
                            <small class="text-muted" style="font-size: 11px;">
                                1スライドあたり <span id="prem_object_current">-</span>個 → <span class="fw-bold text-dark">最大<span id="prem_object_max">-</span>個</span> に拡張
                            </small>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">3倍</span>
                    </div>

                    <div class="d-flex align-items-center py-2">
                        <div class="me-3 text-warning fs-5" style="width: 24px; text-align: center;"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small">限定の再生演出・カスタマイズ</div>
                            <small class="text-muted" style="font-size: 11px;">切り替え速度、粒子エフェクト、ブランドロゴ非表示など</small>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">フル解放</span>
                    </div>
                </div>

                <div class="text-center py-2">
                    <span class="text-muted small">必要ポイント：</span>
                    <span class="fs-4 fw-bold text-danger" id="prem_cost">-</span>
                    <span class="small fw-bold"> pt</span>
                </div>
            </div>

            <div class="modal-footer border-0 p-3 bg-white justify-content-center">
                <button type="button" class="btn btn-light text-secondary px-4 me-2" onclick="closeModal('life_theater_premium-modal')">閉じる</button>
                <button type="button" class="btn btn-warning fw-bold px-4 shadow-sm" id="btn_submit_premium" onclick="document.getElementById('theaterPremiumBuyForm').submit();" disabled>
                    <i class="fa-solid fa-crown me-1"></i> 今すぐ解放する
                </button>
            </div>

        </div>
    </div>
</div>