{{-- 🎮 ゲーム作品 新規登録 ＆ 更新 統合フォーム（右側メイン） --}}
<form id="game_master_form" method="POST" action="{{ route('admin.game.update') }}">
    @csrf
    {{-- 💡 IDが空なら「新規登録」、値が入れば「既存編集」とコントローラーが自動判定します --}}
    <input type="hidden" id="game_id" name="id" value="">

    <div class="card mb-4 shadow-sm border-success animate__animated animate__fadeIn" id="form-card-wrapper">
        <div class="card-header bg-success text-white py-2 small fw-bold d-flex justify-content-between align-items-center" id="form-header">
            <span><i class="bi bi-plus-circle-fill me-1"></i> ➕ 新規ゲーム作品を追加登録</span>
            <button type="button" class="btn btn-xs btn-outline-light d-none" id="btn-reset-mode" onclick="setCreateMode()">新規登録モードに戻る</button>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3 align-items-end">
                
                <div class="col-2 col-md-2">
                    <label class="form-label small fw-bold text-danger">🔑 識別キー (game_key)</label>
                    <input type="text" id="game_key" name="game_key" class="form-control form-control-sm font-monospace border-danger fw-bold" 
                           placeholder="例: twin_facer など" required>
                    <div class="text-muted" style="font-size: 10px; margin-top: 2px;">※半角英数字</div>
                </div>

                <div class="col-3 col-md-3">
                    <label class="form-label small fw-bold">🎮 ゲームタイトル</label>
                    <input type="text" id="game_title" name="title" class="form-control form-control-sm border-secondary fw-bold text-dark" placeholder="タイトル" required>
                </div>

                <div class="col-2 col-md-2">
                    <label class="form-label small fw-bold">📝 ゲーム説明文</label>
                    <input type="text" id="game_desc" name="description" class="form-control form-control-sm border-secondary text-dark" placeholder="説明文（省略可）">
                </div>

                <div class="col-3 col-md-1">
                    <label class="form-label small fw-bold">🔄 Ver</label>
                    <input type="number" id="game_ver" name="version" class="form-control form-control-sm border-secondary text-center fw-bold text-primary" value="1" min="1" required>
                </div>
                
                <div class="col-2 col-md-2">
                    <label class="form-label small fw-bold text-primary">👁️ 視点モード (view_mode)</label>
                    <select id="game_view_mode" name="view_mode" class="form-select form-select-sm border-primary fw-bold text-primary" required>
                        <option value="side_view_flip">side_view_flip (横スクロール：左右共通)</option>
                        <option value="side_view_separate">side_view_separate (横スクロール：左右別定義)</option>
                        <option value="top_down">top_down (見下ろしRPG)</option>
                        <option value="fixed_screen">fixed_screen (1画面固定)</option>
                    </select>
                </div>

                <div class="col-2 col-md-2">
                    <label class="form-label small fw-bold text-info">📱 画面の向き</label>
                    <select id="game_orientation" name="orientation" class="form-select form-select-sm border-info fw-bold text-info" required>
                        <option value="landscape">landscape (横画面)</option>
                        <option value="portrait">portrait (縦画面)</option>
                    </select>
                </div>

                <div class="col-6 col-md-6 d-flex justify-content-around pb-1 px-3 border rounded bg-white" style="height: 31px; align-items: center;">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input text-success" type="checkbox" name="enable_flag" value="1" id="switch-enable">
                        <label class="form-check-label small fw-bold text-success" for="switch-enable">公開中</label>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="login_user_flag" value="1" id="switch-login">
                        <label class="form-check-label small fw-bold text-secondary" for="switch-login">ログイン必須</label>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="admin_only_flag" value="1" id="switch-admin">
                        <label class="form-check-label small fw-bold text-warning" for="switch-admin">管理者限定</label>
                    </div>
                </div>

                <div class="col-4 col-md-4">
                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold shadow-sm" style="height: 31px;" id="form-submit-btn">
                        <i class="bi bi-plus-lg me-1"></i>新規登録
                    </button>
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

