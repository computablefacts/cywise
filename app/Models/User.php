<?php

namespace App\Models;

use App\Hashing\TwHasher;
use App\Helpers\MailCoach;
use App\Helpers\SupersetApiUtilsFacade as SupersetApiUtils;
use App\Jobs\DeleteEmbeddedChunks;
use App\Rules\IsValidCollectionName;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Wave\Traits\HasProfileKeyValues;
use Wave\User as WaveUser;

/**
 * @property int id
 * @property int tenant_id
 * @property ?int customer_id
 * @property Carbon trial_ends_at
 * @property string am_api_token
 * @property string se_api_token
 * @property string stripe_id
 * @property string performa_domain
 * @property string performa_secret
 * @property boolean terms_accepted
 * @property boolean gets_audit_report
 * @property ?int superset_id
 */
class User extends WaveUser
{
    use HasApiTokens, Notifiable, HasProfileKeyValues;

    public $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'avatar',
        'password',
        'role_id',
        'verification_code',
        'verified',
        'trial_ends_at',
        'customer_id',
        'tenant_id',
        'am_api_token',
        'superset_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        // Listen for the creating event of the model
        static::creating(function ($user) {
            // Check if the username attribute is empty
            if (empty($user->username)) {
                // Use the name to generate a slugified username
                $username = Str::slug($user->name, '');
                $i = 1;
                while (self::where('username', $username)->exists()) {
                    $username = Str::slug($user->name, '') . $i;
                    $i++;
                }
                $user->username = $username;
            }
        });

