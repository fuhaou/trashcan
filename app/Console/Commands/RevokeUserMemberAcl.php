<?php

namespace App\Console\Commands;

use App\Models\Sql\Acl;
use App\Models\Sql\ShopUser;
use App\Models\Sql\Users;
use Illuminate\Console\Command;

class RevokeUserMemberAcl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:revoke-member-role-from-shops
                            {user_emails* : list of user email} 
                            {--shop_eids= : list of shop eid to revoke (mandatory). Use ALL to revoke all shops of user. In case of multiple shops, use , to separate (no space). Shop can be active or inactive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke User member role from list of shops';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user_emails = $this->argument('user_emails'); // array
        $shop_eids = $this->option('shop_eids'); // string

        if (is_null($shop_eids)) {
            $this->error('Must have shop_eids to run');
            return 0;
        }
        $shop_eids = explode(',', $shop_eids); // array

        // check user email must be in the system
        $results = Users::query()
            ->whereIn('users_email', $user_emails)
            ->get();
        
        if ($results->count() !== count($user_emails)) {
            $this->error('There is user_email which is not in the system');
            return 0;
        }

        $user_id_mapping = [];
        foreach ($user_emails as $email) {
            $user_id_mapping[$email] = Users::query()
                ->firstWhere('users_email', $email)
                ->users_id; // must be available
        }

        \DB::transaction(function () use ($user_id_mapping, $shop_eids) {
            // remove acl and shop_user
            $shop_user = ShopUser::query()
                ->where('shop_user_role', 'member')
                ->whereIn('fk_user', array_values($user_id_mapping));
            if (!count($shop_eids) == 1 || $shop_eids[0] !== 'ALL') {
                $shop_user->whereIn('fk_shop', $shop_eids);
            }                

            // remove acl first
            Acl::query()
                ->whereIn('fk_shop_user', $shop_user->select('shop_user_id'))
                ->delete();
            
            // remove shop user
            $shop_user->delete();
        });

        $this->info('Successfully revoke');
        return 1;
    }
}
