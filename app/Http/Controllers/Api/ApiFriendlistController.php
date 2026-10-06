<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

use App\Models\Friendlist;
use App\Models\NoteShare;
use App\Models\LifeTheaterShare;


class ApiFriendlistController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    
    // フレンドリスト取得 (共有メモ・共有ライフシアターの共有者取得)
    public function api_friendlist_get(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input                      = $request->all();
        $note_share_status          = get_proc_data($input, "note_share_status");
        $life_theater_share_status  = get_proc_data($input, "life_theater_share_status");

        make_error_log($error_log, "note_share_status:" . $note_share_status . " / life_theater_share_status:" . $life_theater_share_status);

        $friendlist = Friendlist::getFriendList(Auth::id());
        $friendlist_array = [];

        // 1. 共有メモのステータス情報取得
        if ($note_share_status) {
            $input['search_note_id'] = get_proc_data($input, "note_id");
            if ($input['search_note_id']) {
                $share_note_list  = NoteShare::getSharingNoteList(null, false, null, $input);
                $sharing_user_ids = $share_note_list->pluck('share_user_id')->toArray();
            } else {
                $sharing_user_ids = [];
            }
        }

        // 2. 共有ライフシアターのステータス情報取得
        if ($life_theater_share_status) {
            $input['search_life_theater_id'] = get_proc_data($input, "life_theater_id");
            if ($input['search_life_theater_id']) {
                $share_theater_list      = LifeTheaterShare::getSharingLifeTheaterList(null, false, null, $input);
                $sharing_theater_user_ids = $share_theater_list->pluck('share_user_id')->toArray();
            } else {
                $sharing_theater_user_ids = [];
            }
        }

        foreach ($friendlist['accepted'] as $key => $friend) {
            $data = [
                'friend_id' => $friend->friend_id,
                'name'      => $friend->name,
            ];

            // 共有メモの共有状態を追加
            if ($note_share_status) {
                $data['is_shared']     = in_array($friend->id, $sharing_user_ids);
                if ($data['is_shared']) {
                    $note = $share_note_list->where('share_user_id', $friend->id)->first();
                }
                $data['note_id']       = $data['is_shared'] ? $note->note_id : null;
                $data['note_share_id'] = $data['is_shared'] ? $note->note_share_id : null;
                $data['note_title']    = $data['is_shared'] ? $note->title : null;
                $data['admin_flag']    = $data['is_shared'] ? $note->admin_flag : null;
            }

            // 共有ライフシアターの共有状態を追加
            if ($life_theater_share_status) {
                $data['is_shared']             = in_array($friend->id, $sharing_theater_user_ids);
                if ($data['is_shared']) {
                    $theater = $share_theater_list->where('share_user_id', $friend->id)->first();
                }
                $data['life_theater_id']       = $data['is_shared'] ? $theater->life_theater_id : null;
                $data['life_theater_share_id'] = $data['is_shared'] ? $theater->life_theater_share_id : null;
                $data['share_id']              = $data['is_shared'] ? $theater->life_theater_share_id : null;
                $data['admin_flag']            = $data['is_shared'] ? $theater->admin_flag : null;
            }

            $friendlist_array[] = $data;
        }
        make_error_log($error_log, "friendlist_array:" . print_r($friendlist_array, 1));

        // JSON形式で返す
        return response()->json($friendlist_array);
    }
    
}
