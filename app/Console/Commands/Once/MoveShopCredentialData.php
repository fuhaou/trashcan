<?php

namespace App\Console\Commands\Once;

use App\Models\Sql\Channels;
use App\Models\Sql\ShopCredential2;
use App\Services\Credential;
use Illuminate\Console\Command;

class MoveShopCredentialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:move-shop-credential-data {{--shopEid=}}';

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
        // get all credentials old
        $credential = new Credential();
        $credentials = $credential->getAllCredential($shopEid, null);
        $new_credentials = [];
        $new_credentials_openapi = [];

        foreach ($credentials as $cred) {
            // first, default is sellercenter type
            $new_credentials[] = [
                'shop_credential_id' => $cred->shop_credential_id,
                'fk_shop' => $cred->fk_shop,
                'shop_credential_value' => $cred->shop_credential_value,
                'shop_credential_token' => $cred->shop_credential_cookie,
                'shop_credential_token_hidden' => null,
                'shop_credential_state' => $cred->shop_credential_cookie_state,
                'shop_credential_type' => ShopCredential2::TYPE_SELLERCENTER,
                'shop_credential_last_fail_message' => null,
                'shop_credential_is_active' => $cred->shop_credential_is_active,
                'shop_credential_retry_get_credential_sc' => $cred->shop_credential_retry_get_credential_sc,
                'shop_credential_last_retry' => $cred->shop_credential_last_retry,
                'shop_credential_retry_otp' => $cred->shop_credential_retry_otp,
                'shop_credential_last_retry_otp' => $cred->shop_credential_last_retry_otp,
                'shop_credential_created_at' => $cred->shop_credential_created_at,
                'shop_credential_updated_at' => $cred->shop_credential_cookie_updated_at,
            ];
            if ($cred->{Channels::COL_CHANNELS_CODE} == 'LAZADA') {
                if ($cred->shop_credential_open_api != null) {
                    $old_openapi = json_decode($cred->shop_credential_open_api, true);
                    $channel_config = json_decode($cred->{Channels::COL_CHANNELS_CONFIG}, true);

                    $new_token = [
                        'app_key' => $channel_config['app_key'] ?? null,
                        'access_token' => $old_openapi['access_token'] ?? null,
                        'secret_key' => $channel_config['secret_key'] ?? null,
                    ];
                    $hidden = [
                        'account' => $old_openapi['account'] ?? null,
                        'refresh_token' => $old_openapi['refresh_token'] ?? null,
                        'api_url' => $channel_config['api_url'] ?? null,
                    ];

                    $new_credentials_openapi[] = [
                        'fk_shop' => $cred->fk_shop,
                        'shop_credential_value' => $cred->shop_credential_value,
                        'shop_credential_token' => json_encode($new_token),
                        'shop_credential_token_hidden' => json_encode($hidden),
                        'shop_credential_state' => ShopCredential2::STATE_SUCCESS, // default is ok
                        'shop_credential_type' => ShopCredential2::TYPE_OPENAPI,
                        'shop_credential_last_fail_message' => null,
                        'shop_credential_is_active' => $cred->shop_credential_is_active,
                        'shop_credential_retry_get_credential_sc' => 0,
                        'shop_credential_last_retry' => null,
                        'shop_credential_retry_otp' => 0,
                        'shop_credential_last_retry_otp' => null,
                        'shop_credential_created_at' => $cred->shop_credential_created_at,
                        'shop_credential_updated_at' => $cred->shop_credential_open_api_updated_at,
                    ];
                }
            }
        }

        ShopCredential2::insert($new_credentials);
        ShopCredential2::insert($new_credentials_openapi);

        return "done";
    }
}
