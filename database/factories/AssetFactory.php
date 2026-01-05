<?php

namespace Database\Factories;

use App\Enums\AssetTypesEnum;
use App\Models\Asset;
use App\Models\User;
use App\Rules\IsValidDomain;
use App\Rules\IsValidIpAddress;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(AssetTypesEnum::class),
            'asset' => function (array $attributes) {
                if ($attributes['type'] === AssetTypesEnum::DNS) {
                    return $this->faker->domainName;
                } elseif ($attributes['type'] === AssetTypesEnum::IP) {
                    return $this->fakeValidIpv4();
                } else { // AssetTypesEnum::RANGE
                    return $this->fakeValidIpv4().'/24';
                }
            },
            'is_monitored' => $this->faker->boolean,
            'created_by' => Auth::user()->id ?? User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Asset $asset) {
            if (IsValidDomain::test($asset->asset)) {
                $asset->type = AssetTypesEnum::DNS;
            } elseif (IsValidIpAddress::test($asset->asset)) {
                $asset->type = AssetTypesEnum::IP;
            } else {
                $asset->type = AssetTypesEnum::RANGE;
            }
        });
    }

    public function monitored(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_monitored' => true,
            ];
        });
    }

    public function unmonitored(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_monitored' => false,
            ];
        });
    }

    private function fakeValidIpv4(): string
    {
        do {
            $ip = $this->faker->ipv4;
        } while (! IsValidIpAddress::test($ip));

        return $ip;
    }
}
