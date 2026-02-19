<?php

namespace App\Http\Procedures;

use App\Helpers\JosianeClient;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
            'asset' => 'string|nullable|min:1|max:191',
            'created_at_or_after' => 'date|nullable',
        ]);

        /** @var Carbon $createdAtOrAfter */
        $createdAtOrAfter = isset($params['created_at_or_after']) ? Carbon::parse($params['created_at_or_after']) : null;
        $asset = isset($params['asset']) ?? null;

        /** @var User $user */
        $user = $request->user();
        $now = Carbon::now()->utc()->subDays(15);
        $leaks = TimelineItem::fetchItems($user->id, 'leak', $now, null, 0);

        if ($leaks->isEmpty()) {

            $tlds = "'" . Asset::select('am_assets.*')
                    ->join('users', 'users.id', '=', 'am_assets.created_by')
                    ->when($user->tenant_id, fn($query, $tenantId) => $query->where('users.tenant_id', $tenantId))
                    ->when($user->customer_id, fn($query, $customerId) => $query->where('users.customer_id', $customerId))
                    ->when($asset, fn($query, $asset) => $query->whereLike('am_assets.asset', "%{$asset}"))
                    ->get()
                    ->map(fn(Asset $asset) => $asset->tld())
                    ->filter(fn(?string $tld) => !empty($tld))
                    ->unique()
                    ->join("','") . "'";

            if ($tlds === "''") {
                $leaks = collect();
            } else {

                $query = "
                  SELECT DISTINCT 
                    min(db_date) AS leak_date, 
                    lower(concat(login, '@', login_email_domain)) AS email, 
                    concat(url_scheme, '://', url_subdomain, '.', url_domain) AS website, 
                    password
                  FROM dumps_login_email_domain 
                  WHERE login_email_domain IN ({$tlds})
                  GROUP BY email, website, password
                  ORDER BY email, website ASC
                ";

                // Log::debug($query);

                $output = JosianeClient::executeQuery($query);
                $leaks = collect(explode("\n", $output))
                    ->filter(fn(string $line) => !empty($line) && $line !== 'ok')
                    ->map(function (string $line) {
                        $obj = explode("\t", $line);
                        return [
                            'leak_date' => Str::before(Str::trim($obj[0]), ' '),
                            'email' => Str::trim($obj[1] ?? ''),
                            'website' => Str::trim($obj[2] ?? ''),
                            'password' => $this->maskPassword(Str::trim($obj[3] ?? '')),
                        ];
                    })
                    ->map(function (array $credentials) {
                        // if (preg_match("/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/", $credentials['website'])) {
                        if (filter_var($credentials['website'], FILTER_VALIDATE_URL)) {
                            return $credentials;
                        }
                        return [
                            'leak_date' => $credentials['leak_date'],
                            'email' => $credentials['email'],
                            'website' => '',
                            'password' => $credentials['password'],
                        ];
                    })
                    ->unique(fn(array $credentials) => $credentials['email'] . $credentials['website'] . $credentials['password']);
            }
            if (count($leaks) > 0) {

                // Get previous leaks
                $leaksPrev = TimelineItem::fetchItems($user->id, 'leak', null, $now, 0)
                    ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']));

                $leaks = $leaks->filter(function (array $leak) use ($leaksPrev) {
                    return !$leaksPrev->contains(function (object $leakPrev) use ($leak) {
                        return $leakPrev->email === $leak['email'] &&
                            $leakPrev->website === $leak['website'] &&
                            $leakPrev->password === $leak['password'];
                    });
                });

                // Only add the new leaks
                if (count($leaks) > 0) {
                    $leaks->chunk(10)->each(fn(Collection $leaksChunk) => TimelineItem::createItem($user->id, 'leak', Carbon::now(), 0, [
                        'credentials' => json_encode($leaksChunk->values()->toArray()),
                    ]));
                }
            }
        }
        return [
            'leaks' => TimelineItem::fetchItems($user->id, 'leak', $createdAtOrAfter, null, 0)
                ->flatMap(fn(TimelineItem $item) => collect(json_decode($item->attributes()['credentials'], true))
                    ->map(fn(array $credentials) => (object)array_merge(['timestamp' => $item->timestamp], $credentials)))
                ->sortBy('leak_date', SORT_NATURAL | SORT_FLAG_CASE),
        ];
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
