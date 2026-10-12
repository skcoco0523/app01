<!-- 公開URL（パブリックリンク）発行・管理モーダル -->
<div id="life_theater_public_link-modal" class="notification-overlay" onclick="closeModal('life_theater_public_link-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content" style="max-height: 85vh; overflow: hidden;">
            <div class="modal-header">
                <input type="hidden" id="public_link_theater_id" value="">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-share-nodes me-2 text-primary"></i>作品の公開・URL発行
                </h5>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_public_link-modal')"></button>
            </div>

            <div class="modal-body text-start" style="max-height: 62vh; overflow-y: auto;">
                <p class="small text-muted mb-3">
                    この作品を外部の人（未ログインユーザー）にも共有できる専用URLを発行します。
                </p>

                {{-- ▼ 新規発行エリア --}}
                <div class="p-3 bg-light border rounded mb-4">
                    <h6 class="fw-bold mb-2">新しい公開URLを発行する</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small mb-1">用途メモ（任意）</label>
                        <input type="text" class="form-control form-control-sm" id="public_link_memo" placeholder="例: LINE送付用、SNS公開用" maxlength="50">
                    </div>

                    {{-- ★ 追加: 合言葉（パスワード）入力欄 --}}
                    <div class="mb-3">
                        <label class="form-label small mb-1">合言葉（任意）</label>
                        <input type="text" class="form-control form-control-sm" id="public_link_password" placeholder="未入力なら誰でも閲覧可能" maxlength="20">
                        <div class="form-text" style="font-size: 10px;">
                            ※合言葉を設定すると、URLを開いた際に合言葉の入力が必須になります。
                        </div>
                    </div>

                    {{-- ★ JavaScriptで動的にボタン表示を更新するエリア --}}
                    <div class="d-flex flex-column gap-2 mt-3" id="public_link_action_area">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 public-link-btn" onclick="createPublicLink(false)">
                            <i class="fa-regular fa-clone me-1"></i> 無料（広告あり）で発行する
                        </button>
                    </div>
                </div>

                {{-- ▼ 発行済みURL一覧エリア --}}
                <h6 class="fw-bold mb-2">発行済みのURL</h6>
                <div class="d-flex flex-column gap-2" id="public_link_list_area">
                    {{-- JavaScriptで動的に生成 --}}
                </div>

            </div>

            <div class="modal-footer row gap-3 justify-content-center m-0 py-2">
                <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_public_link-modal')">閉じる</button>
            </div>
        </div>
    </div>
</div>

<script>
// 現在の単価（確認ダイアログ用）
let currentPublicUrlCost = {{ $po_life_theater_url_cost ?? 300 }};
// XSS対策用のHTMLエスケープ関数
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('life_theater_public_link-modal');
    if (modal) {
        modal.addEventListener('modal:open', async function () {
            document.getElementById('public_link_memo').value = '';
            document.getElementById('public_link_password').value = ''; // ★ 初期化
            await refreshPublicLinkList();
        });
    }
});

