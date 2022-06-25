<?php

namespace App\Console\Commands;

use App\Models\Sql\Channels;
use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Features;
use App\Models\Sql\GroupFeatures;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use Illuminate\Console\Command;

class AddFeaturesIntoSubscriptionCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:add-feature
                            {subscription_codes* : list of subscription code will be updated}
                            {--new_feature= : create new feature (optional), must be in format: <group_feature_code>|<feature_name>|<feature_code>|<channel>. If feature_code is already existed, raise error}
                            {--feature_codes= : list of feature code to add (must have). In case of multiple features, use , to separate (no space).}
                            {--skip= : 3 values: SKIP, SHOP_USER and NONE. SKIP: Skip if feature already in subscription code. SHOP_USER: allow subscription code have feature in subscription_details, will upsert shop_user data. NONE: raise error if subscription code already contains feature}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add more features into subscription codes (keep same quota, expire time, admin from old feature). Must have active feature in subscription code before.';

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
        $sub_codes = $this->argument('subscription_codes'); // array
        $feature_codes = $this->option('feature_codes');
        $new_feature = $this->option('new_feature'); // <group_feature_code>|<feature_name>|<feature_code>|<channel>
        $skip = $this->option('skip');

        if (is_null($feature_codes)) {
            $this->error('Must have value to depict feature codes to add');
            return 0;
        }
        if (is_null($skip)) {
            $this->error('Must have value to depict skip otion');
            return 0;
        }
        if (!in_array($skip, ['SKIP', 'NONE', 'SHOP_USER'])) {
            $this->error('Value of skip must be: SKIP, NONE or SHOP_USER');
            return 0;
        }
        $feature_codes = array_unique(explode(',', $feature_codes)); // array

        $new_feature_input = [];
        $current_time = time();
        if (!is_null($new_feature)) {
            $groups = explode('|', $new_feature); // array
            // must have 4 items
            if (count($groups) != 4) {
                $this->error('new_feature must be in format: <group_feature_code>|<feature_name>|<feature_code>|<channel>');
                return 0;
            }
            // check group feature code
            $group_feature = GroupFeatures::query()
                ->firstWhere('group_features_code', $groups[0]);
            if (is_null($group_feature)) {
                $this->error('group feature code in new_feature is not existed');
                return 0;
            }
            // check length of feature name must be > 0
            if (strlen($groups[1]) == 0) {
                $this->error('feature name in new_feature is empty');
                return 0;
            }
            // check feature code existed
            $feature = Features::query()
                ->firstWhere('features_code', $groups[2]);
            if (!is_null($feature)) {
                $this->error('feature code in new_feature is already existed');
                return 0;
            }
            // check channel
            $channels = Channels::query()
                ->where('channels_code', $groups[3])
                ->get();
            if (is_null($channels)) {
                $this->error('channel code in new_feature is not existed');
                return 0;
            }

            // everything is ok, create array of new feature will be inserted
            foreach ($channels as $channel) {
                $new_feature_input[] = [
                    'features_name' => $groups[1],
                    'features_code' => $groups[2],
                    'fk_group_feature' => $group_feature->group_features_id,
                    'fk_channel' => $channel->channels_id,
                    'features_is_active' => true,
                    'features_created_at' => $current_time,
                    'features_updated_at' => $current_time,
                ];
            }
        }

        // get list of company_subscription_code_id
        $sub_ids = CompanySubscriptionCode::query()
            ->select('company_subscription_code_id')
            ->whereIn('company_subscription_code_value', $sub_codes)
            ->pluck('company_subscription_code_id');

        // get list of existing subscription details
        $existing_sub_details = SubscriptionDetails::query()
                ->whereIn('fk_company_subscription_code', $sub_ids)
                ->where('subscription_details_is_active', true)
                ->get();
        $group_sub_details = $existing_sub_details
                ->groupBy('fk_company_subscription_code');
        
        foreach ($sub_ids as $sub_id) {
            if ($group_sub_details->get($sub_id, collect())->count() == 0) {
                $this->error('There is subscription code which doesn\'t contain any feature');
                return 0;
            }
        }

        // get list map of feature code and feature id
        $feature_ids = Features::query()
            ->select('features_id')
            ->whereIn('features_code', $feature_codes)
            ->pluck('features_id');

        // check subscription detail existed or not
        $check_sub_details = SubscriptionDetails::query()
            ->whereIn('fk_company_subscription_code', $sub_ids)
            ->whereIn('fk_features', $feature_ids)
            ->where('subscription_details_is_active', true)
            ->get();

        // array will be used to create relationship between subscription code
        // and feature in subscription_details table
        $feature_subscriptions = $feature_ids;
        if ($check_sub_details->count() > 0) {
            if ($skip == 'NONE') {
                $this->error('Subscription code already contains feature code, exit');
                return 0;
            }
            if ($skip == 'SKIP') {
                // exclude completely
                $this->info('Exclude these features from adding due to skip ON: '.$check_sub_details->pluck('fk_features')->implode(','));
                $feature_ids = $feature_ids->diff($check_sub_details
                    ->pluck('fk_features')
                    ->all());
                $feature_subscriptions = $feature_ids;
            }
            if ($skip == 'SHOP_USER') {
                // exclude creating relationship between subscription code and feature
                $feature_subscriptions = $feature_ids->diff($check_sub_details
                    ->pluck('fk_features')
                    ->all());
            }
        }

        // query distinct shop + user (admin + member) from shop_user table
        $existing_shop_users = ShopUser::query()
            ->join('shops', 'shops_eid', '=', 'shop_user.fk_shop')
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->join('subscription_details', 'subscription_details_id', '=', 'fk_subscription_details')
            ->select('fk_shop', 'fk_user', 'shop_user_role')
            ->addSelect(\DB::raw('CONCAT(fk_company_subscription_code, \'-\',channels_code, \'-\', countries_code) as channel_country'))
            ->distinct()
            ->whereIn('fk_subscription_details', $existing_sub_details->pluck('subscription_details_id'))
            ->get();

        $admins = [];
        foreach ($existing_shop_users as $shop_user) {
            if ($shop_user->shop_user_role != 'admin') {
                continue;
            }
            // get the first one
            if (!array_key_exists($shop_user->fk_shop, $admins)) {
                $admins[$shop_user->fk_shop] = $shop_user->fk_user;
            }
        }

        // add new subscription_details, shop_user (admin + member)
        \DB::transaction(function () use ($sub_ids, $feature_ids, $group_sub_details,
                                        $admins, $new_feature_input, $feature_subscriptions,
                                        $current_time, $existing_shop_users) {
            
            if (!empty($new_feature_input)) {
                Features::query()
                    ->insert($new_feature_input);

                // add new feature into $feature_ids
                $new_features = Features::query()
                    ->where('features_code', $new_feature_input[0]['features_code'])
                    ->select('features_id')
                    ->pluck('features_id');
                $feature_ids = $feature_ids->concat($new_features->all());
            }

            $subs = [];
            foreach ($sub_ids as $sub_id) {
                foreach ($feature_subscriptions as $feature_id) {
                    $subs[] = [
                        'fk_features' => $feature_id,
                        'fk_company_subscription_code' => $sub_id,
                        'subscription_details_quota_shop' => $group_sub_details->get($sub_id)->get(0)->subscription_details_quota_shop,
                        'subscription_details_expire_time' => $group_sub_details->get($sub_id)->get(0)->subscription_details_expire_time,
                        'subscription_details_is_active' => true,
                        'subscription_details_created_at' => $current_time,
                    ];
                }
            }
            SubscriptionDetails::query()
                ->insert($subs);

            // query about new inserted subscription_details_id
            $new_sub_details = SubscriptionDetails::query()
                ->join('features', 'features_id', '=', 'fk_features')
                ->join('channels', 'channels_id', '=', 'fk_channel')
                ->join('countries', 'countries_id', '=', 'fk_country')
                ->whereIn('fk_company_subscription_code', $sub_ids)
                ->whereIn('fk_features', $feature_ids)
                ->select('subscription_details_id')
                ->addSelect(\DB::raw('CONCAT(fk_company_subscription_code, \'-\',channels_code, \'-\', countries_code) as channel_country'))
                ->pluck('subscription_details_id', 'channel_country');

            // upsert shop user (admin + member)
            foreach ($existing_shop_users as $shop_user) {
                if (!is_null($new_sub_details->get($shop_user->channel_country))) {
                    ShopUser::query()
                        ->upsert([
                        [
                            'fk_shop' => $shop_user->fk_shop,
                            'fk_user' => $shop_user->fk_user,
                            'shop_user_role' => $shop_user->shop_user_role,
                            'shop_user_is_allocated' => true,
                            'fk_subscription_details' => $new_sub_details->get($shop_user->channel_country),
                            'shop_user_created_at' => $current_time,
                            'shop_user_created_by' => $admins[$shop_user->fk_shop],
                        ]
                    ], ['fk_shop', 'fk_user', 'fk_subscription_details']);
                }
            }
        });

        $this->info('Added successfully');
        return 1;
    }
}
