<?php

namespace App\Console\Commands\Once;

use App\Models\Sql\CompanyRegisterCode;
use App\Models\Sql\CompanyUser;
use App\Models\Sql\Users;
use App\Repositories\Sql\CompanyRegisterCodeRepository;
use App\Repositories\Sql\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;

class MigrateUserTable extends Command
{
    protected $signature = 'once:user-table';

    protected $description = 'Migrate data user table V1 => V2';

    public function handle()
    {
        $users = DB::table('users')->get();
        if (!(count($users) > 0)) {
            $path = storage_path('app/public/users.xlsx');
            if (file_exists($path)) {
                $dataUser = [];
                (new FastExcel())->import($path, function ($row) use (&$dataUser) {
                    $firstName = $row["User First Name"];
                    $lastName = $row["User Last Name"];
                    if (empty($firstName) && empty($lastName)) {
                        $firstName = $row["User Full Name"];
                    }
                    $company = $row["Company"];
                    $companyRegisterCode = new CompanyRegisterCodeRepository();
                    $companyRegisterCode = $companyRegisterCode->getCodeByCompanyName($company);
                    if ($companyRegisterCode) {
                        array_push($dataUser,[
                            Users::COL_USERS_ID => $row["User ID"],
                            Users::COL_USERS_FIRST_NAME => $firstName,
                            Users::COL_USERS_LAST_NAME => $lastName,
                            Users::COL_USERS_EMAIL => $row["User Email"],
                            Users::COL_USERS_PASSWORD => $row["User Password"],
                            Users::COL_USERS_PHONE => $row["User Phone"],
                            Users::COL_USERS_IP => $row["User IP"],
                            Users::COL_USERS_DEVICE => $row["User Device"],
                            Users::COL_USERS_AVATAR => $row["User Avatar"],
                            Users::COL_FK_COMPANY_REGISTER_CODE => $companyRegisterCode->{CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID},
                            Users::COL_USERS_IS_ACTIVE => $row["Fk Config Active"],
                            Users::COL_USERS_LAST_LOGIN_AT => $row["User Last Login"],
                            Users::COL_USERS_CREATED_AT => $row["User Created At"],
                            Users::COL_USERS_UPDATED_AT => !empty($row["User Update At"])?$row["User Update At"]:null
                        ]);
                    }
                });
                if (!empty($dataUser)){
                    DB::beginTransaction();
                    try {
                        DB::table('users')->insert($dataUser);
                        $user = new UserRepository();
                        $user = $user->getAllUserRegisterCode();
                        $adminId = [
                            326,
                            685,
                            902,
                            915,
                            989,
                            1004,
                            1026,
                            1054,
                            1078,
                        ];
                        $inputs = [];
                        foreach ($user as $item) {
                            $companyId = $item->{CompanyRegisterCode::COL_FK_COMPANY};
                            $userId = $item->{Users::COL_USERS_ID};
                            if (in_array($userId, $adminId)) {
                                $role = 'root';
                            } else {
                                $role = 'normal';
                            }
                            array_push($inputs, [
                                CompanyUser::COL_FK_COMPANY => $companyId,
                                CompanyUser::COL_FK_USER => $userId,
                                CompanyUser::COL_COMPANY_USER_ROLE => $role,
                                CompanyUser::COL_COMPANY_USER_CREATED_AT => time(),
                                CompanyUser::COL_COMPANY_USER_UPDATED_AT => time(),
                            ]);
                        }
                        DB::table('company_user')->insert($inputs);
                        DB::commit();
                        // all good
                    } catch (\Exception $e) {
                        DB::rollback();
                        dd($e->getMessage());
                    }
                }
                echo '=====================================done========================================';
            } else {
                echo "The file $path does not exist";
            }
            return ;
        }
    }

}
