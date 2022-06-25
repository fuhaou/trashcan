<?php

namespace App\Repositories\Sql;

use App\Models\Sql\Users;
use App\Models\Sql\VerifyRegister;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class VerifyRegisterRepository extends BaseSqlRepository
{
    const IS_INACTIVE = 0;
    const IS_ACTIVE = 1;
    const IS_NOT_VERIFY = 0;
    const IS_VERIFY = 1;

    public function getModel()
    {
        return VerifyRegister::class;
    }

    /**
     * @param $email
     * @param $code
     * @param $expired
     * @return mixed|null
     */
    public function createVerifyCode($email, $code, $expired)
    {
        $response = null;
        try {
            DB::beginTransaction();
            $this->_model->newQuery()->where(VerifyRegister::COL_VERIFY_REGISTER_EMAIL, $email)
                ->update([
                    VerifyRegister::COL_VERIFY_REGISTER_IS_ACTIVE => self::IS_INACTIVE,
                    VerifyRegister::COL_VERIFY_REGISTER_UPDATED_AT => time(),
                ]);
            $response = $this->create(
                [
                    VerifyRegister::COL_VERIFY_REGISTER_EMAIL => $email,
                    VerifyRegister::COL_VERIFY_REGISTER_OTP_CODE => $code,
                    VerifyRegister::COL_VERIFY_REGISTER_EXPIRE_TIME => $expired,
                    VerifyRegister::COL_VERIFY_REGISTER_CREATED_AT => time(),
                ]
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
        }

        return $response;
    }

    /**
     * @param $email
     * @param null $otp
     * @param $isActive
     * @param $isVerify
     * @return mixed
     */
    public function search($email, $otp = null, $isActive = self::IS_ACTIVE, $isVerify = null)
    {
        $email = trim($email);
        $model = $this->_model::where(
            [
                VerifyRegister::COL_VERIFY_REGISTER_EMAIL => $email,
                VerifyRegister::COL_VERIFY_REGISTER_IS_ACTIVE => $isActive,
            ]
        );
        if ($otp) {
            $model->where(VerifyRegister::COL_VERIFY_REGISTER_OTP_CODE, $otp);
        }

        if ($isVerify) {
            $model->where(VerifyRegister::COL_VERIFY_REGISTER_IS_VERIFY, $isVerify);
        }

        return $model->get();
    }
}
