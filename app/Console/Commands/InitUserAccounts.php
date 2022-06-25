<?php

namespace App\Console\Commands;

use App\Models\Sql\CompanyRegisterCode;
use App\Models\Sql\CompanyUser;
use App\Models\Sql\Users;
use App\Repositories\Sql\CompanyRegisterCodeRepository;
use App\Repositories\Sql\CompanyRepository;
use App\Repositories\Sql\CompanyUserRepository;
use App\Repositories\Sql\ShopRepository;
use App\Repositories\Sql\SubscriptionCodeRepository;
use App\Repositories\Sql\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;

class InitUserAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init-user-accounts {{--file=}} {{--registerCode=}}';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $dataLog = [];
        $registerCodeValue = $this->option('registerCode');
        $file = $this->option('file');
        if (empty($registerCodeValue)) {
            dd('Register Code required');
        }
        $registerCode = DB::table('company_register_code')->where('company_register_code_value', $registerCodeValue)->first();
        if (!empty($registerCode)) {
            DB::beginTransaction();
            try {
                $path = storage_path('app/public/' . $file);
                if (file_exists($path)) {
                    $registerCodeId = $registerCode->{CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_ID};
                    $companyId = $registerCode->{CompanyRegisterCode::COL_FK_COMPANY};
                    $companyUserRepo = new CompanyUserRepository();
                    $userRepo = new UserRepository();
                    (new FastExcel())->import($path, function ($row) use ($userRepo, $companyUserRepo, $registerCodeId, $companyId,&$dataLog) {
                        if (!empty($row['Email'])){
                            $user = $userRepo->getByEmail($row['Email']);
                            if (!empty($user)) {
                                $dataLog[] = [$row, 'msg' => 'user email exist.'];
                            } else {
                                $userInfo = $userRepo->createUser($row['Email'], $row['First Name'], $row['Last Name'], '', $row['Password'], $registerCodeId);
                                $companyUserRepo->createCompanyUser(
                                    $companyId,
                                    $userInfo->{Users::COL_USERS_ID},
                                    CompanyUserRepository::ROLE_NORMAL,
                                );
                            }
                        }
                    });
                } else {
                    dd("The file $path does not exist");
                }
                DB::commit();
                // all good
            } catch (\Exception $e) {
                DB::rollback();
            }
            echo "done \n";
            dd($dataLog);
        } else {
            dd("Register Code not found");
        }


    }
}
