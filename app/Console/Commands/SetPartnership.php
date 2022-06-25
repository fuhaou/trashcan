<?php

namespace App\Console\Commands;

use App\Models\Sql\Partnership;
use App\Models\Sql\Users;
use Illuminate\Console\Command;

class SetPartnership extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:set-partnership
                            {company1 : id of company 1}
                            {company2 : id of company 2}
                            {created_by_email : email of who requested to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set partnership between 2 companies';

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
        $company1 = $this->argument('company1');
        $company2 = $this->argument('company2');
        $created_by_email = $this->argument('created_by_email');

        $user = Users::query()
            ->firstWhere('users_email', $created_by_email);
        
        if (is_null($user)) {
            $this->error('created_by_email is not in the system');
            return 0;
        }

        if (!is_numeric($company1)) {
            $this->error('company1 is not integer');
            return 0;
        }
        $company1 = intval($company1);

        if (!is_numeric($company2)) {
            $this->error('company2 is not integer');
            return 0;
        }
        $company2 = intval($company2);

        Partnership::query()
            ->insert([
                [
                    Partnership::COL_PARTNERSHIP_FROM => $company1,
                    Partnership::COL_PARTNERSHIP_TO => $company2,
                    Partnership::COL_PARTNERSHIP_CREATED_AT => time(),
                    Partnership::COL_PARTNERSHIP_UPDATED_AT => time(),
                    Partnership::COL_PARTNERSHIP_CREATED_BY => $user->users_id,
                ],
                [
                    Partnership::COL_PARTNERSHIP_FROM => $company2,
                    Partnership::COL_PARTNERSHIP_TO => $company1,
                    Partnership::COL_PARTNERSHIP_CREATED_AT => time(),
                    Partnership::COL_PARTNERSHIP_UPDATED_AT => time(),
                    Partnership::COL_PARTNERSHIP_CREATED_BY => $user->users_id,
                ]
            ]);

        $this->info('Successfully set partnership');
        return 1;
    }
}
