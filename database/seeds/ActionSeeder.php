<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ["Channel Category", "Lazada - Sponsored Search", "LZDSC_PULL_CHANNEL_CATE_SS", "LAZADA", ["crawler", "openapi"]],
            ["Products of Shop", "Lazada - Sponsored Search", "LZDSC_PULL_SHOP_PRODUCT_SS", "LAZADA", ["crawler", "openapi"]],
            ["Stock of Product", "Lazada - Sponsored Search", "LZDSC_PULL_PRODUCT_STOCK_SS", "LAZADA", ["crawler", "openapi"]],
            ["Sale Orders", "Lazada - Sponsored Search", "LZDSC_PULL_SALE_ORDER_SS", "LAZADA", ["crawler", "openapi"]],
            ["Sponsored Search Overall", "Lazada - Sponsored Search", "LZDSC_PULL_SS_OVERALL", "LAZADA", ["crawler"]],
            ["Sponsored Search - Campaign detail", "Lazada - Sponsored Search", "LZDSC_PULL_SS_CAMPAIGN_DETAIL", "LAZADA", ["crawler"]],
            ["Sponsored Search - Campaign-Product detail", "Lazada - Sponsored Search", "LZDSC_PULL_SS_PRODUCT_DETAIL", "LAZADA", ["crawler"]],
            ["Channel Category", "Shopee - Keyword Bidding", "SHPSC_PULL_CHANNEL_CATE_KWD_BID", "SHOPEE", ["crawler", "openapi"]],
            ["Channel Category", "Shopee - Shop Ads", "SHPSC_PULL_CHANNEL_CATE_SHOP_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Channel Category", "Shopee - Targetting Ads", "SHPSC_PULL_CHANNEL_CATE_TARGET_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Products of Shop", "Shopee - Keyword Bidding", "SHPSC_PULL_SHOP_PRODUCT_KWD_BID", "SHOPEE", ["crawler", "openapi"]],
            ["Products of Shop", "Shopee - Shop Ads", "SHPSC_PULL_SHOP_PRODUCT_SHOP_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Products of Shop", "Shopee - Targetting Ads", "SHPSC_PULL_SHOP_PRODUCT_TARGET_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Stock of Product", "Shopee - Keyword Bidding", "SHPSC_PULL_PRODUCT_STOCK_KWD_BID", "SHOPEE", ["crawler", "openapi"]],
            ["Stock of Product", "Shopee - Shop Ads", "SHPSC_PULL_PRODUCT_STOCK_SHOP_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Stock of Product", "Shopee - Targetting Ads", "SHPSC_PULL_PRODUCT_STOCK_TARGET_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Sale Orders", "Shopee - Keyword Bidding", "SHPSC_PULL_SALE_ORDER_KWD_BID", "SHOPEE", ["crawler", "openapi"]],
            ["Sale Orders", "Shopee - Shop Ads", "SHPSC_PULL_SALE_ORDER_SHOP_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Sale Orders", "Shopee - Targetting Ads", "SHPSC_PULL_SALE_ORDER_TARGET_ADS", "SHOPEE", ["crawler", "openapi"]],
            ["Keyword Bidding - Overall Data", "Shopee - Keyword Bidding", "SHPSC_PULL_OVERALL_DATA_KWD_BID", "SHOPEE", ["crawler"]],
            ["Keyword Bidding - Product detail", "Shopee - Keyword Bidding", "SHPSC_PULL_PRODUCT_DETAIL_KWD_BID", "SHOPEE", ["crawler"]]
        ];
        $actions = DB::table('actions')->get();
        $arr = [];
        foreach ($actions as $action) {
            $arr[$action->actions_code . '-' . $action->fk_feature] = $action->actions_name;
        }
        $temp = [];
        foreach ($data as $item) {
            $channels = DB::table('channels');
            if (isset($item[3])) {
                $channels->where('channels_code', $item[3]);
            }
            $channels = $channels->get();
            foreach ($channels as $channel) {
                $feature = DB::table('features')
                    ->where('features_name', $item[1])
                    ->where('fk_channel', $channel->channels_id)
                    ->first();
                if (!empty($feature)) {
                    if (!isset($arr[$item[2] . '-' . $feature->features_id])) {
                        foreach ($item[4] as $type) {
                            $arr = [
                                'actions_name' => $item[0],
                                'actions_code' => $item[2] .'_'. strtoupper($type),
                                'fk_feature' => $feature->features_id,
                                'actions_type' => $type,
                                'actions_is_active' => 1,
                                'actions_created_at' => time(),
                                'actions_updated_at' => time(),
                            ];
                            array_push($temp, $arr);
                        }
                    }
                }
            }
        }
        if (!empty($temp)) {
            DB::table('actions')->insert($temp);
        }

    }
}
