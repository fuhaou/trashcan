<?php

namespace Database\Seeders;

use App\Models\Sql\Companies;
use App\Models\Sql\CompanyRegisterCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModeratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = ['Moderator', '0000', 'RMODERATOR'];
        try {
            DB::beginTransaction();
            $arr = [
                Companies::COL_COMPANIES_NAME => $data[0],
                Companies::COL_COMPANIES_CODE => $data[1],
                Companies::COL_COMPANIES_CREATED_AT => time(),
            ];
            $companyId = DB::table('companies')->insertGetId($arr);
            $arrRegisterCode = [
                CompanyRegisterCode::COL_FK_COMPANY => $companyId,
                CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_VALUE => $data[2],
                CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_CREATED_AT => time(),
            ];
            DB::table('company_register_code')->insert($arrRegisterCode);

            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            // something went wrong
        }
    }
}
