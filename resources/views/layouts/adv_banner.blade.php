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
            const advertisement = await get_advertisement(3, "banner");   
            if (advertisement && advertisement.length > 0) {
                const bannerAdvlInner = document.getElementById('banner-adv-items');
                bannerAdvlInner.innerHTML = ''; // 既存のスライドをクリア

                advertisement.forEach((ad, index) => {
                    const isActive = index === 0 ? 'active' : ''; // 最初のスライドをアクティブに
                    const item = `
                        <div class="carousel-item ${isActive}" data-bs-interval="5000">
                            <a href="${ad.href}" target="_blank" rel="nofollow" onclick="adv_action(${ad.id}, 'select')">
                                <img src="${ad.src}" class="d-block w-100" style="max-height: 200px; object-fit: contain; background: #f8f9fa;">
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

