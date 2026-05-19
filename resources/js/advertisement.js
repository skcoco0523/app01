window.get_advertisement = function get_advertisement(disp_cnt, type) {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "get",
            url: getAdvertisementUrl,
            data: { disp_cnt: disp_cnt, search_type: type },
        })
        .done(data => {
            if (data && data.length > 0) {
                resolve(data);
            } else {
                resolve([]);
            }
        })
        .fail((xhr, status, error) => {
            console.error('Error fetching advertisement:', error);
            reject(error);
        });
    });
};

/**
 * 広告アクション送信
 * @param {number} categoryId カテゴリID
 * @param {string} type select / detail_view / dislike
 * @param {number} seconds 表示秒数
 */
window.adv_action = function adv_action(categoryId, type, seconds = 0) {
    return $.ajax({
        type: "post",
        url: AdvertisementClickUrl,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: { 
            adv_category_id: categoryId,
            type: type,
            display_seconds: seconds
        },
    });
};

// 互換性のための古い関数名も維持（中身は新しいアクション送信を使用）
window.adv_click = function adv_click(categoryId) {
    return window.adv_action(categoryId, 'select');
};
