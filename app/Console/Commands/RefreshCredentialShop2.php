<?php

namespace App\Console\Commands;

use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Repositories\Sql\ShopRepository;
use App\Services\Credential2;
use App\Services\OTP2;
use Illuminate\Console\Command;

class RefreshCredentialShop2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:refresh-credential-shop2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refresh credential shop invalid';

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
        $credential = new Credential2();
        $shops = $credential->getShopCredentialNeedOTP();
        foreach ($shops as $shop) {
            if ($shop->{Shops::COL_SHOPS_IS_ACTIVE} == ShopRepository::IS_ACTIVE) {
                if ($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC} <= MAX_RETRY_CRED) {
                    $otp = new OTP2($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID});
                    $otp->resendOtp();
                } else {
                    $this->info('Passport Shop Credential id: ' . $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID} . ' Login seller fail', [
                        ShopCredential2::COL_SHOP_CREDENTIAL_ID => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID},
                        ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC},
                        ShopCredential2::COL_SHOP_CREDENTIAL_STATE => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_STATE},
                        ShopCredential2::COL_SHOP_CREDENTIAL_LAST_RETRY => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_LAST_RETRY},
                        ShopCredential2::COL_SHOP_CREDENTIAL_IS_ACTIVE => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_IS_ACTIVE},
                        ShopCredential2::COL_FK_SHOP => $shop->{ShopCredential2::COL_FK_SHOP},
                    ]);
                }
            }
        }
        return;
    }
}
