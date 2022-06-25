<?php

namespace Database\Seeders;

use App\Models\Sql\Channels;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

//        $data = [
//            [1,'Lazada', 'LAZADA', 1, 1, 0, '{"api_url": "https://api.lazada.vn/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [2,'Tiki', 'TIKI', 1, 1, 0, '{"url": "https://api-sellercenter.tiki.vn/integration", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [3,'Shopee', 'SHOPEE', 1, 1, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [4,'Robin', 'ROBIN', 1, 1, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [5,'Adayroi', 'ADAYROI', 1, 1, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [6,'Vuivui', 'VUIVUI', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [7,'Sendo', 'SENDO', 1, 1, 0, '{"api_url": "https://open.sendo.vn", "secret_key": "9397f820941f37f2d28383cc419ea34632022b54", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [8,'Lotte', 'LOTTE', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [9,'Off Channel', 'OFFCHANNEL', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [10,'Leflair', 'LEFLAIR', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [11,'Akulaku', 'AKULAKU', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [12,'Brand.Com', 'BRAND_COM', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [51,'Lazada', 'LAZADA', 1, 2, 0, '{"api_url": "https://api.lazada.com.ph/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [53,'Shopee', 'SHOPEE', 1, 2, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [59,'Off Channel', 'OFFCHANNEL', 1, 2, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [61,'Akulaku', 'AKULAKU', 1, 2, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [63,'Cliqq', 'CLIQQ', 1, 2, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [101,'Lazada', 'LAZADA', 1, 3, 0, '{"api_url": "https://api.lazada.com.my/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [103,'Shopee', 'SHOPEE', 1, 3, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [109,'Off Channel', 'OFFCHANNEL', 1, 3, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [151,'Lazada', 'LAZADA', 1, 4, 0, '{"api_url": "https://api.lazada.sg/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [159,'Off Channel', 'OFFCHANNEL', 1, 4, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [160,'11st', '11ST', 1, 3, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [161,'Lazada', 'LAZADA', 1, 5, 0, '{"api_url": "https://api.lazada.co.id/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [162,'Shopee', 'SHOPEE', 1, 5, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [163,'Off Channel', 'OFFCHANNEL', 1, 5, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [164,'Haravan', 'HARAVAN', 1, 1, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [165,'Shopee', 'SHOPEE', 1, 4, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [166,'Lazada', 'LAZADA', 1, 6, 0, '{"api_url": "https://api.lazada.co.th/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [167,'Shopee', 'SHOPEE', 1, 6, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [168,'Drinkies', 'DRK', 1, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [169,'SHOPEE', 'SHOPEE', 1, 214, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
//            [213,'TOKOPEDIA', 'TOKOPEDIA', 1, 5, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}']
//        ];
        $data = [
            [1, 'Lazada', 'LAZADA', 1, 1, 0, '{"api_url": "https://api.lazada.vn/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [2, 'Tiki', 'TIKI', 0, 1, 0, '{"url": "https://api-sellercenter.tiki.vn/integration", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [3, 'Shopee', 'SHOPEE', 1, 1, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [4, 'Robin', 'ROBIN', 0, 1, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [5, 'Adayroi', 'ADAYROI', 0, 1, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [6, 'Vuivui', 'VUIVUI', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [7, 'Sendo', 'SENDO', 0, 1, 0, '{"api_url": "https://open.sendo.vn", "secret_key": "9397f820941f37f2d28383cc419ea34632022b54", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [8, 'Lotte', 'LOTTE', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [9, 'Off Channel', 'OFFCHANNEL', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [10, 'Leflair', 'LEFLAIR', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [11, 'Akulaku', 'AKULAKU', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [12, 'Brand.Com', 'BRAND_COM', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [51, 'Lazada', 'LAZADA', 1, 2, 0, '{"api_url": "https://api.lazada.com.ph/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [53, 'Shopee', 'SHOPEE', 1, 2, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [59, 'Off Channel', 'OFFCHANNEL', 0, 2, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [61, 'Akulaku', 'AKULAKU', 0, 2, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [63, 'Cliqq', 'CLIQQ', 0, 2, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [101, 'Lazada', 'LAZADA', 1, 3, 0, '{"api_url": "https://api.lazada.com.my/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [103, 'Shopee', 'SHOPEE', 1, 3, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [109, 'Off Channel', 'OFFCHANNEL', 0, 3, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [151, 'Lazada', 'LAZADA', 1, 4, 0, '{"api_url": "https://api.lazada.sg/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [159, 'Off Channel', 'OFFCHANNEL', 0, 4, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [160, '11st', '11ST', 0, 3, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [161, 'Lazada', 'LAZADA', 1, 5, 0, '{"api_url": "https://api.lazada.co.id/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [162, 'Shopee', 'SHOPEE', 1, 5, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [163, 'Off Channel', 'OFFCHANNEL', 0, 5, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [164, 'Haravan', 'HARAVAN', 0, 1, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [165, 'Shopee', 'SHOPEE', 1, 4, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [166, 'Lazada', 'LAZADA', 1, 6, 0, '{"api_url": "https://api.lazada.co.th/rest", "app_key": "100311", "secret_key": "sZNWjq8SescsigM5RnTyDjPwu0z45hr5", "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [167, 'Shopee', 'SHOPEE', 1, 6, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [168, 'Drinkies', 'DRK', 0, 1, 1, '{"url_callback": "/api/v1/link-shop-channel-callback"}'],
            [169, 'SHOPEE', 'SHOPEE', 0, 214, 0, '{"key": "98fe8338e78ebb2add020a9785cdc894387aa85fc1d9233f330f9fc9a2d02652", "url": "https://partner.shopeemobile.com/api/v1", "auth_url": "https://partner.shopeemobile.com/api/v1/shop/auth_partner", "partner_id": 842799, "url_callback": "/api/v1/link-shop-channel-callback"}'],
            [213, 'TOKOPEDIA', 'TOKOPEDIA', 1, 5, 0, '{"url_callback": "/api/v1/link-shop-channel-callback"}']
        ];
        $arr = DB::table('channels')->pluck(Channels::COL_CHANNELS_NAME, Channels::COL_CHANNELS_CODE);
        $temp = [];
        foreach ($data as $item) {
            if (!isset($arr[$item[1]])) {
                $arr = [
                    Channels::COL_CHANNELS_ID => $item[0],
                    Channels::COL_CHANNELS_NAME => $item[1],
                    Channels::COL_CHANNELS_CODE => $item[2],
                    Channels::COL_CHANNELS_IS_ACTIVE => $item[3],
                    Channels::COL_FK_COUNTRY => $item[4],
                    Channels::COL_CHANNELS_IS_OFFLINE => $item[5],
                    Channels::COL_CHANNELS_CONFIG => $item[6],
                    Channels::COL_CHANNELS_CREATED_AT => time(),
                    Channels::COL_CHANNELS_UPDATED_AT => time(),
                ];
                array_push($temp, $arr);
            }
        }
        if (!empty($temp)) {
            DB::table('channels')->insert($temp);
        }
    }
}
