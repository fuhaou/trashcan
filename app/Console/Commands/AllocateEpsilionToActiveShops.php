<?php

namespace App\Console\Commands;

use App\Models\Sql\Acl;
use App\Models\Sql\Partnership;
use App\Models\Sql\Permission;
use App\Models\Sql\Shops;
use App\Models\Sql\ShopUser;
use App\Models\Sql\Users;
use Illuminate\Console\Command;

class AllocateEpsilionToActiveShops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'epsilion:allocate-active-shops 
                            {user_emails* : list of user email} 
                            {--permission= : permission for user, select 1 value from availalble values: READ_ONLY or FULL} 
                            {--overwrite_normal_role=true : overwrite allocation + permission if any, values: true + false. When this option is false, all shops which user is already allocated with permission will be ignored}
                            {--auto_partnership=true : auto set partnership between company of shop and company of user, values: true + false. When this option is false, all shops of company which is not partnership will be ignored}
                            {--shop_eids= : list of shop eid to apply (optional). If empty, apply all active shops. In case of multiple shops, use , to separate (no space). Shop must be active.}
                            {--exclude_shop_eids=* : list of shop eid to exclude (optional)}
                            {--dry_run : dry run only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate Epsilo user to all active shops with some advanced options';

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
        $include_shop_eids = $this->option('shop_eids');
        $exclude_shop_eids = $this->option('exclude_shop_eids'); // array
        $permission = $this->option('permission');
        $overwrite_normal_role = $this->option('overwrite_normal_role');
        $auto_partnership = $this->option('auto_partnership');
        $dry_run = $this->option('dry_run');

        if (is_null($permission)) {
            $this->error('Must have value to depict permission of user on each shop');
            return 0;
        }
        if ($permission !== 'READ_ONLY' && $permission !== 'FULL') {
            $this->error('Value of permission must be READ_ONLY or FULL');
            return 0;
        }
        if ($overwrite_normal_role !== 'true' && $overwrite_normal_role !== 'false') {
            $this->error('Value of overwrite_normal_role must be true or false');
            return 0;
        }
        $overwrite_normal_role = filter_var($overwrite_normal_role, FILTER_VALIDATE_BOOLEAN);

        if ($auto_partnership !== 'true' && $auto_partnership !== 'false') {
            $this->error('Value of auto_partnership must be true or false');
            return 0;
        }
        $auto_partnership = filter_var($auto_partnership, FILTER_VALIDATE_BOOLEAN);

        // user quang@epsilo.io is default user for "created_by" in partnership
        $quang_id = Users::query()
            ->firstWhere('users_email', 'quang@epsilo.io');
        if (is_null($quang_id)) {
            $this->error('Does not have user Quang, big problem');
            return 0;
        }
        $quang_id = $quang_id->users_id;

        // check user email must be Epsilion
        $results = Users::query()
            ->join('company_register_code', 'company_register_code_id', '=', 'fk_company_register_code')
            ->join('companies', 'companies_id', '=', 'fk_company')
            ->whereIn('users_email', $user_emails)
            ->where('companies_name', 'Epsilo')
            ->get();
        
        if ($results->count() !== count($user_emails)) {
            $this->error('There is user_email which is not belong to Epsilo');
            return 0;
        }
        $epsilo_company_id = $results->pluck('companies_id')[0];
        $this->info('Epsilo company id: '.$epsilo_company_id);

        $user_id_mapping = [];
        foreach ($user_emails as $email) {
            $user_id_mapping[$email] = Users::query()
                ->firstWhere('users_email', $email)
                ->users_id; // must be available
        }

        // get all active shops
        $active_shops = Shops::query()
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->where('shops_is_active', true)
            ->select('shops_eid', 'shops_name', 'channels_code', 'countries_code')
            ->get();

        $this->info('Full list of active shops ('.$active_shops->count().')');
        if (!is_null($include_shop_eids)) {
            $include_shop_eids = explode(',', $include_shop_eids); // array
            $this->info('List of shop eid to apply: ['.implode(',', $include_shop_eids).']');

            $active_shops = $active_shops->filter(function($value, $key) use ($include_shop_eids) {
                return in_array($value->shops_eid, $include_shop_eids);
            });
            $this->info('==> List of active shops after filter ('.$active_shops->count().')');
        }
        
        $this->table(
            ['Shop eid', 'Shop name', 'Channel code', 'Country code'],
            $active_shops->toArray(),
        );

        // prequisite: must exclude shops below:
        //    1. shops which doesn't have shop_user
        //    2. shops which doesn't have admin
        //    3. shops belong to more than 1 company
        $this->info('Current list of excluded shops: ['.implode(',', $exclude_shop_eids).']');
        $not_shop_user = Shops::query()
            ->select('shops_eid')
            ->whereNotIn('shops_eid', function ($query) {
                $query->select('fk_shop')
                    ->from('shop_user')
                    ->distinct();
            })
            ->pluck('shops_eid');
        $this->info('List of shops doesn\'t have shop_user: ['.$not_shop_user->implode(',').']');

        $not_admin_shop = ShopUser::query()
            ->select('fk_shop')
            ->distinct()
            ->whereIn('fk_shop', function ($query1) {
                $query1->select('shops_eid')
                    ->from('shops')
                    ->where('shops_is_active', true)
                    ->whereNotIn('shops_eid', function ($query2) {
                        $query2->select('fk_shop')
                            ->from('shop_user')
                            ->where('shop_user_role', 'admin')
                            ->distinct();
                    });
            })
            ->pluck('fk_shop');
        $this->info('List of shops doesn\'t have admin: ['.$not_admin_shop->implode(',').']');

        $more_one_company = Shops::query()
            ->select('shops_eid')
            ->join('shop_user', 'shops.shops_eid', '=', 'shop_user.fk_shop')
            ->join('subscription_details', 'subscription_details.subscription_details_id', '=', 'shop_user.fk_subscription_details')
            ->join('company_subscription_code', 'company_subscription_code.company_subscription_code_id', '=', 'subscription_details.fk_company_subscription_code')
            ->join('companies', 'companies.companies_id', '=', 'company_subscription_code.fk_company')
            ->where('shops_is_active', true)
            ->groupBy('shops_eid')
            ->havingRaw('count(distinct(companies_id)) > 1')
            ->pluck('shops_eid');
        $this->info('List of shops belong to more than 1 company: ['.$more_one_company->implode(',').']');

        $exclude_shop_eids = array_unique(array_merge($exclude_shop_eids, $not_shop_user->all(), $not_admin_shop->all(), $more_one_company->all()));
        $this->info('==> List of excluded shops after prequisite check step: ['.implode(',', $exclude_shop_eids).']');

        $no_partner_Epsilo = Shops::query()
                ->select('shops_eid')
                ->addSelect(\DB::raw('group_concat(distinct(companies_id)) as company_id')) // already sure that 1 shop belong to 1 company
                ->addSelect(\DB::raw('group_concat(distinct(companies_name)) as company_name')) // already sure that 1 shop belong to 1 company
                ->join('shop_user', 'shops.shops_eid', '=', 'shop_user.fk_shop')
                ->join('subscription_details', 'subscription_details.subscription_details_id', '=', 'shop_user.fk_subscription_details')
                ->join('company_subscription_code', 'company_subscription_code.company_subscription_code_id', '=', 'subscription_details.fk_company_subscription_code')
                ->join('companies', 'companies.companies_id', '=', 'company_subscription_code.fk_company')
                ->leftJoin('partnership', function ($join) use ($epsilo_company_id) {
                    $join->on('partnership_from', '=', 'companies_id');
                    $join->on('partnership_to', '=', \DB::raw($epsilo_company_id));
                })
                ->whereNotIn('shops_eid', $exclude_shop_eids)
                ->whereNotIn('shops_eid', function ($query) use ($epsilo_company_id) {
                    // exclude Epsilo shops also
                    $query->select('shops_eid')
                        ->from('shops')
                        ->join('shop_user', 'shops.shops_eid', '=', 'shop_user.fk_shop')
                        ->join('subscription_details', 'subscription_details.subscription_details_id', '=', 'shop_user.fk_subscription_details')
                        ->join('company_subscription_code', 'company_subscription_code.company_subscription_code_id', '=', 'subscription_details.fk_company_subscription_code')
                        ->join('companies', 'companies.companies_id', '=', 'company_subscription_code.fk_company')
                        ->where('companies_id', $epsilo_company_id)
                        ->get();
                })
                ->groupBy('shops_eid')
                ->havingRaw('count(distinct(partnership_from)) = 0');
        if (is_array($include_shop_eids)) {
            $no_partner_Epsilo = $no_partner_Epsilo
                ->whereIn('shops_eid', $active_shops->pluck('shops_eid'));
        }
        $this->info('List of shops doesn\'t have partnership with Epsilo: ['.$no_partner_Epsilo->get()->implode('shops_eid', ',').']');

        // check partnership
        if (!$auto_partnership && $no_partner_Epsilo->count() > 0) {
            $this->info('auto_partnership is OFF');
            // auto exclude shops of company which is not partnership with Epsilo
            $exclude_shop_eids = array_unique(array_merge($exclude_shop_eids, $no_partner_Epsilo->pluck('shops_eid')->all()));
            $this->info('==> List of excluded shops after auto_partnership=false: ['.implode(',', $exclude_shop_eids).']');
        }

        // common list shops to allocate to user
        $common_shops = $active_shops->filter(function($value, $key) use ($exclude_shop_eids) {
            return !in_array($value->shops_eid, $exclude_shop_eids);
        });
        $common_eids = $common_shops->pluck('shops_eid')->all();

        // get list of admin id + subscription detail id of each shop
        $shop_admin_feature = ShopUser::query()
            ->select('fk_shop', 'fk_user', 'fk_subscription_details')
            ->addSelect(\DB::raw('group_concat(features_code) feature, group_concat(shops_name) as shop_name, group_concat(channels_code) as channel_code, group_concat(countries_code) as country_code'))
            ->join('subscription_details', 'subscription_details_id', '=', 'fk_subscription_details')
            ->join('features', 'features_id', '=', 'fk_features')
            ->join('shops', 'shops_eid', '=', 'shop_user.fk_shop')
            ->join('channels', 'channels_id', '=', 'shops.fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->where('shop_user_role', 'admin')
            ->whereIn('fk_shop', $common_eids)
            ->groupBy('fk_shop', 'fk_user', 'fk_subscription_details')
            ->get()
            ->groupBy('fk_shop')
            ->transform(function ($item, $key) {
                $features = [];
                foreach ($item as $data) {
                    $features[] = $data->fk_user.'|'.$data->fk_subscription_details.'|'.$data->feature;
                }
                return [
                    'shop_eid' => $item[0]->fk_shop,
                    'shop_name' => $item[0]->shop_name,
                    'channel_code' => $item[0]->channel_code,
                    'country_code' => $item[0]->country_code,
                    'features' => implode('+', $features),
                ];
            });
        $this->info('List shops will be allocated to user ('.$common_shops->count().')');
        $this->table(
            ['Shop eid', 'Shop name', 'Channel code', 'Country code', 'Features'],
            $shop_admin_feature->toArray(),
        );

        // permission list
        $permission_list = []; // array of features of countries
        foreach ($shop_admin_feature as $shop) {
            $features = explode('+', $shop['features']); // array
            foreach ($features as $feature) {
                $data = explode('|', $feature);
                if (!array_key_exists($data[2], $permission_list)) {
                    $query = Permission::query()
                        ->join('features', 'features_id', '=', 'fk_feature')
                        ->join('channels', 'channels_id', '=', 'fk_channel')
                        ->join('countries', 'countries_id', '=', 'fk_country')
                        ->where('features_code', $data[2])
                        ->select('countries_code', 'permission_id', 'permission_code');
                    if ($permission == 'READ_ONLY') {
                        $query->where('permission_code', 'like', $data[2].'_VIEW%');
                    }

                    $permission_list[$data[2]] = $query->get()
                        ->groupBy('countries_code');
                }
            }
        }

        $user_exclude_acl = [];
        $user_exclude_admin = [];

        foreach ($user_emails as $email) {
            // FOR SPECIFIC USER:
            // a. list of shop for each user already have permission (has shop_user with role member and acl)
            $user_exclude_acl[$email] = ShopUser::query()
                ->select('fk_shop')
                ->leftJoin('acl', 'fk_shop_user', '=', 'shop_user_id')
                ->where('shop_user_role', 'member')
                ->where('fk_user', $user_id_mapping[$email])
                ->whereIn('fk_shop', $common_eids)
                ->groupBy('fk_user', 'fk_shop')
                ->havingRaw('count(distinct(fk_permission)) > 0')
                ->distinct()
                ->pluck('fk_shop')
                ->all();

            // b. check user is admin of shop to exclude
            $user_exclude_admin[$email] = ShopUser::query()
                ->select('fk_shop')
                ->where('shop_user_role', 'admin')
                ->where('fk_user', $user_id_mapping[$email])
                ->whereIn('fk_shop', $common_eids)
                ->distinct()
                ->pluck('fk_shop')
                ->all();
        }

        \DB::transaction(function () use ($user_emails, $auto_partnership, $overwrite_normal_role, $permission_list,
                                    $no_partner_Epsilo, $shop_admin_feature, $epsilo_company_id, $quang_id,
                                    $user_id_mapping, $user_exclude_acl, $user_exclude_admin, $dry_run) {
            $verb = '';
            if ($dry_run) {
                $verb = 'DRY ';
            }
            // set partnership if $auto_partnership = true
            if ($auto_partnership && $no_partner_Epsilo->count() > 0) {
                $this->info('auto_partnership is ON');
                $this->info($verb.'Set partnership with Epsilo for these companies below');
                $this->table(
                    ['Shop eid', 'Company id', 'Company name'],
                    $no_partner_Epsilo->toArray(),
                );

                // set partnership
                if (!$dry_run) {
                    $companies = $no_partner_Epsilo->pluck('company_id')->unique()->all();
                    foreach ($companies as $company) {
                        Partnership::query()
                            ->insert([
                                [
                                    Partnership::COL_PARTNERSHIP_FROM => $company,
                                    Partnership::COL_PARTNERSHIP_TO => $epsilo_company_id,
                                    Partnership::COL_PARTNERSHIP_CREATED_AT => time(),
                                    Partnership::COL_PARTNERSHIP_UPDATED_AT => time(),
                                    Partnership::COL_PARTNERSHIP_CREATED_BY => $quang_id,
                                ],
                                [
                                    Partnership::COL_PARTNERSHIP_FROM => $epsilo_company_id,
                                    Partnership::COL_PARTNERSHIP_TO => $company,
                                    Partnership::COL_PARTNERSHIP_CREATED_AT => time(),
                                    Partnership::COL_PARTNERSHIP_UPDATED_AT => time(),
                                    Partnership::COL_PARTNERSHIP_CREATED_BY => $quang_id,
                                ]
                            ]);
                    }
                }
                $this->info('Done '.$verb.'set partnership with Epsilo');
            }

            // for each user
            foreach ($user_emails as $email) {
                $this->info('****  Start processing user '.$email);
                // exclude shop which user is admin
                $user_shops = $shop_admin_feature;
                if (count($user_exclude_admin[$email]) > 0) {
                    $this->info('-- Exclude shops which user is admin: ['.implode(',', $user_exclude_admin[$email]).']');
                    $user_shops = $shop_admin_feature->filter(function($value, $key) use ($user_exclude_admin, $email) {
                        return !in_array($value['shop_eid'], $user_exclude_admin[$email]);
                    });
                }
                $this->info('-- List shops will be allocated to user '.$email.' ('.$user_shops->count().')');
                $this->table(
                    ['Shop eid', 'Shop name', 'Channel code', 'Country code', 'Features'],
                    $user_shops->toArray(),
                );
                
                if ($overwrite_normal_role) {
                    $this->info('-- overwrite_normal_role is ON');
                    if (count($user_exclude_acl[$email]) > 0) {
                        // remove acl of users for shop in $user_exclude_acl
                        $this->info('-- '.$verb.'Remove acl of user '.$email.' from list shops: ['.implode(',', $user_exclude_acl[$email]).']');
                        if (!$dry_run) {
                            Acl::query()
                                ->whereIn('fk_shop_user', function($query) use ($user_id_mapping, $email, $user_exclude_acl) {
                                    $query->select('shop_user_id')
                                        ->from('shop_user')
                                        ->where('fk_user', $user_id_mapping[$email])
                                        ->whereIn('fk_shop', $user_exclude_acl[$email])
                                        ->where('shop_user_role', 'member')
                                        ->get();
                                })
                                ->delete();
                        }
                    }
                } else {
                    $this->info('-- overwrite_normal_role is OFF');
                    if (count($user_exclude_acl[$email]) > 0) {
                        $this->info('-- Exclude shops which user is member and has acl: ['.implode(',', $user_exclude_acl[$email]).']');
                        $user_shops = $user_shops->filter(function($value, $key) use ($user_exclude_acl, $email) {
                            return !in_array($value['shop_eid'], $user_exclude_acl[$email]);
                        });
                        $this->info('-- List shops will be allocated to user '.$email.' after excluding ('.$user_shops->count().')');
                        $this->table(
                            ['Shop eid', 'Shop name', 'Channel code', 'Country code', 'Features'],
                            $user_shops->toArray(),
                        );
                    }
                }

                // Upsert shop_user (member role) + add acl
                if (!$dry_run) {
                    $current_time = time();
                    foreach ($user_shops as $shop) {
                        $features = explode('+', $shop['features']); // array
                        foreach ($features as $feature) {
                            $data = explode('|', $feature);
                            // upsert shop_user
                            ShopUser::query()
                                ->updateOrInsert(
                                    [
                                        'fk_shop' => $shop['shop_eid'],
                                        'fk_user' => $user_id_mapping[$email],
                                        'fk_subscription_details' => $data[1],
                                        'shop_user_role' => 'member',
                                    ],
                                    [
                                        'fk_shop' => $shop['shop_eid'],
                                        'fk_user' => $user_id_mapping[$email],
                                        'fk_subscription_details' => $data[1],
                                        'shop_user_role' => 'member',
                                        'shop_user_created_by' => $data[0],
                                        'shop_user_created_at' => $current_time,
                                        'shop_user_updated_at' => $current_time,
                                    ]
                                );
                            // insert ACL
                            $shop_user = ShopUser::query()
                                ->firstWhere([
                                    'fk_shop' => $shop['shop_eid'],
                                    'fk_user' => $user_id_mapping[$email],
                                    'fk_subscription_details' => $data[1],
                                    'shop_user_role' => 'member',
                                ]);
                            if ($shop_user) {
                                $acls = [];
                                $list = $permission_list[$data[2]]->get($shop['country_code'], collect()); // this can be empty list

                                foreach ($list as $acl) {
                                    $acls[] = [
                                        'fk_shop_user' => $shop_user->shop_user_id,
                                        'fk_permission' => $acl->permission_id,
                                        'acl_is_active' => 1,
                                        'acl_created_by' => $data[0],
                                        'acl_created_at' => $current_time,
                                    ];
                                }
                                if (!empty($acls)) {
                                    Acl::query()
                                        ->insert($acls);
                                }
                            }
                        }
                    }
                }

                // add shop_user with role member + permission in acl
                $this->info('****  End user '.$email);
            }
        });

        $this->info('Successfully allocate');
        return 1;
    }
}
