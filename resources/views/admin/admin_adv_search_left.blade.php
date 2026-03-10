{{--検索--}}
<form method="GET" action="{{ route('admin.adv.index') }}">

    検索条件
    <div class="row g-3 align-items-end">
        <div class="col-6 col-md-12">
            ・広告名
            <input type="text" name="search_name" class="form-control" value="{{$input['search_name'] ?? ''}}">
        </div>
        <div class="col-6 col-md-12">
            ・タイプ
            <select name="search_type" class="form-control">
                <option value="" {{ ($input['search_type'] ?? '') == '' ? 'selected' : '' }}></option>
                @foreach($type_list as $type)
                    <option value="{{ $type }}" {{ ($input['search_type'] ?? '') == $type ? 'selected' : '' }}>{{$type}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-12">
            ・クリック数
            <div class="row align-items-center">
                <div class="col-8">
                    <input type="number" name="search_click_cnt_s" class="form-control" value="{{ $input['search_click_cnt_s'] ?? '' }}" min="0">
                </div>
                以上
                <div class="col-8">
                    <input type="number" name="search_click_cnt_e" class="form-control" value="{{ $input['search_click_cnt_e'] ?? '' }}" min="0">
                </div>
                以下
            </div>
        </div>
        <div class="col-4 col-md-12">
            ・掲載期間(月)
            <select name="search_month" class="form-control">
                <option value="" {{ ($input['search_month'] ?? '') == '' ? 'selected' : '' }}></option>
                @for ($i = 1; $i <= 12; $i++) <option value="{{ $i }}" {{ ($input['search_month'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}月</option> @endfor
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・掲載期間(日)
            <select name="search_day" class="form-control">
                <option value="" {{ ($input['search_day'] ?? '') == '' ? 'selected' : '' }}></option>
                @for ($i = 1; $i <= 31; $i++) <option value="{{ $i }}" {{ ($input['search_day'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}日</option> @endfor
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・掲載期間(日数)
            <select name="search_days" class="form-control">
                <option value="" {{ ($input['search_days'] ?? '') == '' ? 'selected' : '' }}></option>
                @for ($i = 1; $i <= 99; $i++) <option value="{{ $i }}" {{ ($input['search_days'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}日間</option> @endfor
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・対象年齢
            <select name="search_age" class="form-control">
                <option value="" {{ ($input['search_age'] ?? '') == '' ? 'selected' : '' }}></option>
                @for ($i = 10; $i <= 90; $i+=10) <option value="{{ $i }}" {{ ($input['search_age'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}年代</option> @endfor
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・対象性別
            <select name="search_gender" class="form-control">
                <option value="" {{ ($input['search_gender'] ?? '') == '' ? 'selected' : '' }}></option>
                <option value="0" {{ ($input['search_gender'] ?? '') == '0' ? 'selected' : '' }}>男性</option>
                <option value="1" {{ ($input['search_gender'] ?? '') == '1' ? 'selected' : '' }}>女性</option>
            </select>
        </div>
        <div class="col-4 col-md-12">
            ・表示有無
            <select name="search_disp_flag" class="form-control">
                <option value="" {{ ($input['search_disp_flag'] ?? '') == '' ? 'selected' : '' }}></option>
                <option value="0" {{ ($input['search_disp_flag'] ?? '') == '0' ? 'selected' : '' }}>非公開</option>
                <option value="1" {{ ($input['search_disp_flag'] ?? '') == '1' ? 'selected' : '' }}>公開</option>
            </select>
        </div>



        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success">検索</button>
        </div>
    </div>
</form>
