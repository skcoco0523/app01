<div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner" id="banner-adv-items">
        <!-- JavaScriptで動的に挿入される -->
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<script>
    // 非同期で広告を取得し、表示する
    async function loadAdvertisements() {
        // 設定チェック
        const config = await getCommonConfig(30);
        if (config && config.adv_show_enable && parseInt(config.adv_show_enable.value1) === 0) {
            console.log('adv_show_enable is 0. skipping banner...');
            return;
        }

        try {
            // おすすめ上位3つを取得 (ApiAdvController側で3つに絞り込み済み)
            const advertisement = await get_advertisement(5, "banner");   
            if (advertisement && advertisement.length > 0) {
                const bannerAdvlInner = document.getElementById('banner-adv-items');
                bannerAdvlInner.innerHTML = ''; // 既存のスライドをクリア

                advertisement.forEach((ad, index) => {
                    const isActive = index === 0 ? 'active' : ''; // 最初のスライドをアクティブに
                    
                    // ポップアップ同様、ad.name や ad.price も取得できている前提の横長デザイン
                    const item = `
                        <div class="carousel-item ${isActive}" data-bs-interval="5000">
                            <a href="${ad.href}" target="_blank" rel="nofollow" onclick="adv_action(${ad.id}, 'select')" class="text-decoration-none text-dark">
                                <div class="d-flex align-items-center bg-white border border-light rounded shadow-sm p-2" style="height: 110px; overflow: hidden;">
                                    
                                    <div class="flex-shrink-0 bg-light rounded" style="width: 90px; height: 90px;">
                                        <img src="${ad.src}" class="w-100 h-100" style="object-fit: contain;">
                                    </div>
                                    
                                    <div class="flex-grow-1 ms-3 overflow-hidden text-start">
                                        <p class="fw-bold mb-1" style="font-size: 0.8rem; line-height: 1.3; height: 2.6em; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-all;">
                                            ${ad.name || '注目のおすすめ商品'}
                                        </p>
                                        
                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                            <p class="text-danger fw-bold mb-0" style="font-size: 0.9rem;">
                                                ${ad.price ? Number(ad.price).toLocaleString() + '円' : ''}
                                            </p>
                                            <span class="badge bg-danger pt-1 pb-1 px-2" style="font-size: 0.65rem; font-weight: 500;">
                                                楽天市場で見る
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </a>
                        </div>
                    `;
                    bannerAdvlInner.insertAdjacentHTML('beforeend', item);
                });
            } else {
                console.log('adv_nothing');
            }
        } catch (error) {
            console.error('広告の取得中にエラーが発生しました:', error);
        }
    }
    // ページ読み込み時に広告を表示
    document.addEventListener('DOMContentLoaded', loadAdvertisements);
</script>

