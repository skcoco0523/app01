@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">{{ __('Request') }}</div>

    <div class="card-body">
        <form method="POST" action="{{ route('request.send') }}">
            @csrf
            <div class="row mb-3">
                <label for="type" class="col-md-4 col-form-label text-md-end">種別</label>

                <div class="col-md-6">
                    <select name="type" class="form-select" required>
                        <option value="{{ config('common.request_type.request') }}" 
                            {{ isset($input['type']) && $input['type'] === config('common.request_type.request') ? 'selected' : '' }}>要望</option>
                        <option value="{{ config('common.request_type.inquiry') }}" 
                            {{ isset($input['type']) && $input['type'] === config('common.request_type.inquiry') ? 'selected' : '' }}>問い合わせ</option>
                            
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <label for="message" class="col-md-4 col-form-label text-md-end">問い合わせ内容</label>

                <div class="col-md-6">
                    <textarea class="form-control" name="message" required>{{$input['message'] ?? ''}}</textarea>
                </div>
            </div>

            <div class="row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Send') }}
                    </button>
                </div>
            </div>
        </form>

        {{-- 履歴 --}}
        @if(isset($user_request) && $user_request->isNotEmpty())
            <label for="history" class="col-md-4 col-form-label text-md-end">{{ __('History') }}</label>

            <ol class="list-group list-group-numbered">
            <ul class="list-group">
                @foreach ($user_request as $request)  
                <li class="list-group-item d-flex justify-content-between align-items-start {{ $loop->iteration % 2 == 0 ? 'bg-light' : 'bg-white' }} clicktable-row" data-target="reply-{{$request->id}}">
                    <div class="ms-2 me-auto">
                        <div class="" data-full-text="{{ $request->type == 0 ? $request->message : $request->message }}">
                            <div class="fw-bold mb-1">
                            @if($request->type == config('common.request_type.request')) 要望：@endif
                            @if($request->type == config('common.request_type.inquiry')) 問い合わせ：@endif
                            </div>
                            <div class="message-preview" data-full-text="{!! nl2br(e($request->message)) !!}">
                                {{ mb_substr(str_replace(["\r\n", "\r", "\n"], ' ', $request->message), 0, 20) . '...' }}
                            </div>
                        </div>
                        <div id="reply-{{$request->id}}" class="reply-content mt-2 d-none">
                            <div class="fw-bold mb-1">回答:</div>
                            {!! nl2br(e($request->reply)) !!}
                        </div>
                    </div>
                    @if($request->status == config('common.request_status.unresolved') && $request->reply == null)
                        <span class="badge bg-secondary text-white rounded-pill">確認中</span>
                    @elseif($request->status == config('common.request_status.unresolved') && $request->reply != null)
                        <span class="badge bg-secondary text-white rounded-pill">確認中(一次回答)</span>
                    @elseif($request->status == config('common.request_status.resolved'))
                        <span class="badge bg-primary text-white rounded-pill">完了</span>
                    @endif
                </li>
                @endforeach
                </ul>
            </ol>
        </div>
        {{--ﾊﾟﾗﾒｰﾀ--}}
        @php
            $additionalParams = [
            ];
        @endphp
        {{--ﾍﾟｰｼﾞｬｰ--}}
        @include('layouts.pagination', ['paginator' => $user_request,'additionalParams' => $additionalParams,])
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.clicktable-row').forEach(function (element) {
        element.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var targetElement = document.getElementById(targetId);
            var messagePreview = this.querySelector('.message-preview');

            if (targetElement) {
                targetElement.classList.toggle('d-none');
            }
            if (messagePreview) {
                messagePreview.classList.toggle('expanded');
                if (messagePreview.classList.contains('expanded')) {
                    messagePreview.innerHTML = messagePreview.getAttribute('data-full-text');
                } else {
                    // Display truncated text without line breaks and HTML tags
                    var fullText = messagePreview.getAttribute('data-full-text');
                    // Remove HTML tags and replace newlines with spaces
                    var plainText = fullText.replace(/<[^>]*>/g, '').replace(/\n/g, ' ');
                    // Truncate to 20 characters
                    var truncatedText = plainText.length > 20 ? plainText.slice(0, 20) + '...' : plainText;
                    messagePreview.innerHTML = truncatedText;
                }
            }
        });
    });
});
</script>


@endsection
