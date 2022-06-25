<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            "Shopee - Keyword Bidding" => [
                ["View Campaign, Products, Keywords", "M_SHP_KB_VIEW_CPK", ""],
                ["Edit/Create Campaign, Products, Keywords", "M_SHP_KB_EDIT_CPK", "View Campaign, Products, Keywords"],
                ["Add/Create Rules to Products, Keywords", "M_SHP_KB_EDIT_RULE", "Edit/Create Campaign, Products, Keywords"],
                ["MassUpload", "M_SHP_KB_MASSUPLOAD", "Add/Create Rules to Products, Keywords"],
                ["Create, Edit, Remove monthly target of Dashboard", "M_SHP_KB_EDIT_DASHBOARD", "View Campaign, Products, Keywords"],
                ["Download selected Objects", "M_SHP_KB_DOWNLOAD", "View Campaign, Products, Keywords"]
            ],

            "Shopee - Shop Ads" => [
                ["View Shop Ads, Keywords", "M_SHP_SA_VIEW_SAKWD", ""],
                ["Download selected Objects", "M_SHP_SA_DOWNLOAD", "View Shop Ads, Keywords"],
                ["Edit/Create Shop Ads, Keywords", "M_SHP_SA_EDIT_SAKWD", "View Shop Ads, Keywords"],
                ["Create, Edit, Remove monthly target of Dashboard", "M_SHP_SA_EDIT_DASHBOARD", ""],
            ],

            "Lazada - Sponsored Search" => [
                ["View Campaign, Products, Keywords", "M_LZD_SS_VIEW_CPK", ""],
                ["Download selected Objects", "M_LZD_SS_DOWNLOAD", "View Campaign, Products, Keywords"],
                ["Edit/Create Campaign, Products, Keywords", "M_LZD_SS_EDIT_CPK", "View Campaign, Products, Keywords"],
                ["Add/Create Rules to Products, Keywords.", "M_LZD_SS_EDIT_RULE", "Edit/Create Campaign, Products, Keywords"],
                ["MassUpload", "M_LZD_SS_MASSUPLOAD", "Add/Create Rules to Products, Keywords."],
                ["Create, Edit, Remove monthly target of Dashboard", "M_LZD_SS_EDIT_DASHBOARD", ""],
            ],

            "Product" => [
                ["View Product List", "O_PRD_VIEW", ""],
            ],

            "Pricing" => [
                ["View Price Tracker", "O_PRICE_VIEW_PRICE", ""],
                ["Edit Price Tracker", "O_PRICE_EDIT_PRICE", "View Price Tracker"],
                ["View Promotion Tracker, Promotion Details", "O_PRICE_VIEW_PROMOTION_TRACKER&DETAIL", ""],
                ["Edit Promotion", "O_PRICE_EDIT_PROMOTION", "View Promotion Tracker, Promotion Details"],
                ["Approve/ Reject Promotion", "O_PRICE_APPROVE_PROMOTION", "View Promotion Tracker, Promotion Details"]
            ],
        ];

        $temp = [];

        $id = 1;
        foreach ($data as $featureName => $permission) {
            $features = DB::table('features')->where('features_name', $featureName)->get();
            if (!empty($features)) {
                foreach ($features as $feature) {
                    $tempIdPermission = [];
                    foreach ($permission as $item) {
                        $tempIdPermission[$item[0]] = $id;
                        $arr = [
                            'permission_id' => $id,
                            'permission_name' => $item[0],
                            'permission_code' => $item[1],
                            'fk_feature' => $feature->features_id,
                            'permission_inherit' => isset($tempIdPermission[$item[2]]) ? $tempIdPermission[$item[2]] : null,
                            'permission_created_by' => 1,
                            'permission_created_at' => time()
                        ];
                        array_push($temp, $arr);
                        $id = $id + 1;
                    }
                }

            }
        }
        if (!empty($temp)) {
            DB::table('permission')->insert($temp);
        }
    }
}
