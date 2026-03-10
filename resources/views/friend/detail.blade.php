@extends('layouts.app')
 
@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('friend.index') }}'"></i>
    <div class="text-center ">
        <p class="card-text">フレンド：{{ $friend_profile->name }}</p>
    </div>

    <div class="d-flex overflow-auto contents_box">
        <ul class="nav nav-pills flex-nowrap">
            <li class="nav-item nav-item-red" onclick="window.location='{{ route('friend.show', ['id'=>$friend_profile->id]) }}?table=1'">
                <a class="nav-link nav-link-red {{ $input['table']=='1' ? 'active' : '' }}">1</a>
            </li>
            <li class="nav-item nav-item-red" onclick="window.location='{{ route('friend.show', ['id'=>$friend_profile->id]) }}?table=2'">
                <a class="nav-link nav-link-red {{ $input['table']=='2' ? 'active' : '' }}">2</a>
            </li>
        </ul>
    </div>


    @if($input['table']=='1')

    @elseif($input['table']=='2')

    @endif

    </div>

    <div id="category" class="tab-content">

    </div>

    <?//広告モーダル?>   
    @include('layouts.adv_popup')

@endsection

<style>
</style>
