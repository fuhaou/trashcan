<?php

namespace App\Console\Commands;

use App\Models\Sql\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UpdateUserInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-info
                            {old_user_email : old user email}
                            {--new_user_email= : new user email (optional)}
                            {--new_user_first_name= : new user first name (optional)}
                            {--new_user_last_name= : new user last name (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user info';

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
        $old_user_email = $this->argument('old_user_email');
        $new_user_email = $this->option('new_user_email');
        $new_user_first_name = $this->option('new_user_first_name');
        $new_user_last_name = $this->option('new_user_last_name');

        // check user existed
        $user = Users::query()
            ->firstWhere('users_email', $old_user_email);
        if (is_null($user)) {
            $this->error('User is not existed, cannot update');
            return 0;
        }
        $update = [];
        if ($new_user_email) {
            $update['users_email'] = $new_user_email;
        }
        if ($new_user_first_name) {
            $update['users_first_name'] = $new_user_first_name;
        }
        if ($new_user_last_name) {
            $update['users_last_name'] = $new_user_last_name;
        }

        if (!empty($update)) {
            $update['users_updated_at'] = time();

            Users::query()
                ->where('users_email', $old_user_email)
                ->update($update);
            $this->info('Successfully update user info');
        } else {
            $this->info('No info to update');
        }
        
        return 1;
    }
}
