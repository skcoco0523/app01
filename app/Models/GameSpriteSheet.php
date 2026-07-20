<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GameSpriteSheet extends Model
{
    use HasFactory;

    protected $table = 'game_sprite_sheets';

    protected $fillable = ['filename', 'category', 'pixel_data', 'grid_data'];

    protected $casts = [
        'pixel_data' => 'array',
        'grid_data'  => 'array',
    ];

    public static function getSpriteSheetList($disp_cnt=null, $pageing=false, $page=1, $keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                //管理者による検索
                if (get_proc_data($keyword, "admin_flag")) {
                    if (get_proc_data($keyword, "search_tag"))      $sql_cmd = $sql_cmd->where('category', $keyword['search_tag']);
                    if (get_proc_data($keyword, "search_keyword"))   $sql_cmd = $sql_cmd->where('filename', 'like', '%' . $keyword['search_keyword'] . '%');
                    if (get_proc_data($keyword, "search_filename")) $sql_cmd = $sql_cmd->where('filename', $keyword['search_filename']);
                //ユーザーによる検索
                } else {
                    // 必要に応じてユーザー向けの制限を追加
                }
            }
            $sql_cmd = $sql_cmd->orderBy('category', 'asc');
            $sql_cmd = $sql_cmd->orderBy('filename', 'asc');

            if ($pageing) {
                if ($disp_cnt === null) $disp_cnt = 15;
                $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
            } elseif ($disp_cnt !== null) {
                $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
            } else {
                $sql_cmd = $sql_cmd->get();
            }
            return $sql_cmd;
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return collect();
        }
    }

    // スプライトシートを作成する
    public static function createSpriteSheet($filename, $category, $atlasData, $gridData)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $data = ['category' => $category, 'pixel_data' => $atlasData];
            if ($gridData !== null) $data['grid_data'] = $gridData;
            self::updateOrCreate(['filename' => $filename], $data);

            return ['success' => true, 'msg' => "スプライトシート「{$filename}」を保存しました。"];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }

    // スプライトシートのファイル名を変更する
    public static function renameSpriteSheet($oldFilename, $newFilename)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sheet = self::where('filename', $oldFilename)->first();
            if (!$sheet) {
                return ['success' => false, 'msg' => '対象ファイルが見つかりません。', 'sheet' => null];
            }
            // =========================================
            // ファイル名変更に伴う参照先の調整 座標定義の参照先も変更する必要がある
            // =========================================
            // pixel_data
            $pixel_data = $sheet->pixel_data ?? [];
            if (isset($pixel_data['textures'][0])) {
                $pixel_data['textures'][0]['image'] = $newFilename;
            }
            // grid_data
            $grid_data = $sheet->grid_data ?? [];
            if (isset($grid_data['textures'][0])) {
                $grid_data['textures'][0]['image'] = $newFilename;
            }
            $sheet->filename   = $newFilename;
            $sheet->pixel_data = $pixel_data;
            $sheet->grid_data = $grid_data;
            $sheet->save();
            // =========================================

            return ['success' => true, 'msg' => "スプライトシートの名前を「{$newFilename}」に変更しました。", 'sheet' => $sheet];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage(), 'sheet' => null];
        }
        
    }

    public static function chgSpriteSheet($filename, $atlasData, $target)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            //'pixel' or 'grid'
            if($atlasData === null || !in_array($target, ['pixel', 'grid'])) {
                return ['success' => false, 'msg' => '不正なパラメータです。'];

            }
            if($target === 'pixel') $target = 'pixel_data';
            if($target === 'grid')  $target = 'grid_data';
            $sheet = self::where('filename', $filename)->firstOrFail();
            $sheet->$target = $atlasData;
            $sheet->save();
            return ['success' => true, 'msg' => "スプライトシート「{$filename}」のパーツ切り出し定義を保存しました！"];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }

    /**
     * このスプライトシートが他で使用されているか確認
     */
    public function getUsageCount()
    {
        // アイテムでの使用を確認
        $itemCount = GameItem::where('sprite_sheet_id', $this->id)->count();
        
        // キャラクターのmotion_data内での使用確認（JSON検索）
        // ※パフォーマンス上の懸念がある場合は、将来的に中間テーブル化を検討
        $charCount = GameCharacter::where('motion_data', 'like', '%' . $this->filename . '%')->count();

        return [
            'total' => $itemCount + $charCount,
            'items' => $itemCount,
            'characters' => $charCount,
        ];
    }

    public static function delSpriteSheet($filename)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sheet = self::where('filename', $filename)->first();
            if (!$sheet) {
                return ['success' => false, 'msg' => '⚠️エラー: 対象データがありません。', 'sheet' => null];
            }

            // 使用状況チェック
            $usage = $sheet->getUsageCount();
            if ($usage['total'] > 0) {
                $details = [];
                if ($usage['items'] > 0) $details[] = "アイテム: {$usage['items']}件";
                if ($usage['characters'] > 0) $details[] = "キャラクター: {$usage['characters']}件";
                
                return [
                    'success' => false, 
                    'msg' => '⚠️削除不可: この画像は以下の箇所で使用されています。先に参照を解除してください。<br>・' . implode('<br>・', $details), 
                    'sheet' => $sheet
                ];
            }

            $sheet->delete();
            return ['success' => true, 'msg' => "画像「{$filename}」を完全削除しました。", 'sheet' => $sheet];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage(), 'sheet' => null];
        }
    }

}
