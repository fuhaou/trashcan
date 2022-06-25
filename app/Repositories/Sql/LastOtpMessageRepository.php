<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 11/11/2020
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\LastOtpMessage;
use App\Repositories\BaseSqlRepository;

class LastOtpMessageRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return LastOtpMessage::class;
    }

    public function getOtpByPhone($phone)
    {
        return $this->_model->newQuery()
            ->where(LastOtpMessage::COL_LAST_OTP_MESSAGE_PHONE, $phone)
            ->select(
                LastOtpMessage::COL_LAST_OTP_MESSAGE_OTP,
                LastOtpMessage::COL_LAST_OTP_MESSAGE_UPDATED_AT
            )
            ->first();
    }

    public function updateByPhone($phone, $inputs)
    {
        return $this->_model->newQuery()->where(LastOtpMessage::COL_LAST_OTP_MESSAGE_PHONE, $phone)
            ->update($inputs);
    }


}
