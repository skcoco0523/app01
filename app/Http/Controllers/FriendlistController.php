<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Friendlist;
use App\Models\User;

class FriendlistController extends Controller
{
    //フレンドリスト表示
    public function index(Request $request)
    {
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        //if (empty($input['page']))              $input['page']=null;
        if (empty($input['friend_code']))       $input['friend_code']=null;
        if (empty($input['table']))             $input['table']='accepted';

        //ユーザー検索
        $search_user = array();
        
        $friendlist['search']= array();
        if($input['table']=='search'){
            if($input['friend_code']){
                $search_user = Friendlist::findByFriendCode($input['friend_code'],Auth::id());
                if(!$search_user){
                    //ユーザー検索で一致しなかった場合は場合はリダイレクトする
                    $message = make_message('ユーザーが見つかりませんでした。', 'error');
                    return redirect()->route('friend.index')->with($message);
                }else{
                    $friendlist['search'][]= $search_user;
                }
            }
        }else{
            //0:承認待ち,1:承認済み,2:拒否
            $friendlist = Friendlist::getFriendList(Auth::id());
        }
        return view('friend.show', compact('friendlist', 'input'));
    }    
    //フレンド詳細表示
    public function show(Request $request, $id)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        if (empty($input['page']))              $input['page']=null;
        if (empty($input['table']))             $input['table']=null;
        //選択しているタブのﾍﾟｰｼﾞｬｰのみページを指定する
        $favorite_list = array();

        //ユーザー検索
        $friend_profile = User::getProfile($id);
        //公開フラグ確認
        if(isset($friend_profile) && $friend_profile->friend_status=="accepted"){
            //フレンド承認済みで相手の公開制限無し
            if($friend_profile->release_flag!=1 && $friend_profile->friend_status=="accepted"){
            }else{
            }
            return view('friend.detail', compact('friend_profile', 'input'));
        }else{
            // フレンドリストにリダイレクト
            $message = make_message('フレンド以外のデータは閲覧できません。', 'error');
            return redirect()->route('friend.index')->with($message);

        }
    }
    //フレンド申請
    public function store(Request $request)
    {
        $user_id = Auth::id();
        $friend_id =  (int) $request->user_id;
        if(($user_id != $friend_id)){
            $status = Friendlist::requestFriend($user_id, $friend_id);

            if($status)     $message = make_message('フレンド申請を送信しました。', 'friend');
            else            $message = make_message('フレンド申請の送信に失敗しました。', 'error');
            
            if($status){
                //フレンドへ通知
                $user_prf = User::getProfile($user_id);
                
                $send_info = new \stdClass();
                $send_info->title = "フレンド申請";
                $send_info->body = $user_prf->name. "からフレンド申請が届きました";
                $send_info->url = route('friend.index', ['table' => 'request']);

                push_send($send_info, $friend_id);
            }
        }else{
            $message = make_message('フレンド申請の送信に失敗しました。', 'error');
        }
        
        return redirect()->route('friend.index')->with($message);
    }
    //フレンド申請承諾
    public function accept(Request $request)
    {
        $user_id = Auth::id();
        $friend_id =  (int) $request->user_id;
        $status = Friendlist::acceptFriend($user_id, $friend_id);

        if($status)     $message = make_message('フレンド申請を承諾しました。', 'friend');
        else            $message = make_message('フレンド申請の承諾に失敗しました。', 'error');

        if($status){  
            //フレンドへ通知
            $user_prf = User::getProfile($user_id);
             
            $send_info = new \stdClass();
            $send_info->title = "フレンド申請";
            $send_info->body =  $user_prf->name. "からフレンド申請が承諾されました";
            $send_info->url = route('friend.index', ['table' => 'pending']);

            push_send($send_info, $friend_id);
        }
        return redirect()->route('friend.index')->with($message);
    }

    //フレンド申請拒否
    public function decline(Request $request)
    {
        $friend_id =  (int) $request->user_id;
        $status = Friendlist::declineFriend(Auth::id(), $friend_id);

        if($status)     $message = make_message('フレンド申請を拒否しました。', 'friend');
        else            $message = make_message('フレンド申請の拒否に失敗しました。', 'error');

        return redirect()->route('friend.index')->with($message);
    }
    //フレンド申請キャンセル
    public function cancel(Request $request)
    {
        $friend_id =  (int) $request->user_id;
        $status = Friendlist::cancelFriend(Auth::id(), $friend_id);

        if($status)     $message = make_message('フレンド申請を削除しました。', 'friend');
        else            $message = make_message('フレンド申請の削除に失敗しました。', 'error');

        return redirect()->route('friend.index')->with($message);
    }
}
