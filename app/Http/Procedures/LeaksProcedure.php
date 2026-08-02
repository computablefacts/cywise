<?php

namespace App\Http\Procedures;

use App\Helpers\JosianeClient;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\Leak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sajya\Server\Procedure;

class LeaksProcedure extends Procedure
{
    public static string $name = 'leaks';

    #[RpcMethod(
        description: "List leaks.",
        params: [
            "asset" => "An optional asset to filter leaks by. (string|nullable|min:1|max:191)",
            "created_at_or_after" => "An optional date of the leak.",
        ],
        result: [
            "leaks" => "An array of leaks.",
        ],
        ai_examples: [
            "if the request is 'do I have leaked credentials?', the input should be {}",
            "if the request is 'have credentials leaked for example.com?', the input should be {\"asset\":\"example.com\"}",
        ],
        ai_result: "
@php
\$leaks = collect(\$result['leaks'] ?? []);
@endphp
@if(\$leaks->isEmpty())
No leaks found.
@else
@foreach(\$leaks as \$leak)
@if(empty(\$leak['password']))
The email {{ \$leak['email'] }} was leaked on {{ \$leak['leak_date'] }}.
@else
The email {{ \$leak['email'] }} associated to the password '{{ \$leak['password'] }}' was leaked on {{ \$leak['leak_date'] }}.
@endif
@if(!empty(\$leak['website']))
These credentials enable the user to log in to the website {{ \$leak['website'] }}.   
@endif
@endforeach
@endif
        ",
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'asset_id' => 'integer|nullable|exists:am_assets,id',
            'asset' => 'string|nullable|min:1|max:191',
            'tags' => 'array|nullable',
            'created_at_or_after' => 'date|nullable',
        ]);

        /** @var Carbon $createdAtOrAfter */
        $createdAtOrAfter = isset($params['created_at_or_after']) ? Carbon::parse($params['created_at_or_after'])->startOfDay() : null;

        Log::debug("Fetching leaks of the last 15 days...");

        /** @var User $user */
        $user = $request->user();
        $now = Carbon::now()->utc()->subDays(15);
        $leaks = Leak::where('created_at', '>=', $now)->orderByDesc('created_at')->get();
        $assetId = $params['asset_id'] ?? null;
        $asset = $params['asset'] ?? null;
        $tags = $params['tags'] ?? null;
        $tlds = Asset::select('am_assets.*')
            ->join('users', 'users.id', '=', 'am_assets.created_by')
            ->when($user->tenant_id, fn($query, $tenantId) => $query->where('users.tenant_id', $tenantId))
            ->when($user->customer_id, fn($query, $customerId) => $query->where('users.customer_id', $customerId))
            ->when($assetId, fn($q, $id) => $q->where('am_assets.id', $id))
            ->when($asset, fn($q, $asset) => $q->where(fn($q) => $q->where('am_assets.tld', 'LIKE', '%' . Str::lower($asset) . '%')->orWhere('am_assets.asset', 'LIKE', '%' . Str::lower($asset) . '%')))
            ->when($tags && count($tags) > 0, function ($q) use ($tags) {
                $q->whereHas('tags', function ($sub) use ($tags) {
                    $sub->whereIn('tag', $tags);
                });
            })
            ->get()
            ->map(fn(Asset $asset) => $asset->tld())
            ->filter(fn(?string $tld) => !empty($tld))
            ->unique();

        Log::debug("Searching leaked credentials for {$tlds->count()} TLDs...");

        if (app()->runningUnitTests()) {
            $leaks = collect();
        } else if ($leaks->isEmpty()) {
            $leaks = $this->fetchLeaks($tlds);
        } else {
            $leaks = $this->fetchLeaks($tlds, $leaks->first()->created_at);
        }

        Log::debug("{$leaks->count()} leaks found.");

        $leaks->each(function (array $leak) use ($user) {
            $leak['created_by'] = $user->id;
            $leak['website'] = Str::limit($leak['website'], 191, '');
            $leak['email'] = Str::limit($leak['email'], 191, '');
            $leak['password'] = Str::limit($leak['password'], 191, '');
            Leak::updateOrCreate([
                'website' => $leak['website'],
                'email' => $leak['email'],
                'password' => $leak['password'],
            ], $leak);
        });
        return [
            'leaks' => Leak::query()
                ->when($createdAtOrAfter, fn($query, $date) => $query->where('created_at', '>=', $date))
                ->where(function ($query) use ($tlds) {
                    foreach ($tlds as $tld) {
                        $query->orWhere('email', 'LIKE', "%@{$tld}")
                            ->orWhere('website', 'LIKE', "%{$tld}%");
                    }
                })
                ->get()
                ->map(fn(Leak $leak) => (object)[
                    'timestamp' => $leak->created_at,
                    'leak_date' => $leak->leak_date?->format('Y-m-d'),
                    'leak_type' => $leak->leak_type,
                    'email' => $leak->email,
                    'website' => $leak->website,
                    'password' => $leak->password,
                ])
                ->sortBy('leak_date', SORT_NATURAL | SORT_FLAG_CASE),
        ];
    }

    private function fetchLeaks(Collection $tlds, ?Carbon $createdAtOrAfter = null): Collection
    {
        return collect($tlds)
            ->flatMap(function (string $tld) use ($createdAtOrAfter) {
                $range = $createdAtOrAfter ? "AND inserted_at >= '{$createdAtOrAfter->format('Y-m-d')}'" : '';
                $query = "
                    SELECT 
                        db_date AS leak_date,
                        db_type AS leak_type,
                        lower(concat(login, '@', login_email_domain)) AS email,
                        url_full AS website,
                        password,
                        inserted_at_sort
                    FROM josiane_v2
                    WHERE login_email_domain IN ('{$tld}')
                    {$range}
                    ORDER BY inserted_at_sort DESC, website ASC, email ASC
                    LIMIT 1000
                    SETTINGS use_query_cache = 1
                ";
                $output = JosianeClient::executeQuery($query);
                return collect(explode("\n", $output));
            })
            ->concat(
                $tlds
                    ->map(function (string $tld) {
                        $parts = array_reverse(explode('.', $tld));
                        return implode('.', $parts);
                    })
                    ->flatMap(function (string $tld) use ($createdAtOrAfter) {
                        $range = $createdAtOrAfter ? "AND inserted_at >= '{$createdAtOrAfter->format('Y-m-d')}'" : '';
                        $query = "
                            SELECT 
                                db_date AS leak_date,
                                db_type AS leak_type,
                                lower(concat(login, '@', login_email_domain)) AS email,
                                url_full AS website,
                                password,
                                inserted_at_sort
                            FROM josiane_v2
                            WHERE rev_url_domain LIKE '{$tld}.%'
                            {$range}
                            ORDER BY inserted_at_sort DESC, website ASC, email ASC
                            LIMIT 1000
                            SETTINGS use_query_cache = 1
                        ";
                        $output = JosianeClient::executeQuery($query);
                        return collect(explode("\n", $output));
                    })
            )
            ->filter(fn(string $line) => !empty($line) && $line !== 'ok')
            ->map(function (string $line) {
                $obj = explode("\t", $line);
                $website = Str::trim($obj[3] ?? '');
                if (filter_var($website, FILTER_VALIDATE_URL)) {
                    $website = parse_url($website, PHP_URL_SCHEME) . '://' . parse_url($website, PHP_URL_HOST);
                } else {
                    $website = '';
                }
                return [
                    'leak_date' => Str::before(Str::trim($obj[0]), ' '),
                    'leak_type' => Str::trim($obj[1] ?? ''),
                    'email' => Str::trim($obj[2] ?? ''),
                    'website' => $website,
                    'password' => $this->maskPassword(Str::trim($obj[4] ?? '')),
                ];
            })
            ->unique(fn(array $credentials) => $credentials['website'] . $credentials['email'] . $credentials['password'])
            ->sortByDesc('leak_date');
    }

    private function maskPassword(string $password, int $size = 3): string
    {
        if (Str::length($password) <= 2) {
            return Str::repeat('*', Str::length($password));
        }
        if (Str::length($password) <= 2 * $size) {
            return $this->maskPassword($password, 1);
        }
        return Str::substr($password, 0, $size) . Str::repeat('*', Str::length($password) - 2 * $size) . Str::substr($password, -1 * $size);
    }
}
