<?php
/**
 * Created by An Nguyen.
 * User: an.nguyenhoang@epsilo.io
 * Date: 8/1/2021
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\ResetPassword;
use App\Repositories\BaseSqlRepository;

class ResetPasswordRepository extends BaseSqlRepository
{
    const IS_ACTIVE = 1;
    const IS_INACTIVE = 0;
    const TIME_EXPIRE = 900;//15'

    public function getModel()
    {
        return ResetPassword::class;
    }

    /**
     * @param null $userId
     * @param null $token
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function search($userId = null, $token = null)
    {
        $model = $this->_model->newQuery()->where(ResetPassword::COL_RESET_PASSWORD_IS_ACTIVE, self::IS_ACTIVE);
        if (!empty($userId)) {
            $model->where(ResetPassword::COL_FK_USER, $userId);
        }
        if (!empty($token)) {
            $model->where(ResetPassword::COL_RESET_PASSWORD_TOKEN, $token);
        }

        return $model->get();
    }

    /**
     * @param $userId
     * @param $token
     * @return bool|int|mixed
     */
    public function createResetPassword($userId, $token)
    {
        $model = $this->_model->newQuery()->where(ResetPassword::COL_FK_USER, $userId)->first();
        if (!empty($model)) {
            return $model->update([
                ResetPassword::COL_RESET_PASSWORD_IS_ACTIVE => self::IS_ACTIVE,
                ResetPassword::COL_RESET_PASSWORD_TOKEN => $token,
                ResetPassword::COL_RESET_PASSWORD_CREATED_AT => time(),
            ]);
        } else {
            return $this->create(
                [
                    ResetPassword::COL_FK_USER => $userId,
                    ResetPassword::COL_RESET_PASSWORD_TOKEN => $token,
                    ResetPassword::COL_RESET_PASSWORD_CREATED_AT => time(),
                ]
            );
        }
    }

    /**
     * @param $userId
     * @param $token
     * @return int
     */
    public function updateResetPassword($userId, $token)
    {
        return $this->_model->newQuery()
            ->where(ResetPassword::COL_FK_USER, $userId)
            ->where(ResetPassword::COL_RESET_PASSWORD_TOKEN, $token)
            ->update([
                ResetPassword::COL_RESET_PASSWORD_IS_ACTIVE => self::IS_INACTIVE,
            ]);
    }

}