// URL一覧および発行ボタンの非同期更新
async function refreshPublicLinkList() {
    const lifeTheaterId = document.getElementById('public_link_theater_id').value;
    const listArea = document.getElementById('public_link_list_area');

    if (!lifeTheaterId) return;

    try {
        const res = await $.ajax({
            type: "GET",
            url: "{{ route('api.life_theater.public_link.list') }}",
            data: { life_theater_id: lifeTheaterId }
        });

        listArea.innerHTML = '';

        if (res.status === 'success') {
            // 1. 一覧リストの描画
            if (res.data && res.data.length > 0) {
                res.data.forEach(link => {
                    const badge = link.is_ad_free 
                        ? '<span class="badge bg-warning text-dark px-1 py-0" style="font-size: 10px;"><i class="fa-solid fa-crown"></i> 広告なし</span>'
                        : '<span class="badge bg-secondary px-1 py-0" style="font-size: 10px;">広告あり</span>';

                    // 合言葉の取得とエスケープ（XSS対策）
                    let passcodeRaw = (link.passcode || link.password || '').toString().trim();
                    const passcodeText = escapeHtml(passcodeRaw); // エスケープ処理
                    
                    const lockBadge = passcodeText !== '' 
                        ? `<span class="badge bg-danger text-white px-1 py-0 ms-1" style="font-size: 10px;" title="合言葉あり"><i class="fa-solid fa-key me-1"></i>${passcodeText}</span>`
                        : '';

                    const viewCount = link.view_count || 0;
                    const viewBadge = `<span class="badge bg-light text-secondary border px-1 py-0 ms-1" style="font-size: 10px;"><i class="fa-solid fa-eye me-1"></i>${viewCount}回再生</span>`;

                    // メモのエスケープ（XSS対策）
                    const memo = escapeHtml(link.memo ? link.memo : '');

                    const card = document.createElement('div');
                    card.className = 'card border-0 shadow-sm';
                    card.innerHTML = `
                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                            <div class="overflow-hidden" style="flex: 1;">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    ${badge}
                                    ${lockBadge}
                                    ${viewBadge}
                                    <small class="fw-bold text-truncate ms-1">${memo}</small>
                                </div>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control bg-white text-muted" id="link_url_${link.id}" value="${link.public_url}" readonly style="font-size: 11px;">
                                    <button class="btn btn-outline-secondary copy-btn" type="button" onclick="copyPublicUrl('link_url_${link.id}', this)" title="URLをコピー">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="ms-2 ps-2 border-start">
                                <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="confirmDestroyPublicLink(${link.id})" title="公開停止">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    listArea.appendChild(card);
                });
            } else {
                listArea.innerHTML = '<p class="text-muted small text-center my-3">発行済みの公開URLはありません。</p>';
            }

            // 2. ボタン表示のリアルタイム更新
            if (res.meta) {
                currentPublicUrlCost = res.meta.url_cost || 300;
                renderActionButtons(res.meta);
            }
        } else {
            listArea.innerHTML = '<p class="text-danger small text-center my-3">取得に失敗しました。</p>';
        }
    } catch (err) {
        console.error(err);
        listArea.innerHTML = '<p class="text-danger small text-center my-3">取得に失敗しました。</p>';
    }
}

// ボタン表示エリアのリアルタイム生成
function renderActionButtons(meta) {
    const actionArea = document.getElementById('public_link_action_area');
    if (!actionArea) return;

    let html = `
        <button type="button" class="btn btn-outline-primary btn-sm w-100 public-link-btn" onclick="createPublicLink(false)">
            <i class="fa-regular fa-clone me-1"></i> 無料（広告あり）で発行する
        </button>
    `;

    const freeLimit = meta.free_limit || 1;
    const freeUsedCount = Math.min(meta.used_ad_free_count || 0, freeLimit);

    if (meta.has_free_ticket) {
        html += `
            <button type="button" class="btn btn-warning btn-sm w-100 fw-bold public-link-btn" onclick="createPublicLink(true)">
                <i class="fa-solid fa-crown me-1"></i> 広告なしURLを発行する (プレミアム無料特典)
            </button>
            <small class="text-muted text-center" style="font-size: 10px;">
                ※プレミアム特典（無料枠: ${freeUsedCount}/${freeLimit}件使用中）
            </small>
        `;
    } else {
        html += `
            <button type="button" class="btn btn-warning btn-sm w-100 fw-bold public-link-btn" onclick="confirmAdFreePublicLink()">
                <i class="fa-solid fa-crown me-1"></i> ${meta.url_cost}pt で広告なしURLを発行
            </button>
        `;

        if (meta.is_premium) {
            html += `
                <small class="text-muted text-center" style="font-size: 10px;">
                    ※プレミアム特典（無料枠: ${freeUsedCount}/${freeLimit}件使用中）
                </small>
            `;
        }
    }

    actionArea.innerHTML = html;
}

function confirmAdFreePublicLink() {
    openModal('common-modal', {
        title: 'ポイント消費の確認',
        mess: currentPublicUrlCost + 'ポイントを消費して、広告なしのURLを発行しますか？',
        cancel_btn: 'キャンセル',
        confirm_btn: '発行する',
        user_chk: false,
        onConfirm: function() {
            createPublicLink(true);
        }
    });
}

// 新規発行API呼び出し
async function createPublicLink(isAdFree) {
    const lifeTheaterId = document.getElementById('public_link_theater_id').value;
    const memo          = document.getElementById('public_link_memo').value;
    const password      = document.getElementById('public_link_password').value; // ★ 追加

    try {
        const res = await $.ajax({
            type: "POST",
            url: "{{ route('api.life_theater.public_link.store') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                life_theater_id: lifeTheaterId,
                memo: memo,
                password: password, // ★ 追加
                request_ad_free: isAdFree
            }
        });

        if (res.status === 'success') {
            document.getElementById('public_link_memo').value = '';
            document.getElementById('public_link_password').value = ''; // ★ 初期化
            if (typeof showNotification === 'function') {
                showNotification(res.message || "公開URLを発行しました。", "success", 2000);
            }
            await refreshPublicLinkList();
        } else {
            if (typeof showNotification === 'function') {
                showNotification(res.message || "発行に失敗しました。", "error", 2500);
            }
        }
    } catch (err) {
        console.error(err);
        const errMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : '発行処理中にエラーが発生しました。';
        if (typeof showNotification === 'function') {
            showNotification(errMsg, "error", 2500);
        }
    }
}

function confirmDestroyPublicLink(linkId) {
    openModal('common-modal', {
        title: '公開URLの停止',
        mess: 'この公開URLを停止（削除）しますか？\n※すでにURLを知っている人も見られなくなります。',
        cancel_btn: 'キャンセル',
        confirm_btn: '削除',
        user_chk: false,
        onConfirm: function() {
            destroyPublicLink(linkId);
        }
    });
}

async function destroyPublicLink(linkId) {
    try {
        const res = await $.ajax({
            type: "POST",
            url: "{{ route('api.life_theater.public_link.destroy') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { link_id: linkId }
        });

        if (res.status === 'success') {
            if (typeof showNotification === 'function') {
                showNotification(res.message || "公開URLを削除しました。", "success", 2000);
            }
            await refreshPublicLinkList();
        } else {
            if (typeof showNotification === 'function') {
                showNotification(res.message || "削除に失敗しました。", "error", 2500);
            }
        }
    } catch (err) {
        console.error(err);
        if (typeof showNotification === 'function') {
            showNotification("削除処理中にエラーが発生しました。", "error", 2500);
        }
    }
}

function copyPublicUrl(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input && input.value) {
        navigator.clipboard.writeText(input.value).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-success"></i>';
            if (typeof showNotification === 'function') {
                showNotification("URLをコピーしました", "success", 1500);
            }
            setTimeout(() => { btn.innerHTML = originalHtml; }, 1500);
        }).catch(() => {
            if (typeof showNotification === 'function') {
                showNotification("コピーに失敗しました", "error", 2000);
            }
        });
    }
}
</script>