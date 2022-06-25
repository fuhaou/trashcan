<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 16/12/2020
 * Time: 19:38
 */

namespace App\Http\Middleware;

use App\Models\Sql\Users;
use App\Repositories\Sql\CompanyRepository;
use App\Repositories\Sql\CompanyUserRepository;
use Closure;

class ModeratorUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userId = $request->input('user_id', null);
        $userId = $userId ? $userId : auth('api')->user()->getAuthIdentifier();
        $companyUserRepository = new CompanyUserRepository();
        $moderatorUser = $companyUserRepository->getByCompanyCode(CompanyRepository::MODERATOR_CODE);
        $arrModeratorUserId = array_column($moderatorUser->toArray(), Users::COL_USERS_ID);
        if (in_array($userId, $arrModeratorUserId)) {
            $pathInfo = $request->getPathInfo();
            $moderatorUserService = new \App\Services\ModeratorUser();
            switch ($pathInfo) {
                case '/api/v1/shop/get-list-shop-by-user':
                    return $moderatorUserService->getListShopByUser($request);
                case '/api/v1/acl/get':
                    return $moderatorUserService->getListAcl($request);
                case '/api/v1/acl/get-for-check-all-shop':
                    return $moderatorUserService->getForCheckAllShop();
                case '/api/v1/shop/get-shop-detail':
                    return $moderatorUserService->getShopDetail($request);
            }
        }

        return $next($request);
    }
}
