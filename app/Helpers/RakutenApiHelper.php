<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use App\Models\AdvApiCache;
use Carbon\Carbon;

class RakutenApiHelper
{
    /**
     * 楽天APIを使用して商品を検索する
     * 
     * @param string $keyword 検索キーワード
     * @param int $hits 取得件数
     * @param int|null $categoryId カテゴリID
     * @return array
     */
    public static function searchItems($keyword, $hits = 10, $categoryId = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        make_error_log($error_log, "keyword=" . $keyword . ", category_id=" . $categoryId);

        if (empty($keyword)) {
            make_error_log($error_log, "keyword empty");
            return [];
        }

        try {
            // DBキャッシュの確認
            $cache = AdvApiCache::where('keyword', $keyword)
                ->where('category_id', $categoryId)
                ->where('expired_at', '>', Carbon::now())
                ->first();

            if ($cache) {
                make_error_log($error_log, "cache hit: id=" . $cache->id);
                return json_decode($cache->response_json, true);
            }

            make_error_log($error_log, "cache miss or expired. requesting API...");
            make_error_log($error_log, "url:" . config('app.url'));
            make_error_log($error_log, "application_id:" . config('services.rakuten.application_id'));

            $response = Http::withHeaders([
                'Origin' => config('services.rakuten.origin'),
            ])->timeout(10)->get(
                'https://openapi.rakuten.co.jp/ichibams/api/IchibaItem/Search/20220601',
                [
                    'applicationId' => config('services.rakuten.application_id'),
                    'accessKey'     => config('services.rakuten.access_key'),
                    'affiliateId'   => config('services.rakuten.affiliate_id'),
                    'keyword'       => str_replace(',', ' ', $keyword),
                    'hits'          => $hits,
                    'format'        => 'json',
                    'formatVersion' => 2,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['Items'] ?? [];
                make_error_log($error_log, "success count=" . count($items));
                
                $formattedItems = collect($items)->map(function ($item) {
                    // formatVersion=2 の場合は Item キーがなく直接データが入っている場合がある
                    $i = isset($item['Item']) ? $item['Item'] : $item;

                    return [
                        'Item' => [
                            'itemName' => $i['itemName'] ?? '',
                            'affiliateUrl' => $i['affiliateUrl'] ?? '',
                            'itemUrl' => $i['itemUrl'] ?? '',
                            'mediumImageUrls' => $i['mediumImageUrls'] ?? [],
                            'itemPrice' => $i['itemPrice'] ?? 0,
                            'shopName' => $i['shopName'] ?? '',
                            'itemCaption' => mb_substr(strip_tags($i['itemCaption'] ?? ''), 0, 120),
                        ]
                    ];
                })->toArray();

                // DBキャッシュの保存
                AdvApiCache::updateOrCreate(
                    ['keyword' => $keyword, 'category_id' => $categoryId],
                    [
                        'response_json' => json_encode($formattedItems),
                        'expired_at' => Carbon::now()->addDay(),
                    ]
                );

                return $formattedItems;
            }

            make_error_log($error_log, "api request failed: " . $response->status() . " " . $response->body());
            return [];

        } catch (\Exception $e) {
            make_error_log($error_log, "DB/Http Exception: " . $e->getMessage());
            return [];
        }
    }
}
