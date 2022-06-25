<?php

namespace App\Console\Commands\Once;

use App\Repositories\Sql\ShopCredentialRepository2;
use App\Services\Credential2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

/**
 *
 */
class DecryptCredentialUsername extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:decrypt-credential-username {{--shopEid=}}';


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
        $shopEid = $this->option('shopEid');
        $credential = new Credential2();
        $shops = $credential->getAllCredential($shopEid, null);
        foreach ($shops as $shop) {
            $type = true;
            $value = json_decode($shop->shop_credential_value, true);
            
            $value['user_name'] = Crypt::decryptString($value['user_name']);
            
            $shopCredentialRepo = new ShopCredentialRepository2();
            $shopCredentialRepo->update($shop->shop_credential_id,[
                'shop_credential_value' => json_encode($value)
            ]);
        }

        return "done";
    }
}
