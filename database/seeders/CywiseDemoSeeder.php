<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CywiseDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        User::getOrCreate('demo@mydomain.com', 'Cywise Démo', 'DemoPass2026');
    }

}
