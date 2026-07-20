{{-- 👥 キャラクター 新規登録 ＆ 更新 統合フォーム（右側メイン） --}}
<form id="character_change_form" method="POST" action="{{ route('admin.game.character.update') }}">
    @csrf
    <input type="hidden" name="game_key" value="{{ $gameKey }}">
    {{-- 💡 IDが空なら「新規登録」、値が入れば「既存編集」とコントローラーが自動判定します --}}
    <input type="hidden" id="char_id" name="id" value="">

    <div class="card mb-4 shadow-sm border-success animate__animated animate__fadeIn" id="edit-card-wrapper">
        <div class="card-header bg-success text-white py-2 small fw-bold d-flex justify-content-between align-items-center" id="edit-card-header"
             data-bs-toggle="collapse" 
             data-bs-target="#characterFormCollapse" 
             role="button" 
             style="user-select: none; cursor: pointer;">
            <span><i class="bi bi-plus-circle-fill me-1"></i> ➕ 新規キャラクターを追加登録</span>
            <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation();">
                <button type="button" class="btn btn-xs btn-outline-light d-none" id="btn-reset-char-mode" onclick="setCharCreateMode()">新規登録モードに戻る</button>
                <span class="small font-monospace text-white-50">クリックして開閉 <i class="bi bi-chevron-expand ms-1"></i></span>
            </div>
        </div>
        
        <div id="characterFormCollapse" class="collapse">
            <div class="card-body bg-light">
                <div class="row g-3 align-items-end">
                    
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-danger">🔑 識別キー (character_key)</label>
                        <input type="text" id="char_key" name="character_key" class="form-control form-control-sm font-monospace border-danger fw-bold" 
                               placeholder="例: player_hero などの英数字" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold">👥 キャラクター表示名</label>
                        <input type="text" id="char_name" name="name" class="form-control form-control-sm border-secondary fw-bold text-dark" placeholder="例: 勇者アルス" required>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-bold">🎭 タイプ</label>
                        <select id="char_type" name="type" class="form-select form-select-sm border-secondary">
                            <option value="player">プレイヤー</option>
                            <option value="enemy">敵キャラ</option>
                            <option value="npc">NPC</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-1">
                        <label class="form-label small fw-bold">🔢 順序</label>
                        <input type="number" id="char_sort" name="sort_order" class="form-control form-control-sm border-secondary text-center" value="0" min="0">
                    </div>

                    <div class="col-8 col-md-8 d-flex justify-content-around pb-1 px-3 border rounded bg-white" style="height: 31px; align-items: center;">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input text-success" type="checkbox" name="enable_flag" value="1" id="switch-enable">
                            <label class="form-check-label small fw-bold text-success" for="switch-enable">公開</label>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="login_user_flag" value="1" id="switch-login">
                            <label class="form-check-label small fw-bold text-secondary" for="switch-login">会員限</label>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="admin_only_flag" value="1" id="switch-admin">
                            <label class="form-check-label small fw-bold text-warning" for="switch-admin">開発限</label>
                        </div>
                    </div>

                    <div class="col-4 col-md-4 flex-grow-1">
                        <button type="submit" class="btn btn-sm btn-success w-100 fw-bold shadow-sm" style="height: 31px;" id="char-submit-btn">
                            <i class="bi bi-plus-lg me-1"></i>新規登録
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- メッセージ表示 --}}
@if(session('msg'))
    <div class="alert alert-success shadow-sm py-2 small">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('msg') }}
    </div>
@endif

