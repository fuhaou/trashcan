<?php

namespace App\Console\Commands;

use App\Models\Sql\Shops;
use Illuminate\Console\Command;

class ChangeShopState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:change
                        {shop_eids* : List of shop eid, must not be empty}
                        {--active= : active or not, 1 or 0}
                        {--pull= : pull or not, 1 or 0}
                        {--pull_reason= : pull reason must be available when pull has value}
                        {--push= : push or not, 1 or 0}
                        {--push_reason= : push reason must be available when push has value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change state of shop';

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
        $shop_eids = $this->argument('shop_eids'); // array
        $active = $this->option('active');
        $pull = $this->option('pull');
        $pull_reason = $this->option('pull_reason');
        $push = $this->option('push');
        $push_reason = $this->option('push_reason');

        if (!is_null($pull) && is_null($pull_reason)) {
            $this->error('Must have pull reason when pull has value');
            return 0;
        }

        if (!is_null($push) && is_null($push_reason)) {
            $this->error('Must have push reason when push has value');
            return 0;
        }

        $update = [];
        if (!is_null($active)) {
            if ($active !== '0' && $active !== '1') {
                $this->error('Value of active must be 0 or 1');
                return 0;
            }
            $update['shops_is_active'] = intval($active);
        }
        
        if (!is_null($pull)) {
            if ($pull !== '0' && $pull !== '1') {
                $this->error('Value of pull must be 0 or 1');
                return 0;
            }
            $update['shop_allowed_pull'] = intval($pull);
            $update['shop_allowed_pull_reason'] = $pull_reason;
        }

        if (!is_null($push)) {
            if ($push !== '0' && $push !== '1') {
                $this->error('Value of push must be 0 or 1');
                return 0;
            }
            $update['shop_allowed_push'] = intval($push);
            $update['shop_allowed_push_reason'] = $push_reason;
        }
        
        if (!empty($update)) {
            Shops::query()
                ->whereIn('shops_eid', $shop_eids)
                ->update($update);
            $this->info('Successfully update');
        } else {
            $this->info('Nothing to update');
        }
        
        return 1;
    }
}
