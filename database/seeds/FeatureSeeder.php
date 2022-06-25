<?php

namespace Database\Seeders;

use App\Models\Sql\Channels;
use App\Models\Sql\Features;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [1, "Shopee - Keyword Bidding", "M_SHP_KB", "SHOPEE"],
            [1, "Shopee - Shop Ads", "M_SHP_SA", "SHOPEE"],
            [1, "Shopee - Targetting Ads", "M_SHP_TA", "SHOPEE"],
            [1, "Lazada - Sponsored Search", "M_LZD_SS", "LAZADA"],
            [1, "Tokopedia - Product Ads", "M_TOK_PA", "TOKOPEDIA"],
            [1, "Tokopedia - Headline Ads", "M_TOK_HA", "TOKOPEDIA"],
            [2, "Product", "O_PRD"],
            [2, "Inventory", "O_INVTR"],
            [2, "Pricing", "O_PRICE"],
            [2, "Stock", "O_STOCK"],
            [3, "Insight", "I_INSIGHT"]
        ];
        $features = DB::table('features')->get();
        $arr = [];
        foreach ($features as $feature) {
            $arr[$feature->{Features::COL_FK_GROUP_FEATURE} . '-' . $feature->{Features::COL_FK_CHANNEL} . '-' . $feature->{Features::COL_FEATURES_CODE}] = $feature->{Features::COL_FEATURES_NAME};
        }
        $temp = [];
        foreach ($data as $item) {
            $channels = DB::table('channels');
            if (isset($item[3])) {
                $channels->where('channels_code', $item[3]);
            }
            $channels = $channels->get();
            foreach ($channels as $channel) {
                if (!isset($arr[$item[0] . '-' . $channel->{Channels::COL_CHANNELS_ID} . '-' . $item[2]])) {
                    $arr = [
                        Features::COL_FEATURES_NAME => $item[1],
                        Features::COL_FEATURES_CODE => $item[2],
                        Features::COL_FEATURES_IS_ACTIVE => 1,
                        Features::COL_FK_GROUP_FEATURE => $item[0],
                        Features::COL_FK_CHANNEL => $channel->{Channels::COL_CHANNELS_ID},
                        Features::COL_FEATURES_CREATED_AT => time(),
                        Features::COL_FEATURES_UPDATED_AT => time(),
                    ];
                    array_push($temp, $arr);
                }
            }
        }
        if (!empty($temp)) {
            DB::table('features')->insert($temp);
        }
    }
}
