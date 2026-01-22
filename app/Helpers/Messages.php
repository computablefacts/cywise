<?php

namespace App\Helpers;

use App\Models\VAuthorizedKey;
use App\Models\VEtcHost;
use App\Models\VEtcService;
use App\Models\VGroup;
use App\Models\VKernelModule;
use App\Models\VLdPreload;
use App\Models\VLoginAndLogout;
use App\Models\VNetworkInterface;
use App\Models\VPackage;
use App\Models\VProcess;
use App\Models\VProcessWithBoundNetworkSockets;
use App\Models\VProcessWithOpenNetworkSockets;
use App\Models\VScheduledTask;
use App\Models\VService;
use App\Models\VShellHistory;
use App\Models\VSuidBinary;
use App\Models\VUserAccount;
use App\Models\VUserSshKey;
use App\Models\YnhCve;
use App\Models\YnhOsquery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Messages
{
    // Categories
    const string PROCESSES_AND_BACKGROUND_TASKS = 'Processes & Background Tasks';
    const string SHELL_HISTORY_AND_ROOT_COMMANDS = 'Shell History & Root Commands';
    const string CONNECTIONS_AND_SOCKET_EVENTS = 'Connections & Socket Events';
    const string AUTHENTICATION_AND_SSH_ACTIVITY = 'Authentication & SSH Activity';
    const string PORTS_AND_INTERFACES = 'Ports & Interfaces';
    const string SERVICES_AND_SCHEDULED_TASKS = 'Services & Scheduled Tasks';
    const string USERS_AND_GROUPS = 'Users & Groups';
    const string PACKAGES = 'Packages';
    const string SUID_BIN = 'SUID Bin';
    const string LD_PRELOAD = 'LD_PRELOAD';
    const string KERNEL_MODULES = 'Kernel Modules';

    // Subcategories
    const string PROCESSES = 'Processes';
    const string BACKGROUND_TASKS = 'Background tasks';
    const string SHELL_HISTORY = 'Shell history';
    const string ROOT_COMMANDS = 'Root commands';
    const string PROCESSES_WITH_BOUND_NETWORK_SOCKETS = 'Processes with listening (bound) network sockets/ports';
    const string PROCESSES_WITH_OPEN_NETWORK_SOCKETS = 'Processes which have open network sockets on the system';
    const string LOGINS_AND_LOGOUTS = 'Logins & Logouts';
    const string SSH_KEYS_IN_THE_USERS_SSH_DIRECTORY = 'SSH keys in the users ~/.ssh directory';
    const string AUTHORIZED_KEYS = 'Authorized keys';
    const string ETC_SERVICES = 'Etc services';
    const string ETC_HOSTS = 'Etc hosts';
    const string NETWORK_INTERFACES = 'Network interfaces';
    const string SERVICES = 'Services';
    const string SCHEDULED_TASKS = 'Scheduled tasks';
    const string USERS = 'Users';
    const string GROUPS = 'Groups';

    // Dismiss
    const int HIDE_AFTER_DISMISS_COUNT = 3;

    public static function get(Collection $servers, Carbon $cutOffTime, array $categories = [
        self::PROCESSES_AND_BACKGROUND_TASKS,
        self::SHELL_HISTORY_AND_ROOT_COMMANDS,
        self::CONNECTIONS_AND_SOCKET_EVENTS,
        self::AUTHENTICATION_AND_SSH_ACTIVITY,
        self::PORTS_AND_INTERFACES,
        self::SERVICES_AND_SCHEDULED_TASKS,
        self::USERS_AND_GROUPS,
        self::PACKAGES,
        self::SUID_BIN,
        self::LD_PRELOAD,
        self::KERNEL_MODULES,
    ]): Collection
    {
        $messages = collect();
        foreach ($categories as $category) {
            match ($category) {
                self::PROCESSES_AND_BACKGROUND_TASKS => $messages = $messages->concat(self::processesAndBackgroundTasks($servers, $cutOffTime)->toArray()),
                self::SHELL_HISTORY_AND_ROOT_COMMANDS => $messages = $messages->concat(self::shellHistoryAndRootCommands($servers, $cutOffTime)->toArray()),
                self::CONNECTIONS_AND_SOCKET_EVENTS => $messages = $messages->concat(self::connectionsAndSocketEvents($servers, $cutOffTime)->toArray()),
                self::AUTHENTICATION_AND_SSH_ACTIVITY => $messages = $messages->concat(self::authenticationAndSshActivity($servers, $cutOffTime)->toArray()),
                self::PORTS_AND_INTERFACES => $messages = $messages->concat(self::portsAndInterfaces($servers, $cutOffTime)->toArray()),
                self::SERVICES_AND_SCHEDULED_TASKS => $messages = $messages->concat(self::servicesAndScheduledTasks($servers, $cutOffTime)->toArray()),
                self::USERS_AND_GROUPS => $messages = $messages->concat(self::usersAndGroups($servers, $cutOffTime)->toArray()),
                self::PACKAGES => $messages = $messages->concat(self::packages($servers, $cutOffTime)->toArray()),
                self::SUID_BIN => $messages = $messages->concat(self::suidBin($servers, $cutOffTime)->toArray()),
                self::LD_PRELOAD => $messages = $messages->concat(self::ldPreload($servers, $cutOffTime)->toArray()),
                self::KERNEL_MODULES => $messages = $messages->concat(self::kernelModules($servers, $cutOffTime)->toArray()),
                default => throw new \Exception("Unknown category: $category"),
            };
        }
        return $messages->filter(fn(array $event) => count($event) >= 1)
            ->sortByDesc('timestamp')
            ->values();
    }

    public static function processesAndBackgroundTasks(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VProcess::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_processes.server_id')
                    ->whereColumn('name', '=', 'v_processes.name2')
                    ->whereColumn('action', '=', 'v_processes.action')
                    ->whereColumn('columns_uid', '=', 'v_processes.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VProcess $event) {
                if ($event->isAdded()) {
                    $msg = "Le processus {$event->name} est lancé.";
                    return self::message($event, self::PROCESSES_AND_BACKGROUND_TASKS, self::PROCESSES, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Le processus {$event->name} est arrêté.";
                    return self::message($event, self::PROCESSES_AND_BACKGROUND_TASKS, self::PROCESSES, $msg);
                }
                return [];
            })
            ->filter(fn(array $event) => count($event) >= 1);
    }

    public static function shellHistoryAndRootCommands(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VShellHistory::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_shell_history.server_id')
                    ->whereColumn('name', '=', 'v_shell_history.name')
                    ->whereColumn('action', '=', 'v_shell_history.action')
                    ->whereColumn('columns_uid', '=', 'v_shell_history.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VShellHistory $event) {
                if ($event->isAdded()) {
                    $msg = "L'utilisateur {$event->username} a lancé la commande {$event->command}.";
                    return self::message($event, self::SHELL_HISTORY_AND_ROOT_COMMANDS, self::SHELL_HISTORY, $msg);
                }
                return [];
            })
            ->filter(fn(array $event) => count($event) >= 1);
    }

    public static function connectionsAndSocketEvents(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VProcessWithBoundNetworkSockets::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_processes_with_bound_network_sockets.server_id')
                    ->whereColumn('name', '=', 'v_processes_with_bound_network_sockets.name')
                    ->whereColumn('action', '=', 'v_processes_with_bound_network_sockets.action')
                    ->whereColumn('columns_uid', '=', 'v_processes_with_bound_network_sockets.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VProcessWithBoundNetworkSockets $event) {
                if ($event->isAdded()) {
                    $msg = "Le processus {$event->path} écoute à l'adresse {$event->local_address}:{$event->local_port}.";
                    return self::message($event, self::CONNECTIONS_AND_SOCKET_EVENTS, self::PROCESSES_WITH_BOUND_NETWORK_SOCKETS, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Le processus {$event->path} n'écoute plus à l'adresse {$event->local_address}:{$event->local_port}.";
                    return self::message($event, self::CONNECTIONS_AND_SOCKET_EVENTS, self::PROCESSES_WITH_BOUND_NETWORK_SOCKETS, $msg);
                }
                return [];
            })
            ->concat(
                VProcessWithOpenNetworkSockets::where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_processes_with_open_network_sockets.server_id')
                            ->whereColumn('name', '=', 'v_processes_with_open_network_sockets.name2')
                            ->whereColumn('action', '=', 'v_processes_with_open_network_sockets.action')
                            ->whereColumn('columns_uid', '=', 'v_processes_with_open_network_sockets.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VProcessWithOpenNetworkSockets $event) {
                        if ($event->isAdded()) {
                            $process = empty($event->path) ? "{$event->pid}" : "{$event->path} ($event->pid)";
                            $msg = "Le processus {$process} a une connexion ouverte de {$event->local_address}:{$event->local_port} vers {$event->remote_address}:{$event->remote_port}.";
                            return self::message($event, self::CONNECTIONS_AND_SOCKET_EVENTS, self::PROCESSES_WITH_OPEN_NETWORK_SOCKETS, $msg);
                        }
                        if ($event->isRemoved()) {
                            $process = empty($event->path) ? "{$event->pid}" : "{$event->path} ($event->pid)";
                            $msg = "Le processus {$process} n'a plus de connexion ouverte de {$event->local_address}:{$event->local_port} vers {$event->remote_address}:{$event->remote_port}.";
                            return self::message($event, self::CONNECTIONS_AND_SOCKET_EVENTS, self::PROCESSES_WITH_OPEN_NETWORK_SOCKETS, $msg);
                        }
                        return [];
                    })
            )
            ->filter(fn(array $event) => count($event) >= 1)
            ->sortByDesc('timestamp');
    }

    public static function authenticationAndSshActivity(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VLoginAndLogout::query()->where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_logins_and_logouts.server_id')
                    ->whereColumn('name', '=', 'v_logins_and_logouts.name')
                    ->whereColumn('action', '=', 'v_logins_and_logouts.action')
                    ->whereColumn('columns_uid', '=', 'v_logins_and_logouts.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VLoginAndLogout $event) {
                if ($event->isAdded()) {
                    $msg = "L'utilisateur {$event->entry_username} s'est connecté au serveur.";
                    return self::message($event, self::AUTHENTICATION_AND_SSH_ACTIVITY, self::LOGINS_AND_LOGOUTS, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "L'utilisateur {$event->entry_username} s'est déconnecté du serveur.";
                    return self::message($event, self::AUTHENTICATION_AND_SSH_ACTIVITY, self::LOGINS_AND_LOGOUTS, $msg);
                }
                return [];
            })
            ->concat(
                VAuthorizedKey::query()->where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_authorized_keys.server_id')
                            ->whereColumn('name', '=', 'v_authorized_keys.name')
                            ->whereColumn('action', '=', 'v_authorized_keys.action')
                            ->whereColumn('columns_uid', '=', 'v_authorized_keys.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VAuthorizedKey $event) {
                        if ($event->isAdded()) {
                            $msg = "Une clef SSH a été ajoutée au trousseau {$event->key_file}.";
                            return self::message($event, self::AUTHENTICATION_AND_SSH_ACTIVITY, self::AUTHORIZED_KEYS, $msg);
                        }
                        if ($event->isRemoved()) {
                            $msg = "Une clef SSH a été supprimée du trousseau {$event->key_file}.";
                            return self::message($event, self::AUTHENTICATION_AND_SSH_ACTIVITY, self::AUTHORIZED_KEYS, $msg);
                        }
                        return [];
                    })
            )
            ->concat(
                VUserSshKey::query()->where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_user_ssh_keys.server_id')
                            ->whereColumn('name', '=', 'v_user_ssh_keys.name')
                            ->whereColumn('action', '=', 'v_user_ssh_keys.action')
                            ->whereColumn('columns_uid', '=', 'v_user_ssh_keys.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VUserSshKey $event) {
                        if ($event->isAdded()) {
                            $msg = "L'utilisateur {$event->username} a créé une clef SSH ({$event->ssh_key}).";
                            return self::message($event, self::AUTHENTICATION_AND_SSH_ACTIVITY, self::SSH_KEYS_IN_THE_USERS_SSH_DIRECTORY, $msg);
                        }
                        if ($event->isRemoved()) {
                            $msg = "L'utilisateur {$event->username} a supprimé une clef SSH ({$event->ssh_key}).";
                            return self::message($event, self::AUTHENTICATION_AND_SSH_ACTIVITY, self::SSH_KEYS_IN_THE_USERS_SSH_DIRECTORY, $msg);
                        }
                        return [];
                    })
            )
            ->filter(fn(array $event) => count($event) >= 1)
            ->sortByDesc('timestamp');
    }

    public static function portsAndInterfaces(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VEtcService::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_etc_services.server_id')
                    ->whereColumn('name', '=', 'v_etc_services.name2')
                    ->whereColumn('action', '=', 'v_etc_services.action')
                    ->whereColumn('columns_uid', '=', 'v_etc_services.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VEtcService $event) {
                if ($event->isAdded()) {
                    $msg = "Le service {$event->name} ($event->comment) écoute sur le port {$event->port} ({$event->protocol}).";
                    return self::message($event, self::PORTS_AND_INTERFACES, self::ETC_SERVICES, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Le service {$event->name} ($event->comment) n'écoute plus sur le port {$event->port} ({$event->protocol}).";
                    return self::message($event, self::PORTS_AND_INTERFACES, self::ETC_SERVICES, $msg);
                }
                return [];
            })
            ->concat(
                VEtcHost::where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_etc_hosts.server_id')
                            ->whereColumn('name', '=', 'v_etc_hosts.name')
                            ->whereColumn('action', '=', 'v_etc_hosts.action')
                            ->whereColumn('columns_uid', '=', 'v_etc_hosts.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VEtcHost $event) {
                        if ($event->isAdded()) {
                            $msg = "L'hôte {$event->hostnames} redirige vers {$event->address}.";
                            return self::message($event, self::PORTS_AND_INTERFACES, self::ETC_HOSTS, $msg);
                        }
                        if ($event->isRemoved()) {
                            $msg = "L'hôte {$event->hostnames} ne redirige plus vers {$event->address}.";
                            return self::message($event, self::PORTS_AND_INTERFACES, self::ETC_HOSTS, $msg);
                        }
                        return [];
                    })
            )
            ->concat(
                VNetworkInterface::where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_network_interfaces.server_id')
                            ->whereColumn('name', '=', 'v_network_interfaces.name')
                            ->whereColumn('action', '=', 'v_network_interfaces.action')
                            ->whereColumn('columns_uid', '=', 'v_network_interfaces.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VNetworkInterface $event) {
                        if ($event->isAdded()) {
                            $msg = "L'interface réseau {$event->interface} ({$event->address}) a été ajoutée.";
                            return self::message($event, self::PORTS_AND_INTERFACES, self::NETWORK_INTERFACES, $msg);
                        }
                        if ($event->isRemoved()) {
                            $msg = "L'interface réseau {$event->interface} ({$event->address}) a été supprimée.";
                            return self::message($event, self::PORTS_AND_INTERFACES, self::NETWORK_INTERFACES, $msg);
                        }
                        return [];
                    })
            )
            ->filter(fn(array $event) => count($event) >= 1)
            ->sortByDesc('timestamp');
    }

    public static function servicesAndScheduledTasks(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VService::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_services.server_id')
                    ->whereColumn('name', '=', 'v_services.name2')
                    ->whereColumn('action', '=', 'v_services.action')
                    ->whereColumn('columns_uid', '=', 'v_services.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VService $event) {
                if ($event->isAdded()) {
                    $msg = "Le service {$event->name} ({$event->type}) a été ajouté.";
                    return self::message($event, self::SERVICES_AND_SCHEDULED_TASKS, self::SERVICES, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Le service {$event->name} ({$event->type}) a été supprimé.";
                    return self::message($event, self::SERVICES_AND_SCHEDULED_TASKS, self::SERVICES, $msg);
                }
                return [];
            })
            ->concat(
                VScheduledTask::where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_scheduled_tasks.server_id')
                            ->whereColumn('name', '=', 'v_scheduled_tasks.name')
                            ->whereColumn('action', '=', 'v_scheduled_tasks.action')
                            ->whereColumn('columns_uid', '=', 'v_scheduled_tasks.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VScheduledTask $event) {
                        if ($event->isAdded()) {
                            if ($event->cron === 'n/a') {
                                $schedule = "last_run={$event->last_run_time}, next_run={$event->next_run_time}";
                            } else {
                                $schedule = $event->cron;
                            }
                            if ($event->file === 'n/a') {
                                $file = "";
                            } else {
                                $file = " au fichier {$event->file}";
                            }
                            $msg = "La tâche planifiée {$event->command} ({$schedule}) a été ajoutée{$file}.";
                            return self::message($event, self::SERVICES_AND_SCHEDULED_TASKS, self::SCHEDULED_TASKS, $msg);
                        }
                        if ($event->isRemoved()) {
                            if ($event->cron === 'n/a') {
                                $schedule = "last_run={$event->last_run_time}, next_run={$event->next_run_time}";
                            } else {
                                $schedule = $event->cron;
                            }
                            if ($event->file === 'n/a') {
                                $file = "";
                            } else {
                                $file = " du fichier {$event->file}";
                            }
                            $msg = "La tâche planifiée {$event->command} ({$schedule}) a été supprimée{$file}.";
                            return self::message($event, self::SERVICES_AND_SCHEDULED_TASKS, self::SCHEDULED_TASKS, $msg);
                        }
                        return [];
                    })
            )
            ->filter(fn(array $event) => count($event) >= 1)
            ->sortByDesc('timestamp');
    }

    public static function usersAndGroups(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VUserAccount::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_user_accounts.server_id')
                    ->whereColumn('name', '=', 'v_user_accounts.name')
                    ->whereColumn('action', '=', 'v_user_accounts.action')
                    ->whereColumn('columns_uid', '=', 'v_user_accounts.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VUserAccount $event) {
                if ($event->isAdded()) {
                    $home = empty($event->home_directory) ? "" : " ({$event->home_directory})";
                    $msg = "L'utilisateur {$event->username}{$home} a été créé.";
                    return self::message($event, self::USERS_AND_GROUPS, self::USERS, $msg);
                }
                if ($event->isRemoved()) {
                    $home = empty($event->home_directory) ? "" : " ({$event->home_directory})";
                    $msg = "L'utilisateur {$event->username}{$home} a été supprimé.";
                    return self::message($event, self::USERS_AND_GROUPS, self::USERS, $msg);
                }
                return [];
            })
            ->concat(
                VGroup::where('timestamp', '>=', $cutOffTime)
                    ->whereIn('server_id', $servers->pluck('id'))
                    ->whereNotExists(function (Builder $query) {
                        $query->select(DB::raw(1))
                            ->from('v_dismissed')
                            ->whereColumn('ynh_server_id', '=', 'v_groups.server_id')
                            ->whereColumn('name', '=', 'v_groups.ynh_osquery_name')
                            ->whereColumn('action', '=', 'v_groups.action')
                            ->whereColumn('columns_uid', '=', 'v_groups.columns_uid')
                            ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
                    })
                    ->orderBy('timestamp', 'desc')
                    ->get()
                    ->map(function (VGroup $event) {
                        if ($event->isAdded()) {
                            $msg = "Le groupe {$event->name} a été créé.";
                            return self::message($event, self::USERS_AND_GROUPS, self::GROUPS, $msg);
                        }
                        if ($event->isRemoved()) {
                            $msg = "Le groupe {$event->name} a été supprimé.";
                            return self::message($event, self::USERS_AND_GROUPS, self::GROUPS, $msg);
                        }
                        return [];
                    })
            )
            ->filter(fn(array $event) => count($event) >= 1)
            ->sortByDesc('timestamp');
    }

    public static function packages(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VPackage::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_packages.server_id')
                    ->whereColumn('name', '=', 'v_packages.name')
                    ->whereColumn('action', '=', 'v_packages.action')
                    ->whereColumn('columns_uid', '=', 'v_packages.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VPackage $event) {
                if ($event->isAdded()) {

                    $osInfo = YnhOsquery::operatingSystem($event->server_id);

                    if (!$osInfo) {
                        $cves = '';
                    } else {
                        $cves = YnhCve::appCves($osInfo->os, $osInfo->codename, $event->package, $event->version)
                            ->pluck('cve')
                            ->unique()
                            ->join(', ');
                    }

                    $warning = empty($cves) ? '' : "Attention, ce paquet est vulnérable: {$cves}.";
                    $msg = "Le paquet {$event->package} {$event->version} ({$event->type}) a été installé. {$warning}";
                    return self::message($event, self::USERS_AND_GROUPS, self::USERS, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Le paquet {$event->package} {$event->version} ({$event->type}) a été désinstallé.";
                    return self::message($event, self::USERS_AND_GROUPS, self::USERS, $msg);
                }
                return [];
            })
            ->filter(fn(array $event) => count($event) >= 1);
    }

    public static function suidBin(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VSuidBinary::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_suid_binaries.server_id')
                    ->whereColumn('name', '=', 'v_suid_binaries.name')
                    ->whereColumn('action', '=', 'v_suid_binaries.action')
                    ->whereColumn('columns_uid', '=', 'v_suid_binaries.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VSuidBinary $event) {
                if ($event->isAdded()) {
                    $msg = "Les privilèges du binaire {$event->program} ont été élevés.";
                    return self::message($event, self::SUID_BIN, self::SUID_BIN, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Les privilèges du binaire {$event->program} ont été abaissés.";
                    return self::message($event, self::SUID_BIN, self::SUID_BIN, $msg);
                }
                return [];
            })
            ->filter(fn(array $event) => count($event) >= 1);
    }

    public static function ldPreload(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VLdPreload::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_ld_preload.server_id')
                    ->whereColumn('name', '=', 'v_ld_preload.name')
                    ->whereColumn('action', '=', 'v_ld_preload.action')
                    ->whereColumn('columns_uid', '=', 'v_ld_preload.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VLdPreload $event) {
                if ($event->action === 'snapshot') {
                    $msg = "Le binaire {$event->program} utilise la variable d'environnement LD_PRELOAD={$event->ld_preload_value}.";
                    return self::message($event, self::LD_PRELOAD, self::LD_PRELOAD, $msg);
                }
                return [];
            })
            ->filter(fn(array $event) => count($event) >= 1);
    }

    public static function kernelModules(Collection $servers, Carbon $cutOffTime): Collection
    {
        return VKernelModule::where('timestamp', '>=', $cutOffTime)
            ->whereIn('server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'v_kernel_modules.server_id')
                    ->whereColumn('name', '=', 'v_kernel_modules.ynh_osquery_name')
                    ->whereColumn('action', '=', 'v_kernel_modules.action')
                    ->whereColumn('columns_uid', '=', 'v_kernel_modules.columns_uid')
                    ->havingRaw('count(1) >=' . self::HIDE_AFTER_DISMISS_COUNT);
            })
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function (VKernelModule $event) {
                if ($event->isAdded()) {
                    $msg = "Le module {$event->name} a été ajouté au noyau.";
                    return self::message($event, self::KERNEL_MODULES, self::KERNEL_MODULES, $msg);
                }
                if ($event->isRemoved()) {
                    $msg = "Le module {$event->name} a été enlevé du noyau.";
                    return self::message($event, self::KERNEL_MODULES, self::KERNEL_MODULES, $msg);
                }
                return [];
            })
            ->filter(fn(array $event) => count($event) >= 1);
    }

    private static function message(Model $event, string $category, string $subcategory, string $message): array
    {
        return self::messageEx($event->event_id, $event->timestamp, $event->server_name, $event->server_ip_address, $category, $subcategory, $message);
    }

    private static function messageEx(int $id, Carbon $timestamp, string $serverName, string $serverIpAddress, string $category, string $subcategory, string $message): array
    {
        return [
            'id' => $id,
            'timestamp' => $timestamp->utc()->format('Y-m-d H:i:s'),
            'server' => $serverName,
            'ip' => $serverIpAddress,
            'category' => $category,
            'subcategory' => $subcategory,
            'message' => $message,
        ];
    }
}