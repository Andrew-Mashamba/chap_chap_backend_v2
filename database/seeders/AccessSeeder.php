<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('access')->insert([
            [
                'key' => 'punguzo_token',
                'value' => 'eyJhcGlfa2V5IjoiMTIzMWFhdFROTkgifQ.aBYDdw.LE1rMNgObPMaEudaivuJbiJBNwM',
                'expires_at' => Carbon::parse('2025-05-03 12:52:23'),
                'created_at' => Carbon::parse('2025-04-22 08:00:34'),
                'updated_at' => Carbon::parse('2025-05-03 11:52:23'),
            ],
        ]);
    }
}
