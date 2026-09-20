{{-- リモコンデザインプレビュー --}}
@extends('layouts.app')

@section('content')

@if($virtualremoteblade->views_path)
    <div class="alert alert-warning text-center" role="alert">
        <p class="text-center mb-0">
            <strong class="text-danger fs-4">【プレビュー】</strong>
            <span class="badge {{ $virtualremoteblade->library_flag ? 'bg-info' : 'bg-secondary' }} ms-2">
                {{ $virtualremoteblade->library_flag ? 'ライブラリ型' : '学習型(RAW)' }}
            </span>
            
            {{-- プロトコル表示 --}}
            @if($virtualremoteblade->protocol)
                <span class="badge bg-dark ms-1">
                    {{ $virtualremoteblade->protocol }}
                </span>
            @endif
        </p>
        <p class="text-center mb-0">
            @if($virtualremoteblade->test_flag)
                テスト状態のため、<br>ユーザーが使用することはできません。
            @endif
        </p>
    </div>

    @include($virtualremoteblade->views_path)
    
@else
    <div class="alert alert-warning text-center" role="alert">
        <p class="text-center mb-0">
            <strong class="text-danger fs-4">【プレビュー】</strong>
            <span class="badge {{ $virtualremoteblade->library_flag ? 'bg-info' : 'bg-secondary' }} ms-2">
                {{ $virtualremoteblade->library_flag ? 'ライブラリ型' : '学習型(RAW)' }}
            </span>
        </p>
        <p class="text-center mb-0">
            @if($virtualremoteblade->test_flag)
                テスト状態のため、<br>ユーザーが使用することはできません。
            @endif
        </p>
    </div>
@endif

@endsection

<style>
    /* プレビュー時は未割当状態を変えない */
    .noset-signal {
         opacity: 1 !important;
    }
</style>