@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('home') }}'"></i>
    <div class="container py-4">
        <h1 class="mb-4">ライフシアター管理</h1>

        {{-- 個人作品のアコーディオン --}}
        <div class="accordion" id="theaterListAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTheaters">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTheaters" aria-expanded="true" aria-controls="collapseTheaters">
                        <h3>個人作品</h3>
                    </button>
                </h2>
                <div id="collapseTheaters" class="accordion-collapse collapse show" aria-labelledby="headingTheaters" data-bs-parent="#theaterListAccordion">
                    <div class="accordion-body">

                        {{-- 新規作品登録行 --}}
                        <table class="table table-borderless table-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%; min-width: 70px;">
                                <col style="width: 80%">
                            </colgroup>
                            <tr>
                                <td class="icon-55 d-flex justify-content-center b-gray" onclick="openModal('life_theater_add-modal');" style="cursor: pointer;">
                                    <i class="fa-solid fa-plus icon-25 red"></i>
                                </td>
                                <td onclick="openModal('life_theater_add-modal');" style="vertical-align: middle; cursor: pointer;">
                                    新規作品
                                </td>
                            </tr>
                        </table>

                        {{-- ナビ用の色選択タブ --}}
                        <div class="d-flex overflow-auto contents_box mb-3">
                            <ul class="nav nav-pills flex-nowrap" id="theaterColorTabs">
                                <li class="nav-item">
                                    <a class="nav-link nav-link-simple active border me-1 px-2 py-1" href="javascript:void(0)" style="background-color: transparent;"
                                    onclick="filterTheaters('all', this, 'my')">all</a>
                                </li>
                                @foreach(config('common.note_colors', []) as $key => $color)
                                    <li class="nav-item">
                                        <a class="nav-link nav-link-simple border me-1 px-2 py-1" style="background-color: {{ $color['code'] }};" href="javascript:void(0)" onclick="filterTheaters('{{ $key }}', this, 'my')">
                                            {{ $my_theater_counts[$key] ?? 0 }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- 個人作品リストテーブル --}}
                        <table id="theaterTable" class="table table-borderless table-data-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 85%">
                                <col style="width: 15%">
                            </colgroup>
                            @foreach ($my_theater_list as $key => $detail)
                                @php
                                    $theaterId = $detail->id ?? $detail->life_theater_id;
                                    $colorCode = config('common.note_colors.'.$detail->theme_color_num.'.code', '#ffffff');
                                @endphp
                                <tr class="table-row theater-item" data-color="{{ $detail->theme_color_num }}">
                                    <td style="background-color: {{ $colorCode }}; cursor: pointer;" onclick="window.location='{{ route('life_theater.show', ['id' => $theaterId]) }}'">
                                        {{ $detail->title }}<br>
                                        <small class="text-muted">{{ $detail->subtitle ?? '' }}</small>
                                    </td>
                                    <td style="background-color: {{ $colorCode }}; cursor: pointer;" onclick="openModal('life_theater_share-modal',{ life_theater_id: '{{ $theaterId }}', theater_title: '{{ $detail->title ?? '' }}'});">
                                        <i class="fa-solid fa-users red"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                    </div>
                </div>
            </div>
        </div>

        {{-- 共有作品のアコーディオン --}}
        <div class="accordion" id="sharedTheaterListAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSharedTheaters">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSharedTheaters" aria-expanded="true" aria-controls="collapseSharedTheaters">
                        <h3>共有作品</h3>
                    </button>
                </h2>
                <div id="collapseSharedTheaters" class="accordion-collapse collapse show" aria-labelledby="headingSharedTheaters" data-bs-parent="#sharedTheaterListAccordion">
                    <div class="accordion-body">

                        {{-- ナビ用の色選択タブ --}}
                        <div class="d-flex overflow-auto contents_box mb-3">
                            <ul class="nav nav-pills flex-nowrap" id="theaterShareColorTabs">
                                <li class="nav-item">
                                    <a class="nav-link nav-link-simple active border me-1 px-2 py-1" href="javascript:void(0)" style="background-color: transparent;"
                                    onclick="filterTheaters('all', this, 'share')">all</a>
                                </li>
                                @foreach(config('common.note_colors', []) as $key => $color)
                                    <li class="nav-item">
                                        <a class="nav-link nav-link-simple border me-1 px-2 py-1" style="background-color: {{ $color['code'] }};" href="javascript:void(0)" onclick="filterTheaters('{{ $key }}', this, 'share')">
                                            {{ $share_theater_counts[$key] ?? 0 }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        
                        {{-- 共有作品リストテーブル --}}
                        <table id="theaterShareTable" class="table table-borderless table-data-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 85%">
                                <col style="width: 15%">
                            </colgroup>
                            @foreach ($shared_theater_list as $key => $detail)
                                @php
                                    $theaterId = $detail->life_theater_id ?? $detail->id;
                                    $colorCode = config('common.note_colors.'.$detail->theme_color_num.'.code', '#ffffff');
                                @endphp
                                <tr class="table-row theater-item" data-color="{{ $detail->theme_color_num }}">
                                    <td style="background-color: {{ $colorCode }}; cursor: pointer;" onclick="window.location='{{ route('life_theater.show', ['id' => $theaterId]) }}?share_flag=1'">
                                        {{ $detail->title }}<br>
                                        <small class="text-muted">{{ $detail->subtitle ?? '' }}</small>
                                    </td>
                                    <td style="background-color: {{ $colorCode }}; cursor: pointer;"
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

    <!-- モーダルインクルード（名称統一） -->
    @include('modals.life_theater_add-modal')
    @include('modals.life_theater_share-modal')
    @include('layouts.adv_popup')

@endsection

<script>
    function filterTheaters(colorNum, element, type) {
        let selector = (type === 'my') ? '#theaterColorTabs .nav-link' : '#theaterShareColorTabs .nav-link';
        const tabs = document.querySelectorAll(selector);

        tabs.forEach(tab => tab.classList.remove('active'));
        element.classList.add('active');

        selector = (type === 'my') ? '#theaterTable .theater-item' : '#theaterShareTable .theater-item';
        const rows = document.querySelectorAll(selector);
        
        rows.forEach(row => {
            const rowColor = row.getAttribute('data-color');
            if (colorNum === 'all' || rowColor === colorNum.toString()) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>