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
        $demoUser = User::getOrCreate('demo@mydomain.com', 'Cywise Démo', 'DemoPass2026');
        $demoUser->performa_domain = $this->performaDomainFromEnvVar();
        $demoUser->performa_secret = env('PERFORMA_SECRET_KEY', 'performa_secret');
        $demoUser->save();
    }

    private function performaDomainFromEnvVar() 
    {
        $url = env('PERFORMA_BASE_APP_URL', 'http://localhost:17802');

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;
        $port = $parsedUrl['port'] ?? null;

        return $host . ($port ? ":$port" : '');
    }
}
