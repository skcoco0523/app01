@extends('layouts.app')

@section('content')
<div class="container py-5 text-center">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 480px;">
        <div class="card-body p-4">
            <div class="mb-3 text-secondary">
                <i class="fa-solid fa-link-slash fa-3x"></i>
            </div>
            <h5 class="fw-bold mb-3">公開URLが無効です</h5>
            <p class="text-muted small mb-4">
                {{ $message ?? '指定された公開URLは停止（削除）されたか、無効になっています。' }}
            </p>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm px-4">
                トップページへ
            </a>
        </div>
    </div>
</div>
@endsection