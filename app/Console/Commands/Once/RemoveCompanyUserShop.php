<?php

namespace App\Console\Commands\Once;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveCompanyUserShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:remove-company-user-shop';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        DB::beginTransaction();

        try {
            $dataUserId = DB::table('users')->selectRaw('distinct (users_id)')->where('users_email', 'like', "%epsilo.io%")->pluck('users_id')->toArray();
            $dataCompanyUserId = DB::table('company_user')->selectRaw('distinct (fk_user)')->where('company_user_role', "root")->pluck('fk_user')->toArray();
            $dataShopUserId = DB::table('shop_user')->selectRaw('distinct (fk_user)')->pluck('fk_user')->toArray();

            $dataUserId = array_merge($dataUserId,$dataCompanyUserId);
            $dataUserId = array_merge($dataUserId,$dataShopUserId);

            $shopUserIdAcl = DB::table('shop_user')
                ->whereNotIn('fk_user', $dataUserId)
                ->pluck('shop_user_id');
            if (count($shopUserIdAcl) > 0) {
                DB::table('acl')->whereIn('fk_shop_user', $shopUserIdAcl)->delete();
            }
            DB::table('company_user')->whereNotIn('fk_user', $dataUserId)->delete();
            DB::table('shop_user')->whereNotIn('fk_user', $dataUserId)->delete();

            $users = DB::table('users')
                ->whereNotIn('users_id', $dataUserId);
            $users->delete();

            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
        dd("done") ;
    }
}
