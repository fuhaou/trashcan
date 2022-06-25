<?php

namespace App\Repositories\Sql;

use App\Models\Sql\Companies;
use App\Models\Sql\CompanyUser;
use App\Models\Sql\Users;
use App\Repositories\BaseSqlRepository;

class CompanyUserRepository extends BaseSqlRepository
{
    const ROLE_NORMAL = 'normal';
    const ROLE_ROOT = 'root';

    public function getModel()
    {
        return CompanyUser::class;
    }

    /**
     * @param int $companyId
     * @param int $userId
     * @param string $role
     * @return mixed|null
     */
    public function createCompanyUser($companyId, $userId, $role)
    {
        return $this->create(
            [
                CompanyUser::COL_FK_COMPANY => $companyId,
                CompanyUser::COL_FK_USER => $userId,
                CompanyUser::COL_COMPANY_USER_ROLE => $role,
                CompanyUser::COL_COMPANY_USER_CREATED_AT => time(),
                CompanyUser::COL_COMPANY_USER_UPDATED_AT => time(),
            ]
        );
    }

    public function search($companyId = null, $userId = null, $role = null)
    {
        $companyId = $companyId ? intval($companyId) : null;
        $userId = $userId ? intval($userId) : null;
        $role = $role ? trim($role) : null;
        $model = $this->_model->newQuery()->join(
            Companies::TABLE_NAME,
            Companies::COL_COMPANIES_ID,
            '=',
            CompanyUser::COL_FK_COMPANY
        )->join(
            Users::TABLE_NAME,
            Users::COL_USERS_ID,
            '=',
            CompanyUser::COL_FK_USER
        );
        if ($companyId) {
            $model->where(Companies::COL_COMPANIES_ID, $companyId);
        }
        if ($userId) {
            $model->where(Users::COL_USERS_ID, $userId);
        }
        if ($role) {
            $model->where(CompanyUser::COL_COMPANY_USER_ROLE, $role);
        }

        return $model->get();
    }

    public function getCompanyByUser($userIds)
    {
        return $this->_model->newQuery()->whereIn(CompanyUser::COL_FK_USER, $userIds)
            ->pluck(CompanyUser::COL_FK_COMPANY);
    }

    public function getByCompanyCode($companyCode)
    {
        $companyCode = $companyCode ? trim($companyCode) : null;
        $model = $this->_model->newQuery()->join(
            Companies::TABLE_NAME,
            Companies::COL_COMPANIES_ID,
            '=',
            CompanyUser::COL_FK_COMPANY
        )->join(
            Users::TABLE_NAME,
            Users::COL_USERS_ID,
            '=',
            CompanyUser::COL_FK_USER
        )->where(Companies::COL_COMPANIES_CODE, $companyCode);

        return $model->get();
    }
}
