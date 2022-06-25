<?php

namespace App\Console\Commands;

use App\Models\Sql\Acl;
use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Features;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use Illuminate\Console\Command;

class RemoveFeaturesFromSubscriptionCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:remove-feature
                            {subscription_codes* : list of subscription code will be updated}
                            {--feature_codes= : list of feature code to remove (must have). In case of multiple features, use , to separate (no space).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove features from subscription codes (remove also ACL)';

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

        if (is_null($feature_codes)) {
            $this->error('Must have value to depict feature codes to remove');
            return 0;
        }
        $feature_codes = array_unique(explode(',', $feature_codes)); // array

        // get list map of feature code and feature id
        $feature_ids = Features::query()
            ->select('features_id')
            ->whereIn('features_code', $feature_codes)
            ->pluck('features_id');

        // get list of company_subscription_code_id
        $sub_ids = CompanySubscriptionCode::query()
            ->select('company_subscription_code_id')
            ->whereIn('company_subscription_code_value', $sub_codes)
            ->pluck('company_subscription_code_id');

        // remove from subscription_details, shop_user (admin + member), acl
        \DB::transaction(function () use ($sub_ids, $feature_ids) {
            $sub_details = SubscriptionDetails::query()
                ->whereIn('fk_company_subscription_code', $sub_ids)
                ->whereIn('fk_features', $feature_ids)
                ->select('subscription_details_id');

            $shop_user = ShopUser::query() // admin + member
                ->whereIn('fk_subscription_details', $sub_details)
                ->select('shop_user_id');

            Acl::query()
                ->whereIn('fk_shop_user', $shop_user)
                ->delete();

            $shop_user->delete();
            $sub_details->delete();
        });

        $this->info('Removed successfully');
        return 1;
    }
}
