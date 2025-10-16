<?php

namespace Tests;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\Attacker;
use App\Models\HiddenAlert;
use App\Models\Honeypot;
use App\Models\Port;
use App\Models\Scan;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * To create the test database:
 * <pre>
 *     CREATE DATABASE tw_testdb DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 * </pre>
 *
 * To create the test user:
 * <pre>
 *     CREATE USER 'tw_testuser'@'localhost' IDENTIFIED BY 'z0rglub';
 *     GRANT ALL ON tw_testdb.* TO 'tw_testuser'@'localhost';
 *     FLUSH PRIVILEGES;
 * </pre>
 *
 * See https://dwij.net/how-to-speed-up-laravel-unit-tests-using-schemadump/
 */
abstract class TestCase extends BaseTestCase
{
    use FastRefreshDatabase;

    protected User $user;
    protected string $token;

    protected function afterRefreshingDatabase()
    {
        $cachedChecksum = $this->getCachedSeederChecksum();
        $currentChecksum = $this->calculateSeederChecksum();

        if ($cachedChecksum !== $currentChecksum) {
            print "\nSeeding database...\n";
//            $this->artisan('db:seed', ['class' => 'DatabaseSeeder']);
//            $this->artisan('db:seed', ['class' => 'CywiseSeeder']);
            shell_exec('php artisan db:seed --class=DatabaseSeeder');
            shell_exec('php artisan db:seed --class=CywiseSeeder');
            print "\nDatabase is seeded.\n";
            $this->storeSeederChecksum($currentChecksum);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ('testing' !== app()->environment()) {
            echo("The environment is not testing. I quit. This would likely destroy data.\n");
            exit(1);
        }

        $this->user = User::where('email', 'qa@computablefacts.com')->firstOrfail();
        $this->token = $this->user->createToken('tests', [])->plainTextToken;
        $this->user->am_api_token = $this->token;
        $this->user->save();
    }

    protected function tearDown(): void
    {
        Alert::whereNotNull('id')->delete();
        Asset::whereNotNull('id')->delete();
        Attacker::whereNotNull('id')->delete();
        HiddenAlert::whereNotNull('id')->delete();
        Honeypot::whereNotNull('id')->delete();
        Port::whereNotNull('id')->delete();
        Scan::whereNotNull('id')->delete();
        Screenshot::whereNotNull('id')->delete();
        parent::tearDown();
    }

    private function calculateSeederChecksum(): string
    {
        return rescue(fn() => md5_file(database_path('seeders/DatabaseSeeder.php')) . md5_file(database_path('seeders/CywiseSeeder.php')), '');
    }

    private function getCachedSeederChecksum(): ?string
    {
        return rescue(fn() => file_get_contents($this->getSeederChecksumFile()), null, false);
    }

    private function storeSeederChecksum(string $checksum): void
    {
        file_put_contents($this->getSeederChecksumFile(), $checksum);
    }

    private function getSeederChecksumFile(): string
    {
        $connection = $this->app[ConnectionInterface::class];

        $databaseNameSlug = Str::slug($connection->getDatabaseName());

        return storage_path("app/seeder-checksum_{$databaseNameSlug}.txt");
    }
}
