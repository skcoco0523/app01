<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Affiliate;
use App\Models\Advertisement;

use Illuminate\Support\Facades\View; // Viewクラスをインポート


//アルバムコントローラー
class AdminAdvController extends Controller
{    
    public function __construct()
    {
        // 変数をビュー全体に渡す
        View::share('type_list', ['top', 'banner', 'footer', 'in_contents', 'popup']);           //必要があれば追加する
    }
    //追加ページ
    public function create(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $input['admin_flag']            = true;
        $input['cdate_desc']            = true;
        $advertisement = Advertisement::getAdv_list(5,true,null,$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
        $msg = request('msg');
        
        return view('admin.admin_home', compact('advertisement', 'input', 'msg'));
    }
    //追加処理
    public function store(Request $request)
    {
        $input = $request->all();
        $msg=null;
        $aff_ret = Affiliate::createAffiliate($input);
        //affiliate登録成功
        if($aff_ret['error_code'] == 0){
            $input['aff_id'] = $aff_ret['id'];
            $adv_ret = Advertisement::createAdv($input);

            //広告登録成功
            if($adv_ret['error_code'] == 0){
                $adv_id=$adv_ret['id']; //追加した広告ID
                $msg = "広告情報を登録しました。";

            //広告登録失敗 
            }else{
                //一時的に作られたアフィリエイトデータは削除
                Affiliate::delAffiliate($input);
                if($adv_ret['error_code']==1)     $msg = "広告名を入力してください。";
                if($adv_ret['error_code']==2)     $msg = "アフィリエイト情報の登録に失敗しました。";
                if($adv_ret['error_code']==3)     $msg = "広告タイプの制約に反します。";
                if($adv_ret['error_code']==4)     $msg = "掲載期間が不正です。";
                if($adv_ret['error_code']==-1)    $msg = "広告情報の登録に失敗しました。";
            }
            
        //affiliate登録失敗
        }else{
            if($aff_ret['error_code']==1)     $msg = "アフィリエイトリンクを入力してください。";
            if($aff_ret['error_code']==2)     $msg = "アフィリエイトリンクが不正です。(URLと画像情報が必要)";
            if($aff_ret['error_code']==-1)    $msg = "アフィリエイト情報の登録に失敗しました。";
        }

        return redirect()->route('admin.adv.create', ['input' => $input, 'msg' => $msg]);

    }
    //検索
    public function index(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $input['admin_flag']            = true;
        $input['page']                  = get_proc_data($input,"page");

        $input['type_asc']            = true;
        $advertisement = Advertisement::getAdv_list(10,true,$input['page'],$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
        
        $msg = request('msg');
        
        return view('admin.admin_home', compact('advertisement', 'input', 'msg'));
    }
    //削除
    public function destroy(Request $request)
    {
        $input = $request->all();
        $msg=null;
        
        
        $adv_ret = Advertisement::delAdv($input);
        
        //広告削除成功
        if($adv_ret['error_code'] == 0){
            //広告の削除ができたら　affiliateを削除
            $aff_ret = Affiliate::delAffiliate($input);
            if($aff_ret['error_code'] == 0){
                $msg = "広告情報を削除しました。";
            }else{
                $msg = "アフィリエイト情報の削除に失敗しました。";
            }
        }else{
            if($adv_ret['error_code']==-1)    $msg = "広告情報の削除に失敗しました。";
        }


        return redirect()->route('admin.adv.index', ['input' => $input, 'msg' => $msg]);
    }
    //変更
    public function update(Request $request)
    {
        //$input = $request->only(['id', 'alb_name', 'art_id', 'art_name', 'release_date', 'aff_id', 'aff_link', 'keyword']);
        $input = $request->all();
        $msg=null;
        
        //affiliate変更
        //$input = $request->only(['aff_link', 'aff_id']);
        if($input['aff_link']){
            $aff_ret = Affiliate::chgAffiliate($input);
            if($aff_ret['error_code']==1)     $msg = "アフィリエイトリンクを入力してください。";
            if($aff_ret['error_code']==2)     $msg = "アフィリエイトリンクが不正です。(URLと画像情報が必要)";
            if($aff_ret['error_code']==-1)    $msg = "アフィリエイト情報の変更に失敗しました。";
            if($msg!==null) return redirect()->route('admin.adv.index', ['input' => $input, 'msg' => $msg]);

        }
        
        //広告変更
        $adv_ret = Advertisement::chgAdv($input);

        if($adv_ret['error_code']==0){
            $msg = "広告情報を更新しました。";
        }else{
            if($adv_ret['error_code']==1)     $msg = "広告名を入力してください。";
            //if($adv_ret['error_code']==2)     $msg = "アフィリエイト情報の登録に失敗しました。";
            if($adv_ret['error_code']==3)     $msg = "広告タイプの制約に反します。";
            if($adv_ret['error_code']==4)     $msg = "掲載期間が不正です。";
            if($adv_ret['error_code']==-1)    $msg = "広告情報の更新に失敗しました。";
        }
        
        return redirect()->route('admin.adv.index', ['input' => $input, 'msg' => $msg]);
    }
}
