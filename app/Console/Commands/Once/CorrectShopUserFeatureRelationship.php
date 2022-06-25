<?php

namespace App\Console\Commands\Once;

use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Features;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use Illuminate\Console\Command;

/**
 *
 */
class CorrectShopUserFeatureRelationship extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:correct-shop-user-feature';


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
        // remove 'Unilever' from Epsilo shops (4215) - sub code: SUNIMIGRATE
        ShopUser::query()
            ->where([
                'shop_user_id' => 1361,
            ])
            ->delete();

        // replace 'Krizia' sub code (S10KRZEPSILO) from AAD shops (4256)
        ShopUser::query()
            ->where([
                'shop_user_id' => 7984,
            ])
            ->update([
                'fk_subscription_details' => 830
            ]);

        // wrong 'Zenyum' company / 'SZENMIGRATE' sub code from other shops
        // correct Zenyum shops: 4213, 4214
        // query distinct shop + user (admin + member) from shop_user table
        $features = Features::query()
                ->where('features_code', 'M_LZD_SP')
                ->select('features_id');
        
        $sub_codes = CompanySubscriptionCode::query()
                ->where('company_subscription_code_value', 'SZENMIGRATE')
                ->select('company_subscription_code_id');

        $wrong_sub_details_candidates = SubscriptionDetails::query()
                ->whereIn('fk_features', $features)
                ->whereIn('fk_company_subscription_code', $sub_codes)
                ->select('subscription_details_id');

        $wrong_candidates = ShopUser::query()
            ->join('shops', 'shops_eid', '=', 'shop_user.fk_shop')
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->join('subscription_details', 'subscription_details_id', '=', 'fk_subscription_details')
            ->select('shop_user_id', 'fk_shop', 'fk_user', 'fk_subscription_details')
            ->addSelect(\DB::raw('CONCAT(fk_company_subscription_code, \'-\',channels_code, \'-\', countries_code) as channel_country'))
            ->distinct()
            ->whereIn('fk_subscription_details', $wrong_sub_details_candidates)
            ->whereNotIn('fk_shop', [4213, 4214])
            ->get();

        $correct_group = ShopUser::query()
            ->join('shops', 'shops_eid', '=', 'shop_user.fk_shop')
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->join('subscription_details', 'subscription_details_id', '=', 'fk_subscription_details')
            ->select('fk_shop')
            ->addSelect(\DB::raw('CONCAT(fk_company_subscription_code, \'-\',channels_code, \'-\', countries_code) as channel_country'))
            ->distinct()
            ->whereNotIn('fk_subscription_details', $wrong_sub_details_candidates)
            ->whereNotIn('fk_shop', [4213, 4214])
            ->pluck('channel_country', 'fk_shop');

        $correct_candidates = SubscriptionDetails::query()
            ->join('features', 'features_id', '=', 'fk_features')
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->select('subscription_details_id')
            ->addSelect(\DB::raw('CONCAT(fk_company_subscription_code, \'-\',channels_code, \'-\', countries_code) as channel_country'))
            ->distinct()
            ->pluck('subscription_details_id', 'channel_country');

        foreach ($wrong_candidates as $candidate) {
            ShopUser::query()
                ->where('shop_user_id', $candidate->shop_user_id)
                ->update([
                    'fk_subscription_details' => $correct_candidates->get($correct_group->get($candidate->fk_shop)),
                ]);
        }

        $this->info('Done correct');
        return 1;
    }
}
