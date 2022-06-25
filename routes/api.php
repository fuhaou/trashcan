<?php

use App\Http\Controllers\CommonController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Listeners\ReceiveChannelCallback;
use App\Http\Controllers\Listeners\ReceiveOtpController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopCredentialController2;
use App\Http\Controllers\ShopCredentialAccountController;
use App\Http\Controllers\SubscriptionCodeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AclController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ExternalController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ModeratorUser;
use App\Http\Controllers\PermissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/health-check', [HealthCheckController::class, 'healthCheck']);

Route::prefix('/v1')->group(function () {
    Route::get('/link-shop-channel-callback', [ReceiveChannelCallback::class, 'linkShop']);
    Route::post('/demo-registration', [ExternalController::class, 'registerDemo']);
});

Route::prefix('/v1')->group(function () {
    Route::post('/receive-otp', [ReceiveOtpController::class, 'verifyOtp']);
    Route::post('/check-cookie-valid', [ShopCredentialController2::class, 'checkCookieValid']);
    Route::post('/refresh-openapi-lazada', [ShopCredentialController2::class, 'refreshOpenApiLazada']);

    Route::post('/update-credential-shop', [ShopCredentialController2::class, 'updateCredentialShop']);


    /* Client Backend Client **/
    // Noted: temporarily off client auth check in order to see it'll help to reduce pressure on Passport
    // Route::middleware('client')->group(function () {
    /* USER **/
    Route::get('/verify-email', [UserController::class, 'verifyEmailAndSendOtpRegister']);
    Route::get('/verify-registration-code', [UserController::class, 'verifyRegistrationCode']);
    Route::get('/list-user-under-company', [UserController::class, 'getUserUnderCompany']);
    Route::get('/list-user', [UserController::class, 'getAllUser']);
    Route::post('/register-account', [UserController::class, 'registerAccount']);
    Route::get('/verify-otp', [UserController::class, 'verifyOtpRegister']);

    Route::post('/send-link-reset-password-email', [UserController::class, 'sendLinkResetPassword']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']);

    /* END USER **/

    /* ACCOUNT */
    Route::prefix('/account')->group(function () {
        Route::get('/get-list', [ShopCredentialAccountController::class, 'getList']);
        Route::post('/add-other', [ShopCredentialAccountController::class, 'addOther']);
        Route::get('/get-credential', [ShopCredentialAccountController::class, 'getCredential']);
    });
    /* END ACCOUNT **/

    /* SHOP **/
    Route::prefix('/shop')->group(function () {
        Route::get('/get-list-shop', [ShopController::class, 'getListShop']);
        Route::post('/resend-otp', [ShopCredentialController2::class, 'resendOtp']);
        Route::get('/get-credential', [ShopCredentialController2::class, 'getCredential']);
        Route::post('/update-credential-when-expire', [ShopCredentialController2::class, 'updateCredentialWhenExpire']);
        Route::post('/update-state-shop', [ShopController::class, 'updateStateShop']);
        Route::post('/relogin', [ShopCredentialController2::class, 'relogin']);
        Route::post('/add-other-credential', [ShopCredentialController2::class, 'addOtherCredential']);

        Route::post('/renew-cookie-by-otp-email', [ShopCredentialController2::class, 'renewCookieByOTPEmail']);
    });
    /* END SHOP **/

    /* COMMON **/
    Route::prefix('/common')->group(function () {
        Route::get('/get-list-channel', [CommonController::class, 'getListChannel']);
        Route::get('/get-list-country', [CommonController::class, 'getListCountry']);
        Route::get('/get-list-action', [CommonController::class, 'getListAction']);
        Route::get('/get-list-brand', [CommonController::class, 'getListBrand']);
        Route::get('/get-list-feature', [CommonController::class, 'getListFeature']);
        Route::get('/get-feature-all-channel', [CommonController::class, 'getFeatureAllChannel']);
        Route::post('/run-command', [CommonController::class, 'runCommand']);
    });

    /* END COMMON **/
    Route::post('/set-cookie-credential-shop', [ShopCredentialController2::class, 'setCookieCredentialShop']);
    Route::post('/get-link-confirm-link-shop', [ShopController::class, 'getLinkConfirmLinkShop']);
    Route::post('/update-open-api', [ShopController::class, 'updateOpenApi']);
    // });
    /* End Client Backend Client **/

    /* User token **/
    Route::middleware('auth:api')->group(function () {
        /* SHOP **/
        Route::prefix('/shop')->group(function () {
            Route::post('/link-shop', [ShopController::class, 'linkShop']);
            Route::post('/allocated-shop-member', [ShopController::class, 'allocatedShopMember']);
            Route::post('/remove-allocated-shop-member', [ShopController::class, 'removeAllocatedShopMember']);
            Route::get(
                '/get-list-shop-by-user',
                [ShopController::class, 'getListShopsByUser']
            )->middleware(ModeratorUser::class);
            Route::get('/get-shop-detail', [ShopController::class, 'getShopDetail'])->middleware(ModeratorUser::class);
            Route::post('/created-shop-manual', [ShopController::class, 'createShopManual']);
        });
        /* END SHOP **/

        /* USER **/
        Route::prefix('/user')->group(function () {
            Route::get('/get-user-for-allocate', [UserController::class, 'getUserForAllocate']);
            Route::get(
                '/profile-user', [UserController::class, 'getProfileUser']);
        });
        /* END USER **/
        /* CODE **/
        Route::get('/check-subscription-info', [SubscriptionCodeController::class, 'checkSubscriptionInfo']);
        /* END CODE **/

        /* ACL **/
        Route::prefix('/acl')->group(function () {
            Route::get(
                '/get',
                [AclController::class, 'getAcl']
            )->middleware(ModeratorUser::class);
            Route::get(
                '/get-for-check-all-shop',
                [AclController::class, 'getAclForCheckAllShop']
            )->middleware(ModeratorUser::class);
            Route::post('/modify', [AclController::class, 'modifyAcl']);
        });
        /* END ACL **/

        /* PERMISSION **/
        Route::prefix('/permission')->group(function () {
            Route::get('/get-by-shop-user', [PermissionController::class, 'getByShopUser']);
        });
        /* END PERMISSION **/

        Route::prefix('/company')->group(function () {
            Route::get('/get-partner', [CompanyController::class, 'getPartnerOfCompany']);
            Route::get('/get-company-for-allocate-shop', [CompanyController::class, 'getCompanyForAllocateShop']);
        });

        Route::post('/created-subscription-code', [SubscriptionCodeController::class, 'createdSubscriptionCode']);
    });
    /* End User token **/

    /** Client token, check scope */
    Route::prefix('/client')->middleware('client:shop-info')->group(function () {
        Route::get('/get-list-shop', [ShopController::class, 'getListShop']);
    });
});
