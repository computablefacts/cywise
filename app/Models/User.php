<?php

namespace App\Models;

use App\AgentSquad\ActionsRegistry;
use App\Helpers\MailCoach;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory, HasApiTokens, Notifiable, HasProfileKeyValues;

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
        'ynh_password',
        'role_id',
        'verification_code',
        'verified',
        'trial_ends_at',
        'customer_id',
        'tenant_id',
        'am_api_token',
        'superset_id',
        'gets_audit_report',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'ynh_password',
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
        static::created(function (User $user) {

            if (!$user->tenant_id) {
                /** @var Tenant $tenant */
                $tenant = Tenant::create(['name' => Str::random()]);
                $user->tenant_id = $tenant->id;
                $user->save();
            }

            // Assign the default roles
            $user->assignRole(config('wave.default_user_role', 'registered'));

            // Set frameworks, templates and roles
            $user->actAs();
            $user->init();

            // Ensure all agents are added (enabled) at tenant level for this user's tenant
            try {
                $actions = ActionsRegistry::all();
                foreach ($actions as $actionName => $action) {
                    ActionSetting::firstOrCreate([
                        'scope_type' => 'tenant',
                        'scope_id' => $user->tenant_id,
                        'action' => $actionName,
                    ], [
                        'enabled' => true,
                    ]);
                }
            } catch (\Throwable $e) {
                // Non-fatal during user creation
            }
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
        }
        return $user;
    }

    /**
     * Log a user into the application without sessions or cookies. Does not trigger the Login event.
     */
    public function actAs(): void
    {
        if (Auth::setUser($this)) {
            Log::debug('The authenticated user is now : ' . Auth::user()->email);
        } else {
            Log::error('User::actAs() failed for user : ' . $this->email);
        }
    }

    public function isCywiseAdmin()
    {
        return $this->email === config('towerify.admin.email');
    }

    public function tenant(): ?Tenant
    {
        if ($this->tenant_id) {
            return Tenant::where('id', $this->tenant_id)->first();
        }
        return null;
    }

    public function canView(string $route): bool
    {
        return $this->can("view.{$route}");
    }

    public function cannotView(string $route): bool
    {
        return $this->cannot("view.{$route}");
    }

    public function canCall(string $procedure, string $method): bool
    {
        return $this->can("call.{$procedure}.{$method}");
    }

    public function cannotCall(string $procedure, string $method): bool
    {
        return $this->cannot("call.{$procedure}.{$method}");
    }

    public function init(): void
    {
        try {
            Log::debug("[{$this->email}] Updating user's prompts...");

            $this->setupPrompts('default_answer_question', 'seeders/prompts/default_answer_question.txt');
            $this->setupPrompts('default_assistant', 'seeders/prompts/default_assistant.txt');
            $this->setupPrompts('default_chat', 'seeders/prompts/default_chat.txt');
            $this->setupPrompts('default_chat_history', 'seeders/prompts/default_chat_history.txt');
            $this->setupPrompts('default_debugger', 'seeders/prompts/default_debugger.txt');
            $this->setupPrompts('default_hypothetical_questions', 'seeders/prompts/default_hypothetical_questions.txt');
            $this->setupPrompts('default_orchestrator', 'seeders/prompts/default_orchestrator.txt');
            $this->setupPrompts('default_reformulate_question', 'seeders/prompts/default_reformulate_question.txt');
            $this->setupPrompts('default_summarize', 'seeders/prompts/default_summarize.txt');

            //Remediation prompts

            $this->setupPrompts('cve_explanation_prompt', 'seeders/prompts/cve_explanation_prompt.txt');
            $this->setupPrompts('explanation_only_prompt', 'seeders/prompts/explanation_only_prompt.txt');
            $this->setupPrompts('false_positive_prompt', 'seeders/prompts/false_positive_prompt.txt');
            $this->setupPrompts('file_removal_explanation_only_prompt', 'seeders/prompts/file_removal_explanation_only_prompt.txt');
            $this->setupPrompts('file_removal_script_only_prompt', 'seeders/prompts/file_removal_script_only_prompt.txt');
            $this->setupPrompts('general_prompt', 'seeders/prompts/general_prompt.txt');
            $this->setupPrompts('weak_cipher_explanation_prompt', 'seeders/prompts/weak_cipher_explanation_prompt.txt');
            $this->setupPrompts('weak_cipher_script_only_prompt', 'seeders/prompts/weak_cipher_script_only_prompt.txt');

            Log::debug("[{$this->email}] User's prompts updated.");
            Log::debug("[{$this->email}] Updating user's templates...");

            $this->setupTemplates();

            Log::debug("[{$this->email}] User's templates updated.");

            // TODO : create user's private collection privcol*

        } catch (\Exception $e) {
            Log::error("Error while initializing user {$this->email} : {$e->getMessage()}");
        }
    }

    public function sentinelApiToken(): ?string
    {
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

    public function ynhUsername(): string
    {
        return Str::lower(Str::before(Str::before($this->email, '@'), '+'));
    }

    public function ynhPassword(): string
    {
        return isset($this->ynh_password) ? cywise_unhash($this->ynh_password) : '';
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

    private function setupPrompts(string $name, string $root)
    {
        $newPrompt = File::get(database_path($root));

        /** @var Prompt $oldPrompt */
        $oldPrompt = Prompt::query()
            ->where('created_by', $this->id)
            ->where('name', $name)
            ->first();

        if (isset($oldPrompt)) {
            $oldPrompt->update(['template' => $newPrompt]);
        } else {
            /** @var Prompt $oldPrompt */
            $oldPrompt = Prompt::create([
                'created_by' => $this->id,
                'name' => $name,
                'template' => $newPrompt
            ]);
        }
    }

    private function setupTemplates(): void
    {
        $template = Template::updateOrCreate([
            'name' => 'charte-informatique.json',
            'created_by' => $this->id,
        ], [
            'template' => $this->templateCharteInformatique(),
            'readonly' => true,
        ]);
        $template = Template::updateOrCreate([
            'name' => 'pssi.json',
            'created_by' => $this->id,
        ], [
            'template' => $this->templatePssi(),
            'readonly' => true,
        ]);
    }

    private function templateCharteInformatique(): array
    {
        $path = database_path('seeders/templates/charte-informatique.json');
        return json_decode(Str::trim(\File::get($path)), true);
    }

    private function templatePssi(): array
    {
        $path = database_path('seeders/templates/pssi.json');
        return json_decode(Str::trim(\File::get($path)), true);
    }
}
