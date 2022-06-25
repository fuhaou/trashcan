<?php

namespace App\Console\Commands;

use App\Models\Sql\Shops;
use App\Models\Sql\ShopCredential2;
use App\Services\Credential2;
use App\Services\OTP2;
use App\Traits\CommonTrait;
use Illuminate\Console\Command;

class CheckOpenApiTokenValid2 extends Command
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:check-openapi-token-valid2 {{--shopEid=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check open api token invalid shop';

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
     * 
     */
    public function handle()
    {
        $shopEid = $this->option('shopEid');
        $credential = new Credential2();
        $credentials = $credential->getAllCredential($shopEid, ShopCredential2::IS_ACTIVE, 'LAZADA', [ShopCredential2::TYPE_OPENAPI]);

        foreach ($credentials as $credential) {
            $requestId = $this->getRequestId(true, $credential->{ShopCredential2::COL_FK_SHOP});

            $otp = new OTP2($credential->{ShopCredential2::COL_SHOP_CREDENTIAL_ID});
            $msg = $otp->checkOpenApiTokenValid();

            $this->logInfo('Check Open Api Token result', ['shop_eid'=>$credential->{Shops::COL_SHOPS_EID}, 'credential_id'=>$credential->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, 'result' => $msg, 'request_id' => $requestId]);
            echo 'Request Id: ' . $requestId . "\n";
        }
        return;
    }
}