        // Listen for the created event of the model
        static::created(function ($user) {

            // Remove all roles
            $user->syncRoles([]);

            // Assign the default role
            $user->assignRole(config('wave.default_user_role', 'registered'));

            // Set frameworks, templates and roles
            User::init($user);
        });
    }

    public static function getOrCreate(string $email, string $name = '', string $password = '', ?int $tenant_id = null, ?string $username = null): \App\Models\User
    {
        /** @var User $user */
        $user = User::where('email', $email)->first();
        if (!$user) {

            MailCoach::updateSubscribers($email, $name);

            $username = $username ?? Str::before($email, '@');
            /** @var int $count */
            $count = User::where('username', $username)->count();

            if (!$tenant_id) {
                /** @var Tenant $tenant */
                $tenant = Tenant::create(['name' => Str::random()]);
            }

            /** @var User $user */
            $user = User::create([
                'name' => empty($name) ? Str::before($email, '@') : $name,
                'email' => $email,
                'username' => $count === 0 ? $username : ($username . $count),
                'password' => Hash::make(empty($password) ? Str::random(64) : $password),
                'verified' => 1,
                'tenant_id' => $tenant_id ?? $tenant->id,
                'avatar' => 'demo/default.png',
            ]);

            $user->syncRoles(Role::ADMINISTRATOR, Role::LIMITED_ADMINISTRATOR, Role::BASIC_END_USER);
        }
        if (!isset($user->superset_id)) { // Automatically create a proper superset account for all users
            $json = SupersetApiUtils::get_or_add_user($user);
            $user->superset_id = $json['id'] ?? null;
            $user->save();
        }
        return $user;
    }

    public static function init(User $user, bool $forceUpdate = false): void
    {
        $userOld = Auth::user();
        Auth::login($user); // otherwise the tenant will not be properly set

        try {
            // Set the user's prompts
            self::setupPrompts($user, 'default_answer_question', 'seeders/prompts/default_answer_question.txt');
            self::setupPrompts($user, 'default_assistant', 'seeders/prompts/default_assistant.txt');
            self::setupPrompts($user, 'default_chat', 'seeders/prompts/default_chat.txt');
            self::setupPrompts($user, 'default_chat_history', 'seeders/prompts/default_chat_history.txt');
            self::setupPrompts($user, 'default_debugger', 'seeders/prompts/default_debugger.txt');
            self::setupPrompts($user, 'default_hypothetical_questions', 'seeders/prompts/default_hypothetical_questions.txt');
            self::setupPrompts($user, 'default_orchestrator', 'seeders/prompts/default_orchestrator.txt');
            self::setupPrompts($user, 'default_reformulate_question', 'seeders/prompts/default_reformulate_question.txt');
            self::setupPrompts($user, 'default_summarize', 'seeders/prompts/default_summarize.txt');

            // Get the oldest user of the tenant. We will automatically attach the frameworks to this user
            $oldestTenantUser = User::query()
                ->when($user->tenant_id, fn($query) => $query->where('tenant_id', '=', $user->tenant_id))
                ->when($user->customer_id, fn($query) => $query->where('customer_id', '=', $user->customer_id))
                ->orderBy('created_at')
                ->first();

            // TODO : create CyberScribe's templates
            // TODO : create user's private collection privcol*

            // Create shadow collections for some frameworks
            $frameworks = \App\Models\YnhFramework::all();
            $providers = [
                'ANSSI' => 100,
                'FR' => 110,
                'EU' => 120,
                'NIST' => 130,
                'OWASP' => 140,
                'NOREA' => 150,
                'NCSC' => 160,
            ];
            $updated = [];

            foreach ($frameworks as $framework) {

                $collection = $framework->collectionName();
                $priority = $providers[$framework->provider];

                switch ($framework->file) {
                    case 'seeders/frameworks/anssi/anssi-guide-hygiene.jsonl':
                    case 'seeders/frameworks/anssi/anssi-genai-security-recommendations-1.0.jsonl':
                    case 'seeders/frameworks/gdpr/gdpr.jsonl':
                    case 'seeders/frameworks/gdpr/gdpr-checklist.jsonl':
                    case 'seeders/frameworks/nis/nis1-rules-fr.jsonl':
                    case 'seeders/frameworks/nis2/nis2-directive.jsonl':
                    case 'seeders/frameworks/nis2/annex-implementing-regulation-of-nis2-on-t-m.jsonl':
                    case 'seeders/frameworks/dora/dora.jsonl':
                    case 'seeders/frameworks/nist/nist-800-171-rev3.jsonl':
                    {
                        if ($forceUpdate && !in_array($collection, $updated)) {
                            /** @var \App\Models\Collection $collection */
                            $col = Collection::where('name', $collection)
                                ->where('is_deleted', false)
                                ->first();
                            if ($col) {
                                $col->is_deleted = true;
                                $col->save();
                                (new DeleteEmbeddedChunks())->handle();
                            }
                            $updated[] = $collection;
                        }
                        if (!$oldestTenantUser || $user->id === $oldestTenantUser->id) {
                            self::setupFrameworks($framework, $priority);
                        }
                        break;
                    }
                    default:
                    {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error while initializing user {$user->email} : {$e->getMessage()}");
        }

        Auth::logout();

        if ($userOld) {
            Auth::login($userOld);
        }
    }

    private static function setupPrompts(User $user, string $name, string $root)
    {
        $newPrompt = File::get(database_path($root));

        /** @var Prompt $oldPrompt */
        $oldPrompt = Prompt::query()
            ->where('created_by', $user->id)
            ->where('name', $name)
            ->first();

        if (isset($oldPrompt)) {
            $promptPrev = Str::lower(Str::trim(File::get(database_path("$root.prev"))));
            if (Str::lower(Str::trim($oldPrompt->template)) === $promptPrev) {
                $oldPrompt->update(['template' => $newPrompt]);
            } else {
                Log::debug("The user {$user->email} prompt {$oldPrompt->name} has not been updated");
            }
        } else {
            /** @var Prompt $oldPrompt */
            $oldPrompt = Prompt::create([
                'created_by' => $user->id,
                'name' => $name,
                'template' => $newPrompt
            ]);
        }
    }

    private static function setupFrameworks(YnhFramework $framework, int $priority): void
    {
        $collection = self::getOrCreateCollection($framework->collectionName(), $priority);
        if ($collection) {
            $path = Str::replace('.jsonl.gz', '.2.jsonl.gz', $framework->path());
            $url = \App\Http\Controllers\CyberBuddyController::saveLocalFile($collection, $path);
        }
    }

    private static function getOrCreateCollection(string $collectionName, int $priority): ?Collection
    {
        /** @var \App\Models\Collection $collection */
        $collection = Collection::where('name', $collectionName)
            ->where('is_deleted', false)
            ->first();

        if (!$collection) {
            if (!IsValidCollectionName::test($collectionName)) {
                Log::error("Invalid collection name : {$collectionName}");
                return null;
            }
            $collection = Collection::create([
                'name' => $collectionName,
                'priority' => max($priority, 0),
            ]);
        }
        return $collection;
    }

    public function tenant(): ?Tenant
    {
        if ($this->tenant_id) {
            return Tenant::where('id', $this->tenant_id)->first();
        }
        return null;
    }

    /** @deprecated */
    public function isBarredFromAccessingTheApp(): bool
    {
        return is_cywise() && // only applies to Cywise deployment
            !$this->isAdmin() && // the admin is always allowed to login
            !$this->isInTrial() && // the trial ended
            $this->customer_id == null && // the customer has not been set yet (automatically set after a successful subscription)
            !$this->subscribed(); // the customer has been set but the subscription ended
    }

    /** @deprecated */
    public function endsTrialSoon(): bool
    {
        return $this->isInTrial() && \Carbon\Carbon::now()->startOfDay()->gte($this->endOfTrial()->subDays(7));
    }

    /** @deprecated */
    public function endsTrialVerySoon(): bool
    {
        return $this->isInTrial() && \Carbon\Carbon::now()->startOfDay()->gte($this->endOfTrial()->subDays(3));
    }

    /** @deprecated */
    public function isInTrial(): bool
    {
        return $this->customer_id == null && \Carbon\Carbon::now()->startOfDay()->lte($this->endOfTrial());
    }

    /** @deprecated */
    public function endOfTrial(): Carbon
    {
        return $this->created_at->startOfDay()->addDays(15);
    }

    public function adversaryMeterApiToken(): ?string
    {
        if (!$this->canUseAdversaryMeter()) {
            return null;
        }
        if ($this->am_api_token) {
            return $this->am_api_token;
        }

        $tenantId = $this->tenant_id;
        $customerId = $this->customer_id;

        if ($customerId) {

            // Find the first user of this customer with an API token
            /** @var \App\Models\User $userTmp */
            $userTmp = User::where('customer_id', $customerId)
                ->where('tenant_id', $tenantId)
                ->whereNotNull('am_api_token')
                ->first();

            if ($userTmp) {
                return $userTmp->am_api_token;
            }
        }
        if ($tenantId) {

            // Find the first user of this tenant with an API token
            /** @var User $userTmp */
            $userTmp = User::where('tenant_id', $tenantId)
                ->whereNotNull('am_api_token')
                ->first();

            if ($userTmp) {
                return $userTmp->am_api_token;
            }
        }

        // This token will enable the user to configure AdversaryMeter through the user interface
        $token = $this->createToken('adversarymeter', ['']);
        $plainTextToken = $token->plainTextToken;

        $this->am_api_token = $plainTextToken;
        $this->save();

        return $plainTextToken;
    }

    public function sentinelApiToken(): ?string
    {
        if (!$this->canManageServers()) {
            return null;
        }
        if ($this->se_api_token) {
            return $this->se_api_token;
        }

        // This token will enable the user to configure servers using curl
        $token = $this->createToken('sentinel', []);
        $plainTextToken = $token->plainTextToken;

        $this->se_api_token = $plainTextToken;
        $this->save();

        return $plainTextToken;
    }

    public function canViewHome(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_OVERVIEW)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_METRICS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_EVENTS);
    }

    public function canViewVulnerabilityScanner(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_ASSETS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_VULNERABILITIES)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_SERVICE_PROVIDER_DELEGATION);
    }

    public function canViewAgents(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_AGENTS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_SECURITY_RULES);
    }

    public function canViewHoneypots(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_HONEYPOTS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_ATTACKERS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_IP_BLACKLIST);
    }

    public function canViewIssp(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_HARDENING)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_FRAMEWORKS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_AI_WRITER)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_CYBERBUDDY)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_CONVERSATIONS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_COLLECTIONS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_DOCUMENTS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_TABLES)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_CHUNKS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_PROMPTS);
    }

    public function canViewYunoHost(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_DESKTOP)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_SERVERS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_APPLICATIONS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_DOMAINS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_BACKUPS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_INTERDEPENDENCIES)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_TRACES);
    }

    public function canViewMarketplace(): bool
    {
        return $this->isAdmin()
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_PRODUCTS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_CART)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_ORDERS);
    }

    public function canViewSettings(): bool
    {
        return $this->hasPermissionTo(\App\Models\Permission::VIEW_USERS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_INVITATIONS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_PLANS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_MY_SUBSCRIPTION)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_DOCUMENTATION)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_TERMS)
            || $this->hasPermissionTo(\App\Models\Permission::VIEW_RESET_PASSWORD);
    }

    /** @deprecated */
    public function canListServers(): bool
    {
        return $this->hasPermissionTo(Permission::LIST_SERVERS) || $this->canManageServers();
    }

    /** @deprecated */
    public function canManageServers(): bool
    {
        return $this->hasPermissionTo(Permission::MANAGE_SERVERS);
    }

    /** @deprecated */
    public function canListApps(): bool
    {
        return $this->hasPermissionTo(Permission::LIST_APPS) || $this->canManageApps();
    }

    /** @deprecated */
    public function canManageApps(): bool
    {
        return $this->hasPermissionTo(Permission::MANAGE_APPS);
    }

    /** @deprecated */
    public function canListUsers(): bool
    {
        return $this->hasPermissionTo(Permission::LIST_USERS) || $this->canManageUsers();
    }

    /** @deprecated */
    public function canManageUsers(): bool
    {
        return $this->hasPermissionTo(Permission::MANAGE_USERS);
    }

    /** @deprecated */
    public function canListOrders(): bool
    {
        return $this->canListServers() && $this->canListApps();
    }

    /** @deprecated */
    public function canBuyStuff(): bool
    {
        return $this->hasPermissionTo(Permission::BUY_STUFF);
    }

    /** @deprecated */
    public function canUseAdversaryMeter(): bool
    {
        return $this->hasPermissionTo(Permission::USE_ADVERSARY_METER);
    }

    /** @deprecated */
    public function canUseAgents(): bool
    {
        return $this->canManageServers() || $this->hasPermissionTo(Permission::USE_AGENTS);
    }

    /** @deprecated */
    public function canUseCyberBuddy(): bool
    {
        return $this->hasPermissionTo(Permission::USE_CYBER_BUDDY);
    }

    public function ynhUsername(): string
    {
        return Str::lower(Str::before(Str::before($this->email, '@'), '+'));
    }

    public function ynhPassword(): string
    {
        return TwHasher::unhash($this->password); // TODO : migrate legacy column
    }

    public function client(): string
    {
        if ($this->customer_id) {
            return "cid{$this->customer_id}";
        }
        if ($this->tenant_id) {
            return "tid{$this->tenant_id}";
        }
        return "tid0-cid0";
    }
}
