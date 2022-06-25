<?php

namespace App\Console\Commands;

use App\Models\Sql\Companies;
use App\Models\Sql\CompanyUser;
use App\Models\Sql\Users;
use Illuminate\Console\Command;

class ChangeCompanyRootUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:change-root-user
                            {company_name : company will be updated}
                            {new_root_user_email : new root user email. This new root user must be a member of company already}
                            {--old_root_user_email= : old root user (will be checked)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change company root user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $company_name = $this->argument('company_name');
        $new_root_user_email = $this->argument('new_root_user_email');
        $old_root_user_email = $this->option('old_root_user_email');

        // first, get company id
        $company = Companies::query()
            ->firstWhere('companies_name', $company_name);

        if (is_null($company_name)) {
            $this->error('Company name '.$company_name.' is not existed');
            return 0;
        }

        // check $new_root_user_email is current member of company (can be normal or root)
        $normal_user = CompanyUser::query()
            ->join(Users::TABLE_NAME, 'users_id', 'fk_user')
            ->firstWhere([
                'users_email' => $new_root_user_email,
                'fk_company' => $company->companies_id,
            ]);
        if (is_null($normal_user)) {
            $this->error('User '.$new_root_user_email.' is not user of company '.$company_name);
            return 0;
        }

        $root_user = null;
        if (!is_null($old_root_user_email)) {
            // check $old_root_user_email is current root member of company
            $root_user = CompanyUser::query()
                ->join(Users::TABLE_NAME, 'users_id', 'fk_user')
                ->firstWhere([
                    'users_email' => $old_root_user_email,
                    'fk_company' => $company->companies_id,
                    'company_user_role' => 'root',
                ]);

            if (is_null($root_user)) {
                $this->error('User '.$old_root_user_email.' is not root user of company '.$company_name);
                return 0;
            }
        }
        
        // all condition fine, replace it
        \DB::transaction(function () use ($company, $old_root_user_email, $root_user, $normal_user) {
            $query = CompanyUser::query()
                ->where([
                    'company_user_role' => 'root',
                    'fk_company' => $company->companies_id
                ]);
            if (!is_null($old_root_user_email)) {
                $query->where([
                    'fk_user' => $root_user->users_id,
                ]);
            }
            $old_root_data = $query->get();

            $query->update([
                    'fk_user' => $normal_user->users_id
                ]);

            // delete normal_user
            $normal_user->delete();

            // add member user based on $old_root_data
            $data = [];
            $current_time = time();
            foreach ($old_root_data as $old) {
                if ($old->fk_user != $normal_user->users_id) {
                    $data[] = [
                        'fk_company' => $old->fk_company,
                        'fk_user' => $old->fk_user,
                        'company_user_role' => 'normal',
                        'company_user_is_active' => true,
                        'company_user_created_at' => $current_time,
                        'company_user_updated_at' => $current_time,
                    ];
                }
            }
            if (!empty($data)) {
                CompanyUser::query()
                    ->insert($data);
            }
        });
        $this->info('Done');
        return 1;
    }
}
