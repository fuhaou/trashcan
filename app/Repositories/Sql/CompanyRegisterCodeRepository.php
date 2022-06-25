<?php

namespace App\Repositories\Sql;

use App\Models\Sql\Companies;
use App\Models\Sql\CompanyRegisterCode;
use App\Repositories\BaseSqlRepository;

class CompanyRegisterCodeRepository extends BaseSqlRepository
{
    const IS_INACTIVE = 0;
    const IS_ACTIVE = 1;

    public function getModel()
    {
        return CompanyRegisterCode::class;
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getByCode($code)
    {
        return $this->_model::where([
            [CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_VALUE, $code],
            [CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_IS_ACTIVE, self::IS_ACTIVE],
        ])->first();
    }

    public function getCodeByCompanyName($name)
    {
        return $this->_model->newQuery()->join(
            Companies::TABLE_NAME,
            Companies::COL_COMPANIES_ID,
            CompanyRegisterCode::COL_FK_COMPANY
        )
            ->where(Companies::COL_COMPANIES_NAME, $name)
            ->first();
    }
}
