<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class After2DeletePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = DB::table('permission')
            ->where('permission_code','like','%MASSUPLOAD%')
            ->orWhere('permission_code','like','%EDIT_DASHBOARD%');
        DB::table('acl')->whereIn('fk_permission',$permission->pluck('permission_id'))->delete();
        $permission->delete();
    }
}
