<?php

namespace App\Console\Commands;

use App\Models\Sql\ShopAccounts;
use App\Models\Sql\ShopCredential2;
use App\Services\ShopAuthentication;
use App\Traits\CommonTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use App\Constants\CacheTag;

class CheckAccountCookieValid extends Command
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:check-account-cookie-valid {{--channelCode=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check account cookie valid';

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
        $channelCode = $this->option('channelCode');
        // get all active account cookie
        $where = [
            'shop_accounts_status' => 1,
        ];
        if (!empty($channelCode)) {
            $where['channel_code'] = $channelCode;
        }
        $accounts = ShopAccounts::query()
            ->where($where)
            ->whereIn('shop_accounts_type', [ShopCredential2::TYPE_MARKETING, ShopCredential2::TYPE_BRANDPORTAL])
            ->get();

        foreach ($accounts as $acc) {
            $requestId = $this->getRequestId(true, $acc->shop_accounts_id);
            
            $shopAuthService = new ShopAuthentication();
            $shopAuthService->setChannelCode($acc->channel_code);
            $shopAuthService->setCountryCode($acc->country_code);
            $shopAuthService->setUsername($acc->user_name);
            $shopAuthService->setPassword(Crypt::decryptString($acc->password));
            $shopAuthService->setSiteType($acc->shop_accounts_type);

            $isValid = $shopAuthService->checkAccountCookieValid($acc->shop_accounts_token, $acc->user_name);
            $msg = 'Cookie valid';
            if (!$isValid) {
                $msg = 'Cookie invalid';
                // update token status = 0
                try {
                    ShopAccounts::query()
                        ->where('shop_accounts_id', $acc->shop_accounts_id)
                        ->update([
                            'shop_accounts_token_status' => 0,
                            'shop_accounts_updated_at' => time(),
                        ]);
                } catch (Exception $e) {
                    $this->logError('cannot update token status to false', [
                            'trace' => $e->getTraceAsString(),
                            'error' => $e->getMessage(),
                            'account_id' => $acc->shop_accounts_id,
                    ]);
                }
                
                // try 3 times to login again
                $tries = 0;
                $exception_times = 0;
                $ok = false;
                do {
                    $tries++;
                    $response = $shopAuthService->checkLoginAndDetermineType();
                    // check cookie
                    if (empty($response['cookie_string'])) {
                        continue;
                    }
                    // check cookie valid
                    if (!$shopAuthService->checkAccountCookieValid($response['cookie_string'], $acc->user_name)) {
                        continue;
                    }
                    // cookie ok, save
                    try {
                        ShopAccounts::query()
                            ->where('shop_accounts_id', $acc->shop_accounts_id)
                            ->update([
                                'shop_accounts_token' => $response['cookie_string'],
                                'shop_accounts_token_status' => 1,
                                'shop_accounts_updated_at' => time(),
                            ]);
                        $ok = true;
                        break;
                    } catch (Exception $e) {
                        $exception_times++;
                        continue;
                    }
                } while ($tries < 3);
                if ($ok) {
                    $msg = 'Cookie invalid then valid again after '.$tries.' times tries';
                } else {
                    $msg = 'Cookie still invalid after '.$tries.' times, number of exception is '.$exception_times;
                }                
            } else {
                // update token status = 1
                try {
                    ShopAccounts::query()
                        ->where('shop_accounts_id', $acc->shop_accounts_id)
                        ->update([
                            'shop_accounts_token_status' => 1,
                            'shop_accounts_updated_at' => time(),
                        ]);
                } catch (Exception $e) {
                    $this->logError('cannot update token status to true', [
                            'trace' => $e->getTraceAsString(),
                            'error' => $e->getMessage(),
                            'account_id' => $acc->shop_accounts_id,
                    ]);
                }
            }
            
            $log_msg = 'Check cookie valid: '.$msg;            
            $this->logInfo($log_msg, ['account_Id'=>$acc->shop_accounts_id, 'type' => $acc->shop_accounts_type, 'request_id' => $requestId]);
            echo 'Request Id: ' . $requestId . "\n";
        }
        Cache::tags(CacheTag::ACCOUNT)->flush();
        return true;
    }
}
