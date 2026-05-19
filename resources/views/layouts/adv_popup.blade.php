<div id="adv_modal" class="notification-overlay">
    <div class="notification-modal" onclick="event.stopPropagation()" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">おすすめ情報</h5>
                <div id="countdown"></div>
            </div>
            <div class="modal-body">
                <div id="popup-adv-items" class="row g-2">
                    <!-- 広告がここに挿入される -->
                </div>
                <div id="adv-footer-actions" class="mt-3 d-flex justify-content-center gap-2" style="display:none !important;">
                    <button class="btn btn-outline-secondary btn-sm px-4" onclick="sendDislike()">興味がない</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentAdvCategoryIds = [];
    let modalStartTime = 0;

    function closeAdvModal(clickedId = null) {
        const duration = Math.floor((new Date().getTime() - modalStartTime) / 1000);
        
        if (clickedId) {
            // 広告がクリックされた場合：クリックしたカテゴリのみを「detail_view（好スコア）」として記録
            adv_action(clickedId, 'detail_view', duration);
        } else {
            // 広告がクリックされずに閉じられた場合（または「興味がない」以外で閉じられた場合）
            // 必要であればここで全件記録するが、今は重複を防ぐため何もしないか、
            // 滞在時間が長い場合のみ「detail_view」を記録する等に調整可能
        }

        // アクションがあったとみなし、インターバルを設定
        setCookie("adv_disp_time", new Date().getTime(), 7, "/");
        closeModal('adv_modal');
    }

    function sendDislike() {
        currentAdvCategoryIds.forEach(id => {
            adv_action(id, 'dislike');
        });
        // アクションとしてインターバルを設定
        setCookie("adv_disp_time", new Date().getTime(), 7, "/");
        closeModal('adv_modal');
    }

    function sendInterest(id) {
        const duration = Math.floor((new Date().getTime() - modalStartTime) / 1000);
        // 指定されたカテゴリのみ「選択」として記録
        adv_action(id, 'select', duration);
        
        // アクションとしてインターバルを設定
        setCookie("adv_disp_time", new Date().getTime(), 7, "/");
        closeModal('adv_modal');
    }

    document.addEventListener('DOMContentLoaded', async function() {
        const config = await getCommonConfig(30); // 30分キャッシュ（バージョン変更時は即反映）

        // 広告表示が無効な場合は何もしない
        if (config && config.adv_show_enable && parseInt(config.adv_show_enable.value1) === 0) {
            console.log('adv_show_enable is 0. skipping...');
            return;
        }

        const advDisplayInterval = config && config.adv_popup_interval ? parseInt(config.adv_popup_interval.value1) : 180;
        let advDisplayTime = 5; 
        let disp_flag = 0;
        const lastAdTime = getCookie('adv_disp_time');
        const currentTime = new Date().getTime();

        if (lastAdTime) {
            const elapsedTime = (currentTime - lastAdTime) / 1000;
            if (elapsedTime > advDisplayInterval) disp_flag = 1;
        } else {
            disp_flag = 1;
        }

        console.log('disp_flag:', disp_flag);
        if (disp_flag == 1) {
            const advertisements = await get_advertisement(3, "popup");
            if (advertisements && advertisements.length > 0) {
                const container = document.getElementById('popup-adv-items');
                container.innerHTML = '';
                currentAdvCategoryIds = [...new Set(advertisements.map(a => a.id))];

                advertisements.forEach(adv => {
                    const item = `
                        <div class="col-4">
                            <div class="card h-100 border-0">
                                <a href="${adv.href}" target="_blank" rel="nofollow" onclick="closeAdvModal(${adv.id})" class="text-decoration-none">
                                    <img src="${adv.src}" class="card-img-top" style="object-fit: contain; height: 100px;">
                                    <div class="card-body p-1 text-center">
                                        <p class="card-text mb-1" style="font-size: 0.6rem; line-height: 1.2; height: 3.6em; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; word-break: break-all;">${adv.name}</p>
                                        <p class="text-danger fw-bold small mb-1">${adv.price}円</p>
                                    </div>
                                </a>
                                <div class="text-center pb-2 adv-interest-btn-wrapper" style="display: none;">
                                    <button class="btn btn-primary btn-sm py-0 px-2" style="font-size: 0.7rem;" onclick="sendInterest(${adv.id})">興味がある</button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', item);
                });

                openModal('adv_modal');
                modalStartTime = new Date().getTime();

                const countdownDisplay = document.getElementById('countdown');
                const footerActions = document.getElementById('adv-footer-actions');
                countdownDisplay.innerHTML = advDisplayTime;

                const interval = setInterval(function() {
                    advDisplayTime--;
                    countdownDisplay.innerHTML = advDisplayTime;
                    if (advDisplayTime <= 0) {
                        clearInterval(interval);
                        countdownDisplay.style.display = 'none';
                        footerActions.classList.remove('d-none');
                        footerActions.style.setProperty('display', 'flex', 'important');
                        
                        // 個別の「興味がある」ボタンを表示
                        document.querySelectorAll('.adv-interest-btn-wrapper').forEach(el => {
                            el.style.display = 'block';
                        });
                    }
                }, 1000);
            }
        }
    });
</script>
