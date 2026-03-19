<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCaseWithDb;

class FusionLiveUserCredentialsTest extends TestCaseWithDb
{
    public function test_user_fusionlive_credentials_are_encrypted()
    {
        Config::set('towerify.hasher.nonce', 'azertyuiop1234567890');

        $user = User::factory()->create([
            'fusionlive_username' => 'test_user',
            'fusionlive_password' => 'secret_password',
        ]);

        $this->assertEquals('test_user', $user->fusionlive_username);
        $this->assertEquals('secret_password', $user->fusionlive_password);

        // Check if stored encrypted in DB
        $userDb = \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->first();

        $this->assertEquals('test_user', $userDb->fusionlive_username);
        $this->assertNotEquals('secret_password', $userDb->fusionlive_password);
    }
}