{{-- ゲーム作品一覧テーブル --}}
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-collection-play me-1"></i> 登録済みのゲームプラットフォーム作品一覧</h6>
        <span class="badge bg-secondary font-monospace">game_list</span>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x: auto;">
            <table class="table table-striped table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">ID</th>
                        <th>識別キー (game_key)</th>
                        <th>ゲームタイトル</th>
                        <th style="width: 140px;">視点モード</th>
                        <th style="width: 100px;">向き</th>
                        <th>ゲーム説明文</th>
                        <th class="text-center">バージョン</th>
                        <th class="text-center">全体公開状態</th>
                        <th style="width: 90px;" class="text-center">基本設定</th>
                        <th style="width: 80px;" class="text-center">削除</th> </tr>
                </thead>
                <tbody>
                    @forelse($games as $g)
                        <tr>
                            <td class="text-center font-monospace text-secondary">{{ $g->id }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $g->game_key }}</span></td>
                            <td class="fw-bold text-dark fs-6">{{ $g->title }}</td>
                            
                            {{-- 🌟 1. 視点モードを判別するきれいな色分けバッジを正しく追加 --}}
                            <td>
                                @if($g->view_mode === 'side_view_flip')
                                    <span class="badge bg-info text-dark fw-bold"><i class="bi bi-arrows-exchange me-1"></i> 横スクロール (反転共通)</span>
                                @elseif($g->view_mode === 'side_view_separate')
                                    <span class="badge bg-cyan text-dark fw-bold"><i class="bi bi-arrows-expand-vertical me-1"></i> 横スクロール (左右別定義)</span>
                                @elseif($g->view_mode === 'top_down')
                                    <span class="badge bg-warning text-dark fw-bold"><i class="bi bi-grid-3x3-gap me-1"></i> 見下ろしRPG</span>
                                @else
                                    <span class="badge bg-secondary text-white fw-bold"><i class="bi bi-aspect-ratio me-1"></i> 1画面固定</span>
                                @endif
                            </td>

                            <td>
                                @if($g->orientation === 'landscape')
                                    <span class="badge bg-light text-dark border"><i class="bi bi-pc-display me-1"></i> 横画面</span>
                                @else
                                    <span class="badge bg-light text-dark border"><i class="bi bi-phone me-1"></i> 縦画面</span>
                                @endif
                            </td>

                            <td class="text-muted">{{ $g->description ?? '---' }}</td>
                            <td class="text-center font-monospace fw-bold text-primary">v{{ $g->version }}</td>
                            <td class="text-center">
                                @if($g->enable_flag) <span class="badge bg-success"><i class="bi bi-check-circle"></i> 稼働中</span>
                                @else <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> メンテ中</span> @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold edit-btn"
                                        data-id="{{ $g->id }}"
                                        data-key="{{ $g->game_key }}"
                                        data-title="{{ $g->title }}"
                                        data-description="{{ $g->description }}"
                                        data-version="{{ $g->version }}"
                                        data-view_mode="{{ $g->view_mode }}" {{-- 🌟 2. 編集時にフォームに同期させるためのデータを追加 --}}
                                        data-orientation="{{ $g->orientation }}"
                                        data-enable_flag="{{ $g->enable_flag }}"
                                        data-login_user_flag="{{ $g->login_user_flag }}"
                                        data-admin_only_flag="{{ $g->admin_only_flag }}">
                                    <i class="bi bi-pencil"></i> 編集
                                </button>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('admin.game.destroy') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $g->id }}">
                                    <button type="submit" class="btn btn-xs btn-danger fw-bold text-white shadow-sm" 
                                            onclick="return confirm('⚠️警告⚠️\nこのゲーム作品を削除すると、関連するすべての【キャラクター、ステージ、武器、アイテムデータ】もDBから連鎖して完全に消失します！\n本当に削除してもよろしいですか？');">
                                        <i class="bi bi-trash3-fill"></i> 削除
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">ゲーム作品が見つかりません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('game_master_form');
    const formWrapper = document.getElementById('form-card-wrapper');
    const formHeader = document.getElementById('form-header');
    const resetBtn = document.getElementById('btn-reset-mode');
    const submitBtn = document.getElementById('form-submit-btn');

    // 🌟 編集ボタンが押された時の処理
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            // 1. data- 属性からゲームのステータスデータを安全に回収
            const id = this.dataset.id;
            const key = this.dataset.key;
            const title = this.dataset.title;
            const description = this.dataset.description;
            const version = this.dataset.version;
            const viewMode = this.dataset.view_mode; // 🌟データ取得を追加
            const orientation = this.dataset.orientation;
            const enableFlag = this.dataset.enable_flag === '1';
            const loginUserFlag = this.dataset.login_user_flag === '1';
            const adminOnlyFlag = this.dataset.admin_only_flag === '1';

            // 2. フォームの各入力欄にゲームデータを流し込む
            form.querySelector('#game_id').value = id;
            form.querySelector('#game_key').value = key;
            form.querySelector('#game_title').value = title;
            form.querySelector('#game_desc').value = description;
            form.querySelector('#game_ver').value = version;
            form.querySelector('#game_view_mode').value = viewMode;
            form.querySelector('#game_orientation').value = orientation;
            
            // トグルスイッチの状態も同期
            form.querySelector('#switch-enable').checked = enableFlag;
            form.querySelector('#switch-login').checked = loginUserFlag;
            form.querySelector('#switch-admin').checked = adminOnlyFlag;

            // 3. フォームの見た目を「編集モード（青ベース）」に切り替え
            formWrapper.classList.remove('border-success');
            formWrapper.classList.add('border-primary');
            
            formHeader.classList.remove('bg-success');
            formHeader.classList.add('bg-primary');
            formHeader.querySelector('span').innerHTML = '<i class="bi bi-pencil-square me-1"></i> 🔧 既存ゲーム作品の基本設定を編集';
            
            // 「新規登録モードに戻る」ボタンを表示
            resetBtn.classList.remove('d-none');
            
            // 送信ボタンを「更新」に変更
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-primary');
            submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>更新する';

            // 4. 画面をスムーズに上部へスクロール
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // 🌟 新規登録モードにリセットする関数（HTML側のonclick="setCreateMode()"に対応）
    window.setCreateMode = function() {
        // フォームをリセット（IDを空にするのが最重要）
        form.reset();
        form.querySelector('#game_id').value = '';
        form.querySelector('#game_ver').value = '1'; // バージョンの初期値
        form.querySelector('#game_view_mode').value = 'side_view_flip';
        form.querySelector('#game_orientation').value = 'landscape';

        // フォームの見た目を「新規登録モード（緑ベース）」に戻す
        formWrapper.classList.remove('border-primary');
        formWrapper.classList.add('border-success');
        
        formHeader.classList.remove('bg-primary');
        formHeader.classList.add('bg-success');
        formHeader.querySelector('span').innerHTML = '<i class="bi bi-plus-circle-fill me-1"></i> ➕ 新規ゲーム作品を追加登録';
        
        // 「新規登録モードに戻る」ボタンを隠す
        resetBtn.classList.add('d-none');
        
        // 送信ボタンを「新規登録」に戻す
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-success');
        submitBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>新規登録';
    };
});
</script>