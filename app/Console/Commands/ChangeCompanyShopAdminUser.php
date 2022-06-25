<?php

namespace App\Console\Commands;

use App\Models\Sql\Acl;
use App\Models\Sql\Companies;
use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\CompanyUser;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use App\Models\Sql\Users;
use Illuminate\Console\Command;

class ChangeCompanyShopAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:change-shop-admin-user
                            {company_name : company will be updated}
                            {new_admin_user_email : new admin user email}
                            {--shop_eids= : list shops separated by colon. Use ALL if apply all shops of company}
                            {--update_history=true : update historical data (created by, updated by), value: true or false}
                            {--old_admin_user_email= : old admin user to replace}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change company shop admin user';

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
        $company_name = $this->argument('company_name');
        $new_admin_user_email = $this->argument('new_admin_user_email');
        $shop_eids = $this->option('shop_eids');
        $update_history = $this->option('update_history');
        $old_admin_user_email = $this->option('old_admin_user_email');

        // first, get company id
        $company = Companies::query()
            ->firstWhere('companies_name', $company_name);

        if (is_null($company_name)) {
            $this->error('Company name '.$company_name.' is not existed');
            return 0;
        }

        if ($update_history !== 'true' && $update_history !== 'false') {
            $this->error('Value of update_history must be true or false');
            return 0;
        }
        $update_history = filter_var($update_history, FILTER_VALIDATE_BOOLEAN);

        // get list of shops of company
        $list_shops = ShopUser::query()
            ->join(SubscriptionDetails::TABLE_NAME, 'subscription_details_id', 'fk_subscription_details')
            ->join(CompanySubscriptionCode::TABLE_NAME, 'company_subscription_code_id', 'fk_company_subscription_code')
            ->join(Companies::TABLE_NAME, 'companies_id', 'fk_company')
            ->where('companies_name', $company_name)
            ->select('fk_shop')
            ->distinct()
            ->pluck('fk_shop')
            ->all();

        if ($shop_eids != 'ALL') {
            // detail list, compare with $list_shops
            $shop_eids = array_map('intval', explode(',', $shop_eids)); // array
            if (collect($shop_eids)->diff($list_shops)->count() > 0) {
                $this->error('shop list have some shops not belong to company '.$company_name);
                return 0;
            }
            $list_shops = $shop_eids;
        }

        if (empty($list_shops)) {
            $this->info('Company does not have shop, stop');
            return 1;
        }
        $this->info('List shops of company to update: ['.implode(',', $list_shops).']');

        // check $new_admin_user_email belongs to company
        $member_user = CompanyUser::query()
            ->join(Users::TABLE_NAME, 'users_id', 'fk_user')
            ->firstWhere([
                'users_email' => $new_admin_user_email,
                'fk_company' => $company->companies_id,
            ]);
        if (is_null($member_user)) {
            $this->error('User '.$new_admin_user_email.' is not member user of company '.$company_name);
            return 0;
        }

        $old_member_user = null;
        if (!is_null($old_admin_user_email)) {
            // check $old_admin_user_email is current member of company and admin of shops of company
            $old_member_user = CompanyUser::query()
                ->join(Users::TABLE_NAME, 'users_id', 'fk_user')
                ->firstWhere([
                    'users_email' => $old_admin_user_email,
                    'fk_company' => $company->companies_id,
                ]);
            if (is_null($old_member_user)) {
                $this->error('User '.$old_admin_user_email.' is not member user of company '.$company_name);
                return 0;
            }

            $old_admin_shops = ShopUser::query()
                ->whereIn('fk_shop', $list_shops)
                ->where([
                    'fk_user' => $old_member_user->users_id,
                    'shop_user_role' => 'admin',
                ])
                ->get();
            if (empty($old_admin_shops)) {
                $this->info('old_admin_user_email does not have any admin shops, stop');
                return 0;
            }
        }

        // all condition fine, replace it
        \DB::transaction(function () use ($list_shops, $member_user, $old_member_user, $update_history) {
            // delete acl first
            $deleted_shop_users = ShopUser::query()
                ->whereIn('fk_shop', $list_shops)
                ->where([
                    'shop_user_role' => 'member',
                    'fk_user' => $member_user->users_id,
                ])
                ->select('shop_user_id');
            Acl::query()
                ->whereIn('fk_shop_user', $deleted_shop_users)
                ->delete();
            // delete relationship of new_admin_user_email and list shops with role member
            $deleted_shop_users->delete();

            // update to admin
            $query = ShopUser::query()
                ->whereIn('fk_shop', $list_shops)
                ->where([
                    'shop_user_role' => 'admin',
                ]);
            if ($old_member_user) {
                $query->where('fk_user', $old_member_user->users_id);
            }
            $update_fields = [
                'fk_user' => $member_user->users_id,
            ];
            if ($update_history) {
                $update_fields['shop_user_created_by'] = $member_user->users_id;
            }
            $old_admin_data = $query->get();

            $query->update($update_fields);

            // update shop_user_updated_by
            if ($update_history) {
                $query->whereNotNull('shop_user_updated_by')
                    ->update([
                        'shop_user_updated_by' => $member_user->users_id,
                    ]);
            }

            // add member user based on $old_admin_data
            $data = [];
            $current_time = time();
            foreach ($old_admin_data as $old) {
                if ($old->fk_user != $member_user->users_id) {
                    $data[] = [
                        'fk_shop' => $old->fk_shop,
                        'fk_user' => $old->fk_user,
                        'shop_user_role' => 'member',
                        'shop_user_is_allocated' => true,
                        'fk_subscription_details' => $old->fk_subscription_details,
                        'shop_user_state' => $old->shop_user_state,
                        'shop_user_created_by' => $member_user->users_id,
                        'shop_user_created_at' => $current_time,
                    ];
                }
            }
            if (!empty($data)) {
                ShopUser::query()
                    ->insert($data);
            }
            // relating to permissions for member user, please run 'user:allocate-active-shops' command to add
        });
        $this->info('Done');
        return 1;
    }
}
