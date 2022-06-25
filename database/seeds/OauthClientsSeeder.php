<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OauthClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('app.env') == 'production') {
            // on production, run commands below to generate
            // php artisan passport:client --password to generate 1 password client
            // php artisan passport:client to generate 2 clients
            return;
        }

        $data = [
            ['92094bd2-6cfb-4862-b837-4acafcbe4e2b', 'OneX-PullPush Client', 'aHb9JKQBG6qZadzdXpyMJfTd1v5waTAbYk00U2bk', 0],
            ['92074109-ac49-49fc-b6bc-f6828f28dc63', 'OneX-Backend Client', 'rpyClRWYECu16HQgKfw3U26ixo4nDjp2x1SwF7H3', 0],
            ['92074109-f35e-48a1-b7a3-689b1ffc124a', 'OneX Password Client', '78T9XP98hfMCxISLNw8uJdurlL2aiVtofCQkeGqM', 1],
        ];
        $temp = [];
        foreach ($data as $item) {
            $arr = [
                'id' => $item[0],
                'name' => $item[1],
                'secret' => $item[2],
                'redirect' => '',
                'personal_access_client' => 0,
                'password_client' => $item[3],
                'revoked' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            array_push($temp, $arr);
        }
        DB::table('oauth_clients')->insert($temp);
    }
}
