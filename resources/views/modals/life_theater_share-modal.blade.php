<div id="life_theater_share-modal" class="notification-overlay" onclick="closeModal('life_theater_share-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div class="modal-header mx-auto w-100 overflow-hidden">
                <input type="hidden" id="life_theater_id" value="">
                <h5 class="modal-title text-ellipsis" id="life_theater_title"></h5>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_share-modal')"></button>
            </div>
            <div class="modal-body">

                {{-- 共有リスト --}}
                <div class="mb-3">
                    <table class="table table-borderless table-center" style="table-layout: fixed;">
                        <colgroup>
                            <col style="width: 60%">
                            <col style="width: 40%">
                        </colgroup>
                        <tbody id="share_theater_friend_list_area">
                            {{-- 共有リストをここに動的に追加 --}}
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer row gap-3 justify-content-center">
                <button type="button" id="cancel_btn" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_share-modal')">キャンセル</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var lifeTheaterId               = document.getElementById('life_theater_id');
        var lifeTheaterTitle            = document.getElementById('life_theater_title');
        var shareFriendListArea         = document.getElementById('share_theater_friend_list_area');

        var get_share_theater_list_flag = false;
        const modal                     = document.getElementById('life_theater_share-modal');

        // モーダル表示時
        modal.addEventListener('modal:open', async function () {
            if(get_share_theater_list_flag) return; 
            if(lifeTheaterId.value == '')   return; 
            await refreshShareLifeTheaterFriendList();
        });

        // モーダル非表示時
        modal.addEventListener('modal:close', () => {});
    });

    // フレンドリストエリアの更新
    async function refreshShareLifeTheaterFriendList() {
        const lifeTheaterId = document.getElementById('life_theater_id').value;
        const shareFriendListArea = document.getElementById('share_theater_friend_list_area');

        try {
            const theaterShareFriends = await get_life_theater_friend_share_status(lifeTheaterId);

            shareFriendListArea.innerHTML = '';

            if (theaterShareFriends && theaterShareFriends.length > 0) {
                theaterShareFriends.forEach((friend) => {
                    const isShared = friend.is_shared;
                    const friendId = friend.friend_id;
                    const shareId = friend.share_id || friend.life_theater_share_id;
                    const btnClass = isShared ? 'btn-danger' : 'btn-primary';
                    const btnText = isShared ? '解除' : '共有';
                    const shareAction = isShared ? 'unshare' : 'share';

                    let permissionToggle = '';
                    if (isShared) {
                        const adminFlag   = friend.admin_flag; 
                        const isChecked   = adminFlag ? 'checked' : '';
                        const toggleLabel = adminFlag ? '編集可' : '閲覧のみ';
                        const editAction  = adminFlag ? 'disable_edit' : 'enable_edit';
                        
                        const labelStyle = adminFlag 
                            ? 'font-size: 11px; font-weight: bold; color: #198754; line-height: 1;' 
                            : 'font-size: 10px; color: #6c757d; opacity: 0.8; line-height: 1;';

                        permissionToggle = `
                            <div class="d-inline-flex flex-column align-items-center justify-content-center align-self-center me-2" 
                                style="width: 55px; min-height: 38px; vertical-align: middle;">
                                <label class="cursor-pointer mb-1 text-nowrap" for="toggle_theater_${friendId}" style="${labelStyle}">
                                    ${toggleLabel}
                                </label>
                                <div class="form-check form-switch m-0 p-0 d-flex align-items-center" style="min-height: auto;">
                                    <input class="form-check-input cursor-pointer m-0" type="checkbox" role="switch" 
                                        id="toggle_theater_${friendId}" ${isChecked} onclick="changeLifeTheaterShare('${editAction}', ${friendId}, ${lifeTheaterId}, ${shareId})">
                                </div>
                            </div>
                        `;
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="text-start align-middle text-truncate" style="max-width: 0;">${friend.name}</td>
                        <td class="text-end">
                            <button type="button" 
                                class="btn btn-sm ${btnClass}" onclick="changeLifeTheaterShare('${shareAction}', ${friendId}, ${lifeTheaterId}, ${shareId})"> ${btnText}
                            </button>
                            ${permissionToggle}
                        </td>
                    `;
                    
                    shareFriendListArea.appendChild(tr);
                });
            } else {
                shareFriendListArea.innerHTML = '<tr><td colspan="2" class="text-center">フレンドがいません</td></tr>';
            }
        } catch (err) {
            console.error(err);
            alert('共有状況の更新に失敗しました。');
        }
    }

    // 共有者取得API
    async function get_life_theater_friend_share_status(life_theater_id) {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "get",
                url: typeof getFriendlistUrl !== 'undefined' ? getFriendlistUrl : '/api/friendlist/get',
                headers: {},
                data: { life_theater_share_status: 1, life_theater_id: life_theater_id },
            })
            .done(data => {
                if (data && data.length > 0) resolve(data);
                else resolve([]);
            })
            .fail((xhr, status, error) => {
                console.error('Error fetching share status:', error);
                reject(error);
            });
        });
    }

    // 共有状態切り替え
    async function changeLifeTheaterShare(action, friend_id, life_theater_id, share_id) {
        let apiUrl;
        if (action === 'share') {
            apiUrl = "{{ route('api.life_theater.share') }}";
        } else if (action === 'unshare') {
            apiUrl = "{{ route('api.life_theater.unshare') }}";
        } else if (action === 'enable_edit') {
            apiUrl = "{{ route('api.life_theater.enable_edit') }}";
        } else if (action === 'disable_edit') {
            apiUrl = "{{ route('api.life_theater.disable_edit') }}";
        }

        try {
            await $.ajax({
                type: "post",
                url: apiUrl,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { friend_id: friend_id, life_theater_id: life_theater_id, share_id: share_id },
            });

            await refreshShareLifeTheaterFriendList();
        } catch (err) {
            console.error('API Error:', err);
            alert('共有状態の変更に失敗しました。');
        }
    }
</script>