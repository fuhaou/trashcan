<?php

namespace App\Console\Commands;

use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Services\Credential2;
use App\Traits\CommonTrait;
use Epsilo\Auth\LazadaAuth;
use Epsilo\Auth\ShopeeAuth;
use Exception;
use Illuminate\Console\Command;

class UpdateShopName extends Command
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:update-name
                        {channelCode : Channel code to check}
                        {--shopEid= : shop eid to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update shop name';

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
        $shopEid = $this->option('shopEid');
        $channelCode = $this->argument('channelCode');

        $this->logInfo('Update shop name runs with channelCode = '.$channelCode.', shop_eid='.$shopEid);
        if ($channelCode == 'TOKOPEDIA') {
            $this->logInfo('Tokopedia channel is not supported right now');
            return;
        }

        $credential = new Credential2();
        $shops = $credential->getAllCredential($shopEid, ShopCredential2::IS_ACTIVE, $channelCode, [ShopCredential2::TYPE_SELLERCENTER], true);

        $headers = [];
        $headers['User-Agent'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0';
        $headers['Accept'] = 'application/json, text/plain, */*';
        $headers['Accept-Language'] = 'en-US,en;q=0.5';
        $headers['Connection'] = 'keep-alive';

        foreach ($shops as $shop) {
            $requestId = $this->getRequestId(true, $shop->{ShopCredential2::COL_FK_SHOP});
            
            if ($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_STATE} == ShopCredential2::STATE_SUCCESS) {
                // only check shop which have credential live
                switch ($channelCode) {
                    case 'SHOPEE':
                        $shopee = new ShopeeAuth();
                        $headers['Cookie'] = $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN};
                        $shopInfo = $shopee->getShopInfo($headers, $shop->countries_code);
                        if ($shopInfo['shop_name'] != $shop->shops_name) {
                            // update shop name
                            $this->updateShopName($shop->shops_eid, $shop->shops_name, $shopInfo['shop_name'], $requestId);
                        } else {
                            $this->logInfo('Shop name is not changed', ['shop_eid'=>$shop->shops_eid, 'name' => $shop->shops_name, 'request_id' => $requestId]);
                        }
                        break;
                    case 'LAZADA':
                        $lazada = new LazadaAuth();
                        $headers['Cookie'] = $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN};
                        $shopName = $lazada->getShopName($headers, $shop->countries_code);
                        if ($shopName != $shop->shops_name) {
                            // update shop name
                            $this->updateShopName($shop->shops_eid, $shop->shops_name, $shopName, $requestId);
                        } else {
                            $this->logInfo('Shop name is not changed', ['shop_eid'=>$shop->shops_eid, 'name' => $shop->shops_name, 'request_id' => $requestId]);
                        }
                        break;
                }
            }
        }
        return 0;
    }

    private function updateShopName($shop_eid, $old_name, $shop_name, $requestId) {
        try {
            Shops::query()
                ->where('shops_eid', $shop_eid)
                ->update([
                    'shops_name' => $shop_name,
                ]);
            $this->logInfo('Update shop name successfully', ['shop_eid'=>$shop_eid, 'old_name' => $old_name, 'new_name' => $shop_name, 'request_id' => $requestId]);
        } catch (Exception $e) {
            $this->logError('Got problem when updating shop name', ['shop_eid'=>$shop_eid, 'old_name' => $old_name, 'new_name' => $shop_name, 'request_id' => $requestId]);
        }
    }
}
