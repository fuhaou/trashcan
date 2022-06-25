<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class After3PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            "Tokopedia - Product Ads" => [
                ["View Campaign, Products, Keywords", "M_TOK_PA_VIEW_CPK", ""],
                ["Edit/Create Campaign, Products, Keywords", "M_TOK_PA_EDIT_CPK", "View Campaign, Products, Keywords"],
                ["Add/Create Rules to Products, Keywords.", "M_TOK_PA_EDIT_RULE", "Edit/Create Campaign, Products, Keywords"],
                ["Download selected Objects.", "M_TOK_PA_DOWNLOAD", "View Campaign, Products, Keywords"],
            ],
        ];

        $temp = [];
        $id = DB::table('permission')->orderBy('permission_id','desc')->pluck('permission_id')->first();
        foreach ($data as $featureName => $permission) {
            $features = DB::table('features')->where('features_name', $featureName)->get();
            if (!empty($features)) {
                foreach ($features as $feature) {
                    $tempIdPermission = [];
                    foreach ($permission as $item) {
                        $tempIdPermission[$item[0]] = $id + 1;
                        $arr = [
                            'permission_id' => $id + 1,
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
