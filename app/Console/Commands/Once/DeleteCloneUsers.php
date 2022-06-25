<?php

namespace App\Console\Commands\Once;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteCloneUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:delete-clone-user {{--userId=}}';


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
        $users = DB::table('users')
            ->where('users_email', 'like', 'clone_user_%');
        if (!empty($userId)) {
            $users->where('users_id', $userId);
        }
        $usersId = $users->pluck('users_id');

        DB::beginTransaction();

        try {
            $shopUserIdAcl = DB::table('shop_user')
                ->whereIn('fk_user', $usersId)
                ->where('shop_user_role', 'member')
                ->pluck('shop_user_id');
            if (count($shopUserIdAcl) > 0) {
                DB::table('acl')->whereIn('fk_shop_user', $shopUserIdAcl)->delete();
            }

            DB::table('shop_user')->whereIn('fk_user', $usersId)->delete();

            $users = DB::table('users')
                ->where('users_email', 'like', 'clone_user_%');
            if (!empty($userId)) {
                $users->where('users_id', $userId);
            }
            $users->delete();
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
