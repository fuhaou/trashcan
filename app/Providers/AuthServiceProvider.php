<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        $this->registerPolicies();

        Passport::routes(function ($router) {
            $router->forAuthorization();
            $router->forAccessTokens();
            $router->forTransientTokens();
            Route::post('/token', [
                'uses' => 'AccessTokenController@issueToken',
                'middleware' => [/*'throttle:1000000,1',*/'log'],
            ]);
        });

        Passport::tokensCan([
            'shop-info' => 'Shop Info', // minimum scope
        ]);

        Passport::setDefaultScope([
            'shop-info',
        ]);

        Passport::tokensExpireIn(now()->addSeconds(config('passport.tokens_expire')));
        Passport::refreshTokensExpireIn(now()->addSeconds(config('passport.refresh_tokens_expire')));

        if ($this->isRememberMe($request)) {
            Passport::refreshTokensExpireIn(now()->addSeconds(config('passport.refresh_tokens_expire_remember_me')));
        }
    }

    /**
     * Check Request is get token user by password and remember me.
     * @param Request $request
     * @return bool
     */
    private function isRememberMe(Request $request)
    {
        $path = $request->path();
        $grantType = $request->input('grant_type', null);
        $rememberMe = $request->input('remember_me', null);
        $rememberMe = filter_var($rememberMe, FILTER_VALIDATE_BOOLEAN);

        if ($path == 'oauth/token' && in_array($grantType, ['password', 'refresh_token']) && $rememberMe) {
            return true;
        }

        return false;
    }
}
