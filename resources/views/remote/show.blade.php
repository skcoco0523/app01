@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('home') }}'"></i>
    <div class="container py-4">
        <h1 class="mb-4">スマートリモコン管理</h1>

        {{-- ================================================ --}}
        {{-- デバイスリストのアコーディオン --}}
        {{-- ================================================ --}}
        @php
            // ページャー使用時はリスト表示を展開
            $show_device = request('table') === 'iotdevice';
        @endphp
        <div class="accordion" id="deviceListAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingDevices">
                    <button class="accordion-button {{ $show_device ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDevices" aria-expanded="{{ $show_device ? 'true' : 'false' }}" aria-controls="collapseDevices">
                        <h3>デバイスリスト</h3>
                    </button>
                </h2>
                <div id="collapseDevices" class="accordion-collapse collapse {{ $show_device ? 'show' : '' }}" aria-labelledby="headingDevices" data-bs-parent="#deviceListAccordion">
                    <div class="accordion-body">

                        {{-- 新規デバイス登録行用のテーブル --}}
                        <table class="table table-borderless table-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%; min-width: 70px;">
                                <col style="width: 80%">
                            </colgroup>
                            <td class="icon-55 d-flex justify-content-center b-gray" onclick="openModal('add_iotdevice-modal');">
                                <i class="fa-solid fa-plus icon-25 red"></i>
                            </td>
                            <td onclick="openModal('add_iotdevice-modal');" style="vertical-align: middle;">
                                新規デバイス
                            </td>
                        </table>

                        {{-- 既存のデバイスリストのテーブル --}}
                        <table class="table table-borderless table-data-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%; min-width: 70px;">
                                <col style="width: 80%">
                            </colgroup>
                            @foreach ($iotdevice_list as $key => $detail)
                                <tr class="table-row" onclick="window.location='{{ route('iotdevice.detail') }}?id={{ $detail->id }}'">
                                    <td class="icon-55 d-flex justify-content-center">
                                        {{--画像参照を切り替える--}}
                                        @if(isset($detail->icon_class))
                                            <i class="fa-solid {{ $detail->icon_class }} red icon-25"></i>
                                        @else
                                            <img src="{{ asset('img/pic/no_image.png') }}" class="icon-55">
                                        @endif
                                    </td>
                                    <td>{{ $detail->name }}</td>
                                </tr>
                            @endforeach
                        </table>

                        {{-- ページャー用のパディング --}}
                        <div class="p-3"> 
                            @php
                                $additionalParams = ['table' => 'iotdevice' ,];
                            @endphp
                            @include('layouts.pagination', ['paginator' => $iotdevice_list,'additionalParams' => $additionalParams,])
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================ --}}
        {{-- リモコンリストのアコーディオン --}}
        {{-- ================================================ --}}
        <div class="accordion" id="remoteListAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRemotes">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRemotes" aria-expanded="true" aria-controls="collapseRemotes">
                        <h3>リモコンリスト</h3>
                    </button>
                </h2>
                <div id="collapseRemotes" class="accordion-collapse collapse show" aria-labelledby="headingRemotes" data-bs-parent="#remoteListAccordion">
                    <div class="accordion-body">

                        {{-- 新規リモコン登録行用のテーブル --}}
                        <table class="table table-borderless table-center" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%; min-width: 70px;">
                                <col style="width: 80%">
                            </colgroup>
                            <td class="icon-55 d-flex justify-content-center align-items-center b-gray" onclick="openModal('add_virtualremote-modal');">
                                <i class="fa-solid fa-plus icon-25 red"></i>
                            </td>
                            <td onclick="openModal('add_virtualremote-modal');" style="vertical-align: middle;">
                                新規リモコン
                            </td>
                        </table>

                        {{-- 既存のリモコンリストのテーブル --}}
                        <table class="table table-borderless table-data-center" style="table-layout: fixed;">
                                <colgroup>
                                    <col style="width: 20%; min-width: 70px;">
                                    <col style="width: 80%">
                                </colgroup>
                                @foreach ($virtual_remote_list as $key => $detail)
                                    <tr class="table-row" onclick="window.location='{{ route('remote.detail') }}?id={{ $detail->id }}'">
                                        <td class="icon-55 d-flex justify-content-center align-items-center">
                                            {{--画像参照を切り替える--}}
                                            @if(isset($detail->icon_class))
                                                <i class="fa-solid {{ $detail->icon_class }} red icon-25"></i>
                                            @else
                                                <img src="{{ asset('img/pic/no_image.png') }}" class="icon-55">
                                            @endif
                                        </td>
                                        <td>{{ $detail->name }}</td>
                                    </tr>
                                @endforeach

                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- デバイス追加ポップアップモーダル -->
    @include('modals.add_iotdevice-modal')

    <!-- リモコン追加ポップアップモーダル -->
    @include('modals.add_virtualremote-modal')

    <?//広告モーダル?>   
    @include('layouts.adv_popup')
    

@endsection

<style>
    /* プレビュー時は未割当状態を変えない */
    .noset-signal {
         opacity: 1 !important;
    }
</style>