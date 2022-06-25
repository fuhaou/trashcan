<?php

namespace App\Console\Commands;

use App\Models\Sql\Users;
use Illuminate\Console\Command;

class ChangeUserState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:change {user_emails* : list of user email} {--user_active= : active or not, 1 or 0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change state of user';

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
        $user_emails = $this->argument('user_emails'); // array
        $user_active = $this->option('user_active');

        if (is_null($user_active)) {
            $this->error('Must have value to depict user is active or not');
            return 0;
        }

        if ($user_active !== '0' && $user_active !== '1') {
            $this->error('Value of user active must be 0 or 1');
            return 0;
        }

        Users::query()
            ->whereIn('users_email', $user_emails)
            ->update([
                'users_is_active' => intval($user_active),
                'users_updated_at' => time(),
            ]);
        
        $this->info('Successfully update');
        return 1;
    }
}
