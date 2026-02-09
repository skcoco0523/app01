@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1 class="mb-4">メモ管理</h1>

        {{-- ================================================ --}}
        {{-- メモリストのアコーディオン --}}
        {{-- ================================================ --}}
        <div class="accordion" id="deviceListAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingDevices">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDevices" aria-expanded="true" aria-controls="collapseDevices">
                        <h3>個人メモ</h3>
                    </button>
                </h2>
                <div id="collapseDevices" class="accordion-collapse collapse show" aria-labelledby="headingDevices" data-bs-parent="#deviceListAccordion">
                    <div class="accordion-body">

                        {{-- 新規メモ登録行用のテーブル --}}
                        <table class="table table-borderless table-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%; min-width: 70px;">
                                <col style="width: 80%">
                            </colgroup>
                            <td class="icon-55 d-flex justify-content-center b-gray" onclick="openModal('add_note-modal');">
                                <i class="fa-solid fa-plus icon-25 red"></i>
                            </td>
                            <td onclick="openModal('add_note-modal');" style="vertical-align: middle;">
                                新規メモ
                            </td>
                        </table>

                        {{-- ナビ用の色選択タブ --}}
                        <div class="d-flex overflow-auto contents_box mb-3">
                            <ul class="nav nav-pills flex-nowrap" id="noteColorTabs">
                                {{-- 「すべて」を表示するボタン（任意） --}}
                                <li class="nav-item">
                                    <a class="nav-link nav-link-simple active border me-1 px-2 py-1" href="javascript:void(0)" style="background-color: transparent;"
                                    onclick="filterNotes('all', this, 'my')">all</a>
                                </li>
                                @foreach(config('common.note_colors') as $key => $color)
                                    <li class="nav-item">
                                        <a class="nav-link nav-link-simple border me-1 px-2 py-1" style="background-color: {{ $color['code'] }};" href="javascript:void(0)" onclick="filterNotes('{{ $key }}', this, 'my')">
                                            {{ $my_note_counts[$key] ?? 0 }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- 既存のメモリストのテーブル --}}
                        <table id="noteTable" class="table table-borderless table-data-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 85%">
                                <col style="width: 15%">
                            </colgroup>
                            @foreach ($my_note_list as $key => $detail)
                                <tr class="table-row note-item" data-color="{{ $detail->color_num }}">
                                    <td class="" style="background-color: {{ $detail->color_code }};" onclick="window.location='{{ route('note-show-detail') }}?id={{ $detail->id }}'">
                                        {{ $detail->title }}<br>
                                        <small class="text-muted">{{ $detail->content }}</small>
                                    </td>
                                    <td class="" style="background-color: {{ $detail->color_code }};" onclick="openModal('share_note-modal',{ note_id: '{{ $detail->id ?? '' }}', note_title: '{{ $detail->title ?? '' }}'});">
                                        <i class="fa-solid fa-users red"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================ --}}
        {{-- 共有メモのアコーディオン --}}
        {{-- ================================================ --}}
        <div class="accordion" id="remoteListAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRemotes">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRemotes" aria-expanded="true" aria-controls="collapseRemotes">
                        <h3>共有メモ</h3>
                    </button>
                </h2>
                <div id="collapseRemotes" class="accordion-collapse collapse show" aria-labelledby="headingRemotes" data-bs-parent="#remoteListAccordion">
                    <div class="accordion-body">

                        {{-- ナビ用の色選択タブ --}}
                        <div class="d-flex overflow-auto contents_box mb-3">
                            <ul class="nav nav-pills flex-nowrap" id="noteShareColorTabs">
                                {{-- 「すべて」を表示するボタン（任意） --}}
                                <li class="nav-item">
                                    <a class="nav-link nav-link-simple active border me-1 px-2 py-1" href="javascript:void(0)" style="background-color: transparent;"
                                    onclick="filterNotes('all', this, 'share')">all</a>
                                </li>
                                @foreach(config('common.note_colors') as $key => $color)
                                    <li class="nav-item">
                                        <a class="nav-link nav-link-simple border me-1 px-2 py-1" style="background-color: {{ $color['code'] }};" href="javascript:void(0)" onclick="filterNotes('{{ $key }}', this, 'share')">
                                            {{ $share_note_counts[$key] ?? 0 }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        
                        {{-- 既存のメモリストのテーブル --}}
                        <table id="noteShareTable" class="table table-borderless table-data-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 85%">
                                <col style="width: 15%">
                            </colgroup>
                            @foreach ($share_note_list as $key => $detail)
                                <tr class="table-row note-item" data-color="{{ $detail->color_num }}">
                                    <td class="" style="background-color: {{ $detail->color_code }};" onclick="window.location='{{ route('note-show-detail') }}?id={{ $detail->id }}&share_flag=1'">
                                        {{ $detail->title }}<br>
                                        <small class="text-muted">{{ $detail->content }}</small>
                                    </td>
                                    <td class="" style="background-color: {{ $detail->color_code }};"
                                        onclick="openModal('common-modal', {
                                            title: '所有者' ,mess:'{{ $detail->owner_name }}',
                                            user_chk: false
                                        });">
                                        <i class="fa-solid fa-user-gear red"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- メモ追加ポップアップモーダル -->
    @include('modals.add_note-modal')

    <!-- 共有メモポップアップモーダル -->
    @include('modals.share_note-modal')

    <?//広告モーダル?>   
    @include('layouts.adv_popup')
    

@endsection

<style>
    /* プレビュー時は未割当状態を変えない */
    .noset-signal {
         opacity: 1 !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {

    });
    function filterNotes(colorNum, element, type) {
        // タブの活性状態（activeクラス）の切り替え
        let selector = (type === 'my') ? '#noteColorTabs .nav-link' : '#noteShareColorTabs .nav-link';
        const tabs = document.querySelectorAll(selector);

        tabs.forEach(tab => tab.classList.remove('active'));
        element.classList.add('active');

        // リストの絞り込み
        selector = (type === 'my') ? '#noteTable .note-item' : '#noteShareTable .note-item';
        const rows = document.querySelectorAll(selector);
        
        rows.forEach(row => {
            const rowColor = row.getAttribute('data-color');
            if (colorNum === 'all' || rowColor === colorNum.toString()) {
                row.style.display = ''; // 表示
            } else {
                row.style.display = 'none'; // 非表示
            }
        });
    }
</script>