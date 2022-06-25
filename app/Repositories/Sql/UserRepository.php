<?php

namespace App\Repositories\Sql;

use App\Library\QueryPaginator;
use App\Library\QuerySorter;
use App\Models\Sql\Companies;
use App\Models\Sql\CompanyRegisterCode;
use App\Models\Sql\CompanyUser;
use App\Models\Sql\ShopUser;
use App\Models\Sql\Users;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseSqlRepository
{
    const USER_ACTIVE = 1;
    const USER_INACTIVE = 0;

    public function getModel()
    {
        return Users::class;
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getByEmail($email)
    {
        return $this->_model::where(Users::COL_USERS_EMAIL, $email)->first();
    }

    /**
     * @param $companyId
     * @param null $shopId
     * @param null $companyUserRole
     * @param null $shopUserRole
     * @param null $firstName
     * @param null $lastName
     * @param null $email
     * @param QuerySorter|null $sorter
     * @param QueryPaginator|null $paginator
     * @return QueryPaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function search($companyId, $shopId = null, $companyUserRole = null, $shopUserRole = null, $firstName = null,
                           $lastName = null, $email = null, QuerySorter $sorter = null, QueryPaginator $paginator = null)
    {
        $companyId = intval($companyId);
        $shopId = $shopId ? intval($shopId) : null;
        $companyUserRole = $companyUserRole ? trim($companyUserRole) : null;
        $shopUserRole = $shopUserRole ? trim($shopUserRole) : null;
        $model = $this->_model->newQuery()
            ->join(
                CompanyRegisterCode::TABLE_NAME,
                sprintf('%s.%s', Users::TABLE_NAME, Users::COL_FK_COMPANY_REGISTER_CODE),
                '=',
                sprintf('%s.%s', CompanyRegisterCode::TABLE_NAME, CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID)
            )
            ->where(sprintf('%s.%s', CompanyRegisterCode::TABLE_NAME, CompanyRegisterCode::COL_FK_COMPANY), $companyId);
        if ($firstName) {
            $model->where(Users::COL_USERS_FIRST_NAME, 'like', '%'.$firstName.'%');
        }
        if ($lastName) {
            $model->where(Users::COL_USERS_LAST_NAME, 'like', '%'.$lastName.'%');
        }
        if ($email) {
            $model->where(Users::COL_USERS_EMAIL, 'like', '%'.$email.'%');
        }
        if ($shopId || $shopUserRole) {
            $model->join(
                ShopUser::TABLE_NAME,
                sprintf('%s.%s', ShopUser::TABLE_NAME, ShopUser::COL_FK_USER),
                '=',
                sprintf('%s.%s', Users::TABLE_NAME, Users::COL_USERS_ID)
            );
            if ($shopId) {
                $model->where(ShopUser::COL_FK_SHOP, $shopId);
            }
            if ($shopUserRole) {
                $model->where(ShopUser::COL_SHOP_USER_ROLE, $shopUserRole);
            }
        }
        if ($companyUserRole) {
            $model->join(
                CompanyUser::TABLE_NAME, function ($join) {
                    $join->on(
                    sprintf('%s.%s', CompanyUser::TABLE_NAME, CompanyUser::COL_FK_USER),
                    '=',
                    sprintf('%s.%s', Users::TABLE_NAME, Users::COL_USERS_ID)
                );
                    $join->on(
                    sprintf('%s.%s', CompanyUser::TABLE_NAME, CompanyUser::COL_FK_COMPANY),
                    '=',
                    sprintf('%s.%s', CompanyRegisterCode::TABLE_NAME, CompanyRegisterCode::COL_FK_COMPANY)
                );
                })
                ->where(CompanyUser::COL_COMPANY_USER_ROLE, $companyUserRole);
        }

        if ($sorter) {
            $model = $sorter->applyQuery($model);
        }

        if (! $paginator) {
            return $model->get();
        }

        $paginator->applyQuery($model);

        return $paginator;
    }

    public function getForAllocate($companyId, $shopEid, $shopUserRole = null, $firstName = null,
                           $lastName = null, $email = null)
    {
        $companyId = is_numeric($companyId) ? [intval($companyId)] : $companyId;
        $shopEid = $shopEid ? intval($shopEid) : null;
        $shopUserRole = $shopUserRole ? trim($shopUserRole) : null;
        $model = $this->_model->newQuery()
            ->join(
                CompanyRegisterCode::TABLE_NAME,
                sprintf('%s.%s', Users::TABLE_NAME, Users::COL_FK_COMPANY_REGISTER_CODE),
                '=',
                sprintf('%s.%s', CompanyRegisterCode::TABLE_NAME, CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID)
            )->join(
                Companies::TABLE_NAME,
                Companies::COL_COMPANIES_ID,
                '=',
                CompanyRegisterCode::COL_FK_COMPANY
            )
            ->leftJoin(DB::raw("(select distinct(fk_user),fk_shop,shop_user_is_allocated from shop_user where fk_shop = $shopEid) SU"),
                sprintf('%s.%s', 'SU', ShopUser::COL_FK_USER),
                sprintf('%s.%s', Users::TABLE_NAME, Users::COL_USERS_ID))
            ->whereIn(sprintf('%s.%s', CompanyRegisterCode::TABLE_NAME, CompanyRegisterCode::COL_FK_COMPANY), $companyId);
        if ($firstName) {
            $model->where(Users::COL_USERS_FIRST_NAME, 'like', '%'.$firstName.'%');
        }
        if ($lastName) {
            $model->where(Users::COL_USERS_LAST_NAME, 'like', '%'.$lastName.'%');
        }
        if ($email) {
            $model->where(Users::COL_USERS_EMAIL, 'like', '%'.$email.'%');
        }
        if ($shopUserRole) {
            $model->where(ShopUser::COL_SHOP_USER_ROLE, $shopUserRole);
        }

        return $model->get();
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $phone
     * @param string $password
     * @param int $companyRegisterCodeId
     * @return mixed|null
     */
    public function createUser($email, $firstName, $lastName, $phone, $password, $companyRegisterCodeId)
    {
        return $this->create(
            [
                Users::COL_USERS_EMAIL => $email,
                Users::COL_USERS_FIRST_NAME => $firstName,
                Users::COL_USERS_LAST_NAME => $lastName,
                Users::COL_USERS_PHONE => $phone,
                Users::COL_USERS_PASSWORD => Hash::make($password),
                Users::COL_FK_COMPANY_REGISTER_CODE => $companyRegisterCodeId,
                Users::COL_USERS_CREATED_AT => time(),
                Users::COL_USERS_UPDATED_AT => time(),
            ]
        );
    }

    /**
     * Update last login time of user.
     *
     * @param string $email
     * @param string $device
     * @param string $ip
     *
     * @return mixed|null
     */
    public function updateLastLogin($email, $device, $ip = null)
    {
        return $this->_model::where(Users::COL_USERS_EMAIL, $email)
            ->update([
                Users::COL_USERS_DEVICE => $device,
                Users::COL_USERS_LAST_LOGIN_AT => time(),
                Users::COL_USERS_IP => $ip,
            ]);
    }

    /**
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getCompanyInfo($userId)
    {
        $userId = $userId ? intval($userId) : null;
        $model = $this->_model->newQuery()->join(
            CompanyRegisterCode::TABLE_NAME,
            CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID,
            '=',
            Users::COL_FK_COMPANY_REGISTER_CODE
        )->join(
            Companies::TABLE_NAME,
            Companies::COL_COMPANIES_ID,
            '=',
            CompanyRegisterCode::COL_FK_COMPANY
        );
        if ($userId) {
            $model->where(Users::COL_USERS_ID, $userId);
        }

        return $model->first();
    }

    public function getProfileUser($userId)
    {
        return $this->_model->newQuery()
            ->join(
                CompanyRegisterCode::TABLE_NAME,
                Users::COL_FK_COMPANY_REGISTER_CODE,
                CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID
            )
            ->join(
                Companies::TABLE_NAME,
                Companies::COL_COMPANIES_ID,
                CompanyRegisterCode::COL_FK_COMPANY
            )
            ->where(Users::COL_USERS_ID, $userId)
            ->select(
                Users::COL_USERS_ID,
                Users::COL_USERS_FIRST_NAME,
                Users::COL_USERS_LAST_NAME,
                Users::COL_USERS_EMAIL,
                Users::COL_USERS_PHONE,
                Users::COL_USERS_IS_ACTIVE,
                Users::COL_USERS_LAST_LOGIN_AT,
                Companies::COL_COMPANIES_ID,
                Companies::COL_COMPANIES_NAME,
                Companies::COL_COMPANIES_CODE,
                Companies::COL_COMPANIES_IS_ACTIVE,
            )
            ->first();
    }

    public function getAllUserRegisterCode()
    {
        return $this->_model->newQuery()
            ->join(
                CompanyRegisterCode::TABLE_NAME,
                Users::COL_FK_COMPANY_REGISTER_CODE,
                CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID
            )
            ->get();
    }

    public function getUserByUserId($userIds)
    {
        return $this->_model->newQuery()->whereIn(Users::COL_USERS_ID, $userIds)->get();
    }
}
