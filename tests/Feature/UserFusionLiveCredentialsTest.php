<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCaseWithDb;

class UserFusionLiveCredentialsTest extends TestCaseWithDb
{
    public function test_user_fusionlive_credentials_are_encrypted()
    {
        $user = User::factory()->create([
            'fusionlive_username' => 'test_user',
            'fusionlive_password' => 'secret_password',
        ]);

        $this->assertEquals('test_user', $user->fusionlive_username);
        $this->assertEquals('secret_password', $user->fusionlive_password);

        // Check if stored encrypted in DB
        $rawUser = \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->first();
        
        $this->assertNotEquals('test_user', $rawUser->fusionlive_username);
        $this->assertNotEquals('secret_password', $rawUser->fusionlive_password);
        
        $this->assertEquals('test_user', Crypt::decryptString($rawUser->fusionlive_username));
        $this->assertEquals('secret_password', Crypt::decryptString($rawUser->fusionlive_password));
    }
}