{{-- キャラクター一覧 --}}
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-table me-1"></i> 公開マスターデータ目録 [{{ $game->title ?? '未登録' }}]</h6>
        <span class="badge bg-secondary font-monospace">game_characters</span>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x: auto;">
            <table class="table table-striped table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">ID</th>
                        <th>識別キー (character_key)</th>
                        <th>ゲーム内表示名</th>
                        <th>タイプ</th>
                        <th class="text-center">並び順</th>
                        <th class="text-center">ステータスフラグ</th>
                        <th style="width: 90px;" class="text-center">プロフィール</th>
                        <th style="width: 130px;" class="text-center">グラフィック</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($characters as $char)
                        <tr>
                            <td class="text-center font-monospace text-secondary">{{ $char->id }}</td>
                            <td><code>{{ $char->character_key }}</code></td>
                            <td class="fw-bold text-dark">{{ $char->name }}</td>
                            <td>
                                @if($char->type === 'player') <span class="badge bg-primary">プレイヤー</span>
                                @elseif($char->type === 'enemy') <span class="badge bg-danger">敵モンスター</span>
                                @else <span class="badge bg-info text-dark">住民・NPC</span> @endif
                            </td>
                            <td class="text-center font-monospace">{{ $char->sort_order }}</td>
                            <td class="text-center">
                                @if($char->enable_flag) <span class="badge bg-success"><i class="bi bi-rocket-takeoff"></i> 公開中</span>
                                @else <span class="badge bg-secondary">調整中(非公開)</span> @endif
                                @if($char->login_user_flag) <span class="badge bg-lock" style="background:#6f42c1;">会員限定</span> @endif
                                @if($char->admin_only_flag) <span class="badge bg-warning text-dark">開発テスト</span> @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold edit-btn"
                                        data-id="{{ $char->id }}"
                                        data-character_key="{{ $char->character_key }}"
                                        data-name="{{ $char->name }}"
                                        data-type="{{ $char->type }}"
                                        data-sort_order="{{ $char->sort_order }}"
                                        data-enable_flag="{{ $char->enable_flag }}"
                                        data-login_user_flag="{{ $char->login_user_flag }}"
                                        data-admin_only_flag="{{ $char->admin_only_flag }}">
                                    <i class="bi bi-pencil"></i> 編集
                                </button>
                            </td>
                            <td class="text-center">
                                {{-- 🌟【修正】ルートの指定先を sprite_sheet から asset へ変更しました --}}
                                <a href="{{ route('admin.game.asset.index', ['character_id' => $char->id, 'game_key' => $gameKey, 'mode' => 'motion']) }}" 
                                   class="btn btn-xs btn-warning font-monospace fw-bold text-dark shadow-sm py-1 px-2 d-inline-block">
                                    <i class="bi bi-play-circle-fill me-1"></i> モーションを開く
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                まだキャラクターが登録されていません。<br>
                                <span class="text-success fw-bold">上の「新規キャラクターを追加登録」フォームから作成してください。</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('character_change_form');
    const formWrapper = document.getElementById('edit-card-wrapper');
    const formHeader = document.getElementById('edit-card-header');
    const resetBtn = document.getElementById('btn-reset-char-mode');
    const submitBtn = document.getElementById('char-submit-btn');
    const formCollapseEl = document.getElementById('characterFormCollapse');

    // 既存の編集ボタンの処理
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const key = this.dataset.character_key;
            const name = this.dataset.name;
            const type = this.dataset.type;
            const sortOrder = this.dataset.sort_order;
            const enableFlag = this.dataset.enable_flag === '1';
            const loginUserFlag = this.dataset.login_user_flag === '1';
            const adminOnlyFlag = this.dataset.admin_only_flag === '1';

            form.querySelector('#char_id').value = id;
            form.querySelector('#char_key').value = key;
            form.querySelector('#char_name').value = name;
            form.querySelector('#char_type').value = type;
            form.querySelector('#char_sort').value = sortOrder;
            
            form.querySelector('#switch-enable').checked = enableFlag;
            form.querySelector('#switch-login').checked = loginUserFlag;
            form.querySelector('#switch-admin').checked = adminOnlyFlag;

            formWrapper.classList.remove('border-success');
            formWrapper.classList.add('border-primary');
            formHeader.classList.remove('bg-success');
            formHeader.classList.add('bg-primary');
            formHeader.querySelector('span').innerHTML = '<i class="bi bi-pencil-square me-1"></i> 🔧 既存キャラクターの基本プロフィールを編集';
            
            resetBtn.classList.remove('d-none');
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-primary');
            submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>更新する';

            if (formCollapseEl && typeof bootstrap !== 'undefined') {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(formCollapseEl);
                bsCollapse.show();
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    window.setCharCreateMode = function() {
        form.reset();
        form.querySelector('#char_id').value = '';
        form.querySelector('#char_sort').value = '0';

        formWrapper.classList.remove('border-primary');
        formWrapper.classList.add('border-success');
        formHeader.classList.remove('bg-primary');
        formHeader.classList.add('bg-success');
        formHeader.querySelector('span').innerHTML = '<i class="bi bi-plus-circle-fill me-1"></i> ➕ 新規キャラクターを追加登録';
        
        resetBtn.classList.add('d-none');
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-success');
        submitBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>新規登録';
    };
});
</script>