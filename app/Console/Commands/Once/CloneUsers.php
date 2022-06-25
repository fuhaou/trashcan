<?php

namespace App\Console\Commands\Once;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloneUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:clone-user {{--userId=}}';


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
        $userId = $this->option('userId');
        if (empty($userId)){
            throw new \Exception('User Id required');
        }
        $users = DB::table('users')
            ->leftJoin('shop_user', 'fk_user', 'users_id')
            ->leftJoin('company_register_code', 'fk_company_register_code', 'company_register_code_id')
            ->whereNotIn('fk_company', [1,19])
            ->where('company_register_code_is_active', 1)
            ->where('users_is_active', 1)
            ->where('shop_user_is_allocated', 1)
            ->whereNotNull('shop_user_id')
            ->where('users_id', $userId)
            ->get();

        DB::beginTransaction();

        try {
            $pw = md5('12345678');
            $prefixEmail = 'clone_user_';
            $tempUser = [];
            $tempUserId = 0;
            foreach ($users as $item) {
                if (!in_array($item->users_id, $tempUser)) {
                    //create user
                    $tempUserId = DB::table('users')->insertGetId([
                        'users_first_name' => $item->users_first_name,
                        'users_last_name' => $item->users_last_name,
                        'users_email' => $prefixEmail . $item->users_email,
                        'users_password' => $pw,
                        'users_phone' => $item->users_phone,
                        'users_ip' => $item->users_ip,
                        'users_avatar' => $item->users_avatar,
                        'users_remember_token' => $item->users_remember_token,
                        'fk_company_register_code' => $item->fk_company_register_code,
                        'users_is_active' => $item->users_is_active,
                        'users_email_verified_at' => $item->users_email_verified_at,
                        'users_last_login_at' => $item->users_last_login_at,
                        'users_created_at' => $item->users_created_at,
                    ]);
                    array_push($tempUser, $item->users_id);
                }
                //create shop_user
                $shopUserId = DB::table('shop_user')->insertGetId([
                    'fk_shop' => $item->fk_shop,
                    'fk_user' => $tempUserId,
                    'fk_brand' => $item->fk_brand,
                    'shop_user_role' => $item->shop_user_role,
                    'shop_user_is_allocated' => $item->shop_user_is_allocated,
                    'fk_subscription_details' => $item->fk_subscription_details,
                    'shop_user_state' => $item->shop_user_state,
                    'shop_user_created_at' => $item->shop_user_created_at,
                    'shop_user_created_by' => $item->shop_user_created_by,
                    'shop_user_updated_at' => $item->shop_user_updated_at,
                ]);
                if ($item->shop_user_role == 'member') {
                    $dataAcl = DB::table('acl')
                        ->where('fk_shop_user', $item->shop_user_id)
                        ->get();
                    if (!empty($dataAcl)){
                        //create acl
                        $tempAcl = [];
                        foreach ($dataAcl as $acl) {
                            array_push($tempAcl, [
                                'fk_shop_user' => $shopUserId,
                                'fk_permission' => $acl->fk_permission,
                                'acl_is_active' => $acl->acl_is_active,
                                'acl_created_by' => $acl->acl_created_by,
                                'acl_created_at' => $acl->acl_created_at,
                                'acl_updated_by' => $acl->acl_updated_by,
                                'acl_updated_at' => $acl->acl_updated_at
                            ]);
                        }
                        DB::table('acl')->insert($tempAcl);
                    }
                }
            }
            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
        echo "+++DONE+++\n";
        return;
    }
}
