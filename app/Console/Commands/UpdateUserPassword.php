<?php

namespace App\Console\Commands;

use App\Models\Sql\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UpdateUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-password {user_email : user email} {new_plain_password : new password in plain, will be encrypted in system}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user password';

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
        $user_email = $this->argument('user_email');
        $new_pass = $this->argument('new_plain_password');

        // check user existed
        $user = Users::query()
            ->firstWhere('users_email', $user_email);
        if (is_null($user)) {
            $this->error('User is not existed, cannot update');
            return 0;
        }
        Users::query()
            ->where('users_email', $user_email)
            ->update([
                    'users_password' => Hash::make($new_pass),
                    'users_updated_at' => time(),
                ]);
        $this->info('Successfully update password');
        return 1;
    }
}
