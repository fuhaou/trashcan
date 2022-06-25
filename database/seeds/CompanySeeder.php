<?php

namespace Database\Seeders;

use App\Models\Sql\Companies;
use App\Models\Sql\CompanyRegisterCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ["Epsilo", 1001, "RESILOCOM"],
            ["Reckitt Benckiser", 1002, "RRB123ASC"],
            ["AAD", 1003, "RAAD123ASC"],
            ["DKSH", 1004, "RDKSH123ASC"],
            ["GroupM", 1005, "RGROUPMASC"],
            ["L'Oréal", 1006, "RLOREALSC"],
            ["Mindshare", 1007, "RMINDSHARE"],
            ["Rohto Mentholatum", 1008, "RROHTOCOM"],
            ["Skinetiq", 1009, "RSKINETIQCOM"],
            ["Unilever", 1010, "RUNILERVERCOM"],
            ["Colgate Palmolive", 1011, "RCOLPAL1018"],
            ["Danone", 1012, "RDANONE1018"],
            ["Estée Lauder", 1013, "RESTEELAUDER"],
            ["FrieslandCampina", 1014, "RFRIESCAMP"],
            ["VTA", 1015, "RVTA120820"],
            ["P&G", 1016, "RP&G041420"],
            ["Zenyum", 1017, "RZENYUM1210"],
            ["Medela", 1018, "RMEDELA1205"]
        ];
        DB::beginTransaction();

        try {
            foreach ($data as $item) {
                $arr = [
                    Companies::COL_COMPANIES_NAME => $item[0],
                    Companies::COL_COMPANIES_CODE => $item[1],
                    Companies::COL_COMPANIES_CREATED_AT => time()
                ];
                $companyId =DB::table('companies')->insertGetId($arr);
                $arrRegisterCode = [
                    CompanyRegisterCode::COL_FK_COMPANY => $companyId,
                    CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_VALUE => $item[2],
                    CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_CREATED_AT => time()
                ];
                DB::table('company_register_code')->insert($arrRegisterCode);
            }

            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            // something went wrong
        }
    }
}
