<?php

namespace App\Console\Commands;

use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Services\OTP2;
use Illuminate\Console\Command;

class CheckShopeeShopSid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:check-shop-sid
                        {--shop_eids= : List of shop to check (optional). If empty, check all active shops.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Shopee Shop sid';

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
        // get all active Shopee shops
        $active_shops = Shops::query()
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->join('countries', 'countries_id', '=', 'fk_country')
            ->where('shops_is_active', true)
            ->where('channels_code', 'SHOPEE')
            ->select('shops_eid', 'shops_name', 'channels_code', 'countries_code')
            ->get();
        
        $shop_eids = $this->option('shop_eids');
        if (!is_null($shop_eids)) {
            $shop_eids = explode(',', $shop_eids); // array
            $active_shops = $active_shops->filter(function($value, $key) use ($shop_eids) {
                return in_array($value->shops_eid, $shop_eids);
            });
        }

        $this->info('List of active shops to check ('.$active_shops->count().')');
        $this->table(
            ['Shop eid', 'Shop name', 'Channel code', 'Country code'],
            $active_shops->toArray(),
        );

        // get list shops doesn't have credential or invalid credential
        $no_credential_shops = ShopCredential2::query()
            ->rightJoin('shops', 'shops_eid', '=', 'fk_shop')
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->whereNull('shop_credential_id')
            ->where('channels_code', 'SHOPEE')
            ->pluck('shops_eid')
            ->all();

        $invalid_credential_shops = ShopCredential2::query()
            ->join('shops', 'shops_eid', '=', 'fk_shop')
            ->join('channels', 'channels_id', '=', 'fk_channel')
            ->whereIn('shop_credential_state', [ShopCredential2::STATE_NOT_LOGIN, ShopCredential2::STATE_NEED_OTP])
            ->where('shops_is_active', true)
            ->where('channels_code', 'SHOPEE')
            ->where('shop_credential_type', ShopCredential2::TYPE_SELLERCENTER)
            ->pluck('shops_eid')
            ->all();
        
        $exclude_shops = array_merge($no_credential_shops, $invalid_credential_shops);
        $this->info('Exclude list shop doesn\'t have credential: ['.implode(', ', $no_credential_shops).']');
        $this->info('Exclude list shop have invalid credential: ['.implode(', ', $invalid_credential_shops).']');

        $active_shops = $active_shops->filter(function($value, $key) use ($exclude_shops) {
            return !in_array($value->shops_eid, $exclude_shops);
        });
        $this->info('==> Total shops to check: '.$active_shops->count());

        $credential_check = ShopCredential2::query()
            ->whereIn('fk_shop', $active_shops->pluck('shops_eid'))
            ->where('shop_credential_type', ShopCredential2::TYPE_SELLERCENTER)
            ->select('shop_credential_id', 'fk_shop')
            ->get();
        
        $errors = [];
        foreach ($credential_check as $cred) {
            $otp = new OTP2($cred->shop_credential_id);
            if (!$otp->checkShopIdValid()) {
                $errors[] = $cred->fk_shop;
            }
        }
        if (empty($errors)) {
            $this->info('All cookies ok');
        } else {
            $this->error('List shop ('.count($errors).') below have wrong cookie: ['.implode(',', $errors).']');
        }
        
        return 1;
    }
}
