<?php

namespace App\Models;

use App\Enums\OsqueryPlatformEnum;
use App\Http\Procedures\OsqueryRulesProcedure;
use App\Http\Requests\JsonRpcRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int ynh_server_id
 * @property int row
 * @property string name
 * @property string host_identifier
 * @property Carbon calendar_time
 * @property int unix_time
 * @property int epoch
 * @property int counter
 * @property bool numerics
 * @property array columns
 * @property string action
 * @property bool packed
 * @property bool dismissed
 * @property ?string columns_uid
 * @property ?int ynh_osquery_rule_id
 */
class YnhOsquery extends Model
{
    use HasFactory;

    protected $table = 'ynh_osquery';

    protected $fillable = [
        'ynh_osquery_rule_id',
        'ynh_server_id',
        'row',
        'name',
        'host_identifier',
        'calendar_time',
        'unix_time',
        'epoch',
        'counter',
        'numerics',
        'columns',
        'columns_uid',
        'action',
        'packed',
        'dismissed',
    ];

    protected $casts = [
        'numerics' => 'boolean',
        'columns' => 'array',
        'calendar_time' => 'datetime',
        'packed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'dismissed' => 'boolean',
    ];

    public static function computeColumnsUid(array $json): string
    {
        ksort($json);
        $uid = '';
        foreach ($json as $key => $value) {
            if (is_array($value)) {
                $uid .= ($key . ':' . self::computeColumnsUid($value) . ';');
            } else {
                $uid .= ($key . ':' . $value . ';');
            }
        }
        return md5($uid);
    }

    /** @deprecated */
    public static function configLogParserLinux(YnhServer $server): string
    {
        $url = app_url();
        return <<< EOT
#!/bin/bash

if [ -f /etc/os-release ]; then

    id_like=$(grep '^ID_LIKE=' /etc/os-release | cut -d'=' -f2 | tr -d '"')
    
    if [ -z "\$id_like" ]; then
      id_like=$(grep '^ID=' /etc/os-release | cut -d'=' -f2 | tr -d '"')
    fi

    # Ensure that the OS is debian-based
    if [[ "\$id_like" == *"debian"* ]]; then
    
        # Parse Nginx logs
        if [ -d /etc/nginx ]; then
          for conf_file in "/etc/nginx/"{sites-available,conf.d}"/"*; do
            if [ -f "\$conf_file" ]; then
            
              server_name=$(grep -E "^\s*server_name\s+" "\$conf_file" | awk '{print $2}' | tr -d ';' | head -1)
              access_log_info=$(grep -E "^\s*access_log\s+" "\$conf_file")
            
              if [ -z "\$server_name" ]; then
                server_name="vhost.unk"
              fi
              if [ -n "\$access_log_info" ]; then
            
                access_log_path=$(echo "\$access_log_info" | awk '{print $2}' | tr -d ';' | head -1)
                log_format=$(echo "\$access_log_info" | awk '{print $3}' | tr -d ';' | head -1)
            
                if [ "\$log_format" == "combined" ] || [ "\$log_format" == "" ]; then
                  while read file; do
                    if [[ "\$file" == *.gz ]]; then
                      zcat "\$file" | awk -v fname="\$server_name" '{print fname" "$1}'
                    else
                      cat "\$file" | awk -v fname="\$server_name" '{print fname" "$1}'
                    fi
                  done< <(find "$(dirname \$access_log_path)" -type f -name "$(basename \$access_log_path)*")
                fi
              fi
            fi
          done | sort | uniq -c | awk '$1 >= 3' | sort -nr | gzip -c >/opt/logparser/nginx.txt.gz
          
          if [ -f /opt/logparser/nginx.txt.gz ]; then
            curl --silent -X POST \
              -H "Content-Type: multipart/form-data" \
              -F "data=@/opt/logparser/nginx.txt.gz" \
              {$url}/logparser/{$server->secret} \
              > /dev/null
          fi
        fi
        
        # Parse Apache logs
        if [ -d /etc/apache2 ] && [ -d /opt/logparser ]; then
          if [ -f /etc/apache2/envvars ]; then
            apache_log_dir=$(grep -R "APACHE_LOG_DIR" /etc/apache2/envvars | awk -F'=' '{print $2}' | awk -F'$' '{print $1}')
          else
            apache_log_dir="/var/log/apache2"
          fi
          for conf_file in "/etc/apache2/sites-available/"*; do
            if [ -f "\$conf_file" ]; then
        
              server_name=$(grep -E "^\s*ServerName\s+" "\$conf_file" | awk '{print $2}')
              server_alias=$(grep -E "^\s*ServerAlias\s+" "\$conf_file" | awk '{print $2}')
              custom_log_info=$(grep -E "^\s*CustomLog\s+" "\$conf_file")
        
              if [ -z "\$server_name" ]; then
                server_name="\$server_alias"
              fi
              if [ -z "\$server_name" ]; then
                server_name="vhost.unk"
              fi
              if [ -n "\$custom_log_info" ]; then
        
                custom_log_path=$(echo "\$custom_log_info" | awk '{print $2}' | tr -d '"' | sed "s|\\\${APACHE_LOG_DIR}|\$apache_log_dir|g")
                log_format=$(echo "\$custom_log_info" | awk '{print $3}')
        
                if [ "\$log_format" == "combined" ] || [ "\$log_format" == "common" ] || [ -z "\$log_format" ]; then
                  while read file; do
                    if [[ "\$file" == *.gz ]]; then
                      zcat "\$file" | awk -v fname="\$server_name" '{print fname" "$1}'
                    else
                      cat "\$file" | awk -v fname="\$server_name" '{print fname" "$1}'
                    fi
                  done< <(find "$(dirname \$custom_log_path)" -type f -name "$(basename \$custom_log_path)*")
                fi
              fi
            fi
          done | sort | uniq -c | awk '$1 >= 3' | sort -nr | gzip -c >/opt/logparser/apache.txt.gz
          
          if [ -f /opt/logparser/apache.txt.gz ]; then
            curl --silent -X POST \
              -H "Content-Type: multipart/form-data" \
              -F "data=@/opt/logparser/apache.txt.gz" \
              {$url}/logparser/{$server->secret} \
              > /dev/null
          fi
        fi
    fi
fi

# Parse local history to get back dropped metrics and events
# if [ -f /var/log/osquery/osqueryd.snapshots.log ] && [ -f /var/log/osquery/osqueryd.results.log ]; then
# 
#   cat /var/log/osquery/osqueryd.snapshots.log /var/log/osquery/osqueryd.results.log \
#     | grep -Eai "$(date +"%a %b %d")" \
#     | gzip -c >/opt/logparser/osquery.jsonl.gz
# 
#   if [ -f /opt/logparser/osquery.jsonl.gz ]; then
#     curl -X POST \
#       -H "Content-Type: multipart/form-data" \
#       -F "data=@/opt/logparser/osquery.jsonl.gz" \
#       {$url}/logparser/{$server->secret}
#     rm -f /opt/logparser/osquery.jsonl.gz
#   fi
# fi

EOT;
    }

    /** @deprecated */
    public static function configLogParserWindows(YnhServer $server): string
    {
        $url = app_url();
        return <<< EOT
# TODO

Write-Host "TODO!"

EOT;
    }

    public static function configLogAlert(YnhServer $server): array
    {
        $url = app_url();
        $path = ($server->platform === OsqueryPlatformEnum::WINDOWS) ? "C:\\Program Files\\osquery\\log\\osqueryd.*.log" : "/var/log/osquery/osqueryd.*.log";
        return ["monitors" => [
            [
                "name" => "Monitor Osquery Daemon Output",
                "path" => $path,
                "match" => ".*",
                "regexp" => true,
                "url" => "{$url}/logalert/{$server->secret}"
            ]
        ],
            "sleep" => 5,
            "echo" => false,
            "verbose" => 1
        ];
    }

    public static function configPerforma(YnhServer $server): array
    {
        return [
            'enabled' => true,
            'host' => $server->user()->first()->performa_domain,
            'secret_key' => $server->user()->first()->performa_secret,
            'group' => '',
            'proto' => 'https:',
            'socket_opts' => [
                'rejectUnauthorized' => false
            ]
        ];
    }

    public static function configOsquery(): array
    {
        $schedule = [];
        (new OsqueryRulesProcedure())
            ->list(new JsonRpcRequest())['rules']
            ->each(function (YnhOsqueryRule $rule) use (&$schedule) {
                $schedule[$rule->name] = [
                    'query' => $rule->query,
                    'interval' => $rule->interval,
                    'removed' => $rule->removed,
                    'snapshot' => $rule->snapshot,
                    'platform' => $rule->platform->value,
                ];
                if ($rule->version) {
                    $schedule[$rule->name]['version'] = $rule->version;
                }
            });
        return [
            "options" => [
                "logger_snapshot_event_type" => "true",
                "schedule_splay_percent" => 10
            ],
            "schedule" => $schedule,
            "file_paths" => [
                "configuration" => [
                    "/etc/passwd",
                    "/etc/shadow",
                    "/etc/ld.so.preload",
                    "/etc/ld.so.conf",
                    "/etc/ld.so.conf.d/%%",
                    "/etc/pam.d/%%",
                    "/etc/resolv.conf",
                    "/etc/rc%/%%",
                    "/etc/my.cnf",
                    "/etc/modules",
                    "/etc/hosts",
                    "/etc/hostname",
                    "/etc/fstab",
                    "/etc/crontab",
                    "/etc/cron%/%%",
                    "/etc/init/%%",
                    "/etc/rsyslog.conf"
                ],
                "binaries" => [
                    "/usr/bin/%%",
                    "/usr/sbin/%%",
                    "/bin/%%",
                    "/sbin/%%",
                    "/usr/local/bin/%%",
                    "/usr/local/sbin/%%"
                ]
            ],
            "events" => [],
            "packs" => [],
        ];
    }

    public static function monitorLinuxServer(YnhServer $server): string
    {
        $url = app_url();
        $whitelist = collect(config('towerify.adversarymeter.ip_addresses'))
            ->map(fn(string $ip) => "sed -i '/^ignoreip/ { /{$ip}/! s/$/ {$ip}/ }' /etc/fail2ban/jail.conf")
            ->join("\n");
        $installPerforma = '';
        $updatePerformaConfig = '';
        if (!is_null($server->user()->first()?->performa_domain)) {
            $installPerforma = <<<EOT

# Install performa-satellite
if [ ! -f /opt/performa/config.json ]; then 
    mkdir -p /opt/performa
    curl -L https://github.com/jhuckaby/performa-satellite/releases/latest/download/performa-satellite-linux-x64 > /opt/performa/satellite.bin
    chmod 755 /opt/performa/satellite.bin
    /opt/performa/satellite.bin --install
fi

EOT;
            $updatePerformaConfig = <<<EOT

# Update performa-satellite configuration
wget -O /opt/performa/config2.json {$url}/performa/{$server->secret}

if [ -s /opt/performa/config2.json ]; then
  if jq empty /opt/performa/config2.json; then
    mv -f /opt/performa/config2.json /opt/performa/config.json
  fi
else
  rm /opt/performa/config2.json
fi

EOT;

        }
        return <<<EOT
#!/bin/bash

apt install wget curl jq -y

# Install Osquery
if [ ! -f /etc/osquery/osquery.conf ]; then
    wget https://pkg.osquery.io/deb/osquery_5.11.0-1.linux_amd64.deb
    apt install ./osquery_5.11.0-1.linux_amd64.deb
    rm osquery_5.11.0-1.linux_amd64.deb
fi

# Install LogParser
if [ ! -f /opt/logparser/parser ]; then 
  mkdir -p /opt/logparser
fi

# Install LogAlert
if [ ! -f /opt/logalert/config.json ]; then 
  mkdir -p /opt/logalert
  curl -L https://github.com/jhuckaby/logalert/releases/latest/download/logalert-linux-x64 >/opt/logalert/logalert.bin
  chmod 755 /opt/logalert/logalert.bin
fi
{$installPerforma}
# Stop Osquery then LogAlert because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)  
systemctl stop osqueryd
systemctl stop logalert

# For debian-like oses, get the list of installed packages
if [ -f /etc/os-release ]; then

    id_like=$(grep '^ID_LIKE=' /etc/os-release | cut -d'=' -f2 | tr -d '"')
    
    if [ -z "\$id_like" ]; then
      id_like=$(grep '^ID=' /etc/os-release | cut -d'=' -f2 | tr -d '"')
    fi

    # Ensure that the OS is debian-based
    if [[ "\$id_like" == *"debian"* ]]; then

      apt_packages=$(apt list --installed 2>/dev/null | awk -F'[ /]' '{print $1 " " $3 " " $4 " apt"}' | tail -n +2)
      snap_packages=$(snap list 2>/dev/null | awk 'NR>1 {print $1 " " $2 " " $3 " snap"}')
      dpkg_packages=$(dpkg-query -W -f='\${binary:Package} \${Version} \${Architecture} dpkg\\n' 2>/dev/null)
      all_packages=$(echo -e "\$apt_packages\\n\$snap_packages\\n\$dpkg_packages" | sort -u)
    
      echo "\$all_packages" | awk '{
        key = $1 " " $2 " " $3
        if (key in seen) {
          seen[key] = seen[key] "," $4
        } else {
          seen[key] = $4
        }
      } END {
        for (key in seen) {
          print key " " seen[key]
        }
      }' \
      | sort \
      | awk -v hostname="$(hostname)" -v epoch="$(date +'%s')" -v date="$(LC_TIME=C date +'%a %b %e %T %Y %Z')" -v uid="$(tr -dc A-Za-z0-9 </dev/urandom | head -c 15; echo)" '{print "{\"row\":0,\"name\":\"deb_packages_installed_snapshot\",\"hostIdentifier\":\""hostname"\",\"calendarTime\":\""date"\",\"unixTime\":\""epoch"\",\"epoch\":0,\"counter\":0,\"numerics\":0,\"action\":\"snapshot\",\"columns\":{\"uid\":\""uid"\",\"name\":\""$1"\",\"version\":\""$2"\",\"arch\":\""$3"\",\"manager\":\""$4"\",\"status\":\"installed\"}}"}' \
      | gzip -c >/opt/logparser/osquery.jsonl.gz
        
      if [ -f /opt/logparser/osquery.jsonl.gz ]; then
        curl -X POST \
          -H "Content-Type: multipart/form-data" \
          -F "data=@/opt/logparser/osquery.jsonl.gz" \
          {$url}/logparser/{$server->secret}
        rm -f /opt/logparser/osquery.jsonl.gz
      fi
    fi
fi

# Parse local history to get back dropped metrics and events
if [ -f /var/log/osquery/osqueryd.snapshots.log ] && [ -f /var/log/osquery/osqueryd.results.log ]; then

  cat /var/log/osquery/osqueryd.snapshots.log /var/log/osquery/osqueryd.results.log \
    | gzip -c >/opt/logparser/osquery.jsonl.gz

  if [ -f /opt/logparser/osquery.jsonl.gz ]; then
    curl -X POST \
      -H "Content-Type: multipart/form-data" \
      -F "data=@/opt/logparser/osquery.jsonl.gz" \
      {$url}/logparser/{$server->secret}
    rm /opt/logparser/osquery.jsonl.gz
  fi
fi
        
# Update LogAlert configuration
wget -O /opt/logalert/config2.json {$url}/logalert/{$server->secret}

if [ -s /opt/logalert/config2.json ]; then
  if jq empty /opt/logalert/config2.json; then
    mv -f /opt/logalert/config2.json /opt/logalert/config.json
  fi
else
  rm /opt/logalert/config2.json
fi

# Update LogParser configuration
wget -O /opt/logparser/parser2 {$url}/logparser/{$server->secret}

if [ -s /opt/logparser/parser2 ]; then
  if { bash -n /opt/logparser/parser2; } then
    mv -f /opt/logparser/parser2 /opt/logparser/parser
    chmod +x /opt/logparser/parser
  fi
else
    rm /opt/logparser/parser2
fi
{$updatePerformaConfig}
# Set LogAlert as a daemon
echo '[Unit]' > /etc/systemd/system/logalert.service
echo 'Description=LogAlert (cywise)' >> /etc/systemd/system/logalert.service
echo '[Service]' >> /etc/systemd/system/logalert.service
echo 'ExecStart=/opt/logalert/logalert.bin' >> /etc/systemd/system/logalert.service
echo '[Install]' >> /etc/systemd/system/logalert.service
echo 'WantedBy=multi-user.target' >> /etc/systemd/system/logalert.service

# Update Osquery configuration
wget -O /etc/osquery/osquery2.conf {$url}/osquery/{$server->secret}

if [ -s /etc/osquery/osquery2.conf ]; then
  if jq empty /etc/osquery/osquery2.conf; then
    mv -f /etc/osquery/osquery2.conf /etc/osquery/osquery.conf
  fi
else
  rm /etc/osquery/osquery2.conf
fi

# Set Osquery flags
echo '--config_plugin=filesystem' > /etc/osquery/osquery.flags # overwrite file!
echo '--disable_events=false' >> /etc/osquery/osquery.flags
echo '--disable_logging=false' >> /etc/osquery/osquery.flags
echo '--enable_file_events=true' >> /etc/osquery/osquery.flags
echo '--enable_ntfs_publisher=true' >> /etc/osquery/osquery.flags
echo '--enable_syslog=true' >> /etc/osquery/osquery.flags
echo '--force=true' >> /etc/osquery/osquery.flags
echo '--audit_allow_config=true' >> /etc/osquery/osquery.flags
echo '--audit_allow_sockets=true' >> /etc/osquery/osquery.flags
echo '--audit_persist=true' >> /etc/osquery/osquery.flags
echo '--disable_audit=false' >> /etc/osquery/osquery.flags
echo '--events_expiry=1' >> /etc/osquery/osquery.flags
echo '--events_max=500000' >> /etc/osquery/osquery.flags
echo '--logger_min_status=1' >> /etc/osquery/osquery.flags
echo '--logger_plugin=filesystem' >> /etc/osquery/osquery.flags
echo '--schedule_default_interval=3600' >> /etc/osquery/osquery.flags
echo '--verbose=false' >> /etc/osquery/osquery.flags
echo '--watchdog_memory_limit=350' >> /etc/osquery/osquery.flags
echo '--watchdog_utilization_limit=130' >> /etc/osquery/osquery.flags
echo '--worker_threads=2' >> /etc/osquery/osquery.flags

# Parse web logs every hour
cat <(fgrep -i -v '/opt/logparser/parser' <(crontab -l)) <(echo '0 * * * * /opt/logparser/parser') | crontab -

# Drop Osquery daemon's output every sunday at 01:11 am
cat <(fgrep -i -v 'rm /var/log/osquery/osqueryd.results.log /var/log/osquery/osqueryd.snapshots.log' <(crontab -l)) <(echo '11 1 * * 0 rm /var/log/osquery/osqueryd.results.log /var/log/osquery/osqueryd.snapshots.log') | crontab -

# Drop LogAlert's logs every day at 02:22 am
cat <(fgrep -i -v 'rm /opt/logalert/log.txt' <(crontab -l)) <(echo '22 2 * * * rm /opt/logalert/log.txt') | crontab -

# Auto-update the server every day at 03:33 am
cat <(crontab -l | sed '/curl -s https:\/\/.*\/update\/.*| bash/d') <(echo '33 3 * * * curl -s {$url}/update/{$server->secret} | bash') | crontab -

# Delete entry that call old domain app.towerify.io
crontab -l | grep -v "app\.towerify\.io" | crontab -

# Start LogAlert then Osquery because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)  
systemctl start logalert
systemctl start osqueryd

# If fail2ban is up-and-running, whitelist Cywise's IP addresses
if systemctl is-active --quiet fail2ban; then
  if [ -f /etc/fail2ban/jail.conf ]; then
    {$whitelist}
    systemctl restart fail2ban
  fi
fi

EOT;
    }

    public static function monitorWindowsServer(YnhServer $server): string
    {
        $url = app_url();
        $installPerforma = '';
        $updatePerformaConfig = '';
        $updatePerformaScheduledTask = '';
        if (!is_null($server->user()->first()->performa_domain)) {
            $installPerforma = <<<EOT
# Install or update performa-satellite
\$performaPath = "\$cywisePath\performa"
if (-not (Test-Path "\$performaPath\performa-satellite-win-x64.exe")) {
    New-Item -Path \$performaPath -ItemType Directory -Force
    Invoke-WebRequest -Uri "{$url}/bin/performa-satellite-win-x64.exe" -OutFile "\$performaPath\performa-satellite-win-x64.exe"
}
EOT;
            $updatePerformaConfig = <<<EOT
# Update performa-satellite configuration
Invoke-WebRequest -Uri "{$url}/performa/{$server->secret}" -OutFile "\$performaPath\config2.json"

if (Test-Path "\$performaPath\config2.json") {
    # Check if the file is a valid JSON
    try {
        \$config2 = Get-Content "\$performaPath\config2.json" | ConvertFrom-Json
        if (\$null -ne \$config2) {
            # Replace config.json with config2.json
            Copy-Item "\$performaPath\config2.json" "\$performaPath\config.json" -Force
            Remove-Item "\$performaPath\config2.json" -Force
        }
    } catch {
        Write-Output "Erreur lors de la conversion du fichier config2.json en JSON."
    }
}

# Collect CPU, memory and disks metrics every 5 minutes
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-File ""\$cywisePath\localMetrics.ps1"""  -TaskName "LocalMetrics" -ExecutionType Custom -RepeatInterval 300
EOT;
            $updatePerformaScheduledTask = <<<EOT
# Send metric to performa every minute
CreateOrUpdate-ScheduledTask -Executable "\$performaPath\performa-satellite-win-x64.exe" -Arguments "--hostname {$server->name}"  -TaskName "performa-satellite" -ExecutionType Custom -RepeatInterval 60
EOT;
        }
        return <<<EOT
# Create cywise directory
\$cywisePath = "C:\Cywise"
if (-not (Test-Path "\$cywisePath")) {
    New-Item -Path \$cywisePath -ItemType Directory -Force
}

function CreateOrUpdate-ScheduledTask {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = \$true)]
        [string]\$TaskName,

        [Parameter(Mandatory = \$true)]
        [string]\$Executable,

        [Parameter(Mandatory = \$false)]
        [string]\$Arguments = "",

        [Parameter(Mandatory = \$true)]
        [ValidateSet("Custom", "Daily", "Weekly")]
        [string]\$ExecutionType,

        [Parameter(Mandatory = \$false, ParameterSetName = "Custom")]
        [int]\$RepeatInterval = 3600,

        [Parameter(Mandatory = \$true, ParameterSetName = "Daily")]
        [string]\$TimeOfDay,

        [Parameter(Mandatory = \$true, ParameterSetName = "Weekly")]
        [int]\$DayOfWeek,

        [Parameter(Mandatory = \$true, ParameterSetName = "Weekly")]
        [string]\$TimeOfWeek
    )

    # Create an object to define the scheduled task parameters
    if ([string]::IsNullOrEmpty(\$Arguments)) {
        \$Action = New-ScheduledTaskAction -Execute \$Executable
    } else {
        \$Action = New-ScheduledTaskAction -Execute \$Executable -Argument \$Arguments
    }
    \$Settings = New-ScheduledTaskSettingsSet
    \$Principal = New-ScheduledTaskPrincipal -UserId "NT AUTHORITY\SYSTEM" -LogonType ServiceAccount

    # Define the trigger based on the execution type
    switch (\$ExecutionType) {
        "Custom" {
            \$TimeOfDay = [DateTime]::Parse("00:00")
            \$Trigger = New-ScheduledTaskTrigger -Once -At \$TimeOfDay -RepetitionInterval (New-TimeSpan -Seconds \$RepeatInterval) -RepetitionDuration (New-TimeSpan -Days 3650)
        }
        "Daily" {
            \$TimeOfDay = [DateTime]::Parse(\$TimeOfDay)
            \$Trigger = New-ScheduledTaskTrigger -Daily -At \$TimeOfDay
        }
        "Weekly" {
            \$TimeOfWeek = [DateTime]::Parse(\$TimeOfWeek)
            \$Trigger = New-ScheduledTaskTrigger -Weekly -At \$TimeOfWeek -DaysOfWeek \$DayOfWeek
        }
    }

    # Check if the task already exists
    if (\$null -ne (Get-ScheduledTask -TaskPath "\Cywise\" -TaskName \$TaskName -ErrorAction SilentlyContinue)) {
        # Update existing task
        Set-ScheduledTask -TaskPath "\Cywise\" -TaskName \$TaskName -Action \$Action -Principal \$Principal -Trigger \$Trigger -Settings \$Settings
    } else {
        # Create new task
        Register-ScheduledTask -TaskPath "\Cywise\" -TaskName \$TaskName -Action \$Action -Principal \$Principal -Trigger \$Trigger -Settings \$Settings
    }
}

# Install Osquery
# NOTA: the MSI package creates the osqueryd Windows Service as well
\$osqueryPath = "C:\Program Files\osquery"
if (-not (Test-Path "\$osqueryPath\osquery.conf")) {
    Invoke-WebRequest -Uri "https://pkg.osquery.io/windows/osquery-5.11.0.msi" -OutFile "\$cywisePath\osquery.msi"
    Start-Process msiexec.exe -ArgumentList "/i \$cywisePath\osquery.msi /quiet" -Wait
}

$installPerforma

# Install LogAlert
\$logAlertPath = "\$cywisePath\LogAlert"
if (-not (Test-Path "\$logAlertPath\config.json")) {
    New-Item -Path \$logAlertPath -ItemType Directory -Force
    Invoke-WebRequest -Uri "https://github.com/jhuckaby/logalert/releases/download/v1.0.4/logalert-win-x64.exe" -OutFile "\$logAlertPath\logalert.exe"
}

# Install a tool to create a service for LogAlert
# See: https://github.com/winsw/winsw/tree/v2.12.0
if (-not (Test-Path "\$logAlertPath\logalertd.exe")) {
    Invoke-WebRequest -Uri "https://github.com/winsw/winsw/releases/download/v2.12.0/WinSW-x64.exe" -OutFile "\$logAlertPath\logalertd.exe"
}

# Setup LogAlert service configuration
\$logalertd_conf = @"
id: logalert
name: LogAlert
description: Cywise LogAlert Service
executable: \$logAlertPath\logalert.exe
startmode: Automatic
logmode: EventLog
onFailure:
  - action: restart
"@
\$logalertd_conf | Set-Content -Path "\$logAlertPath\logalertd.yml"

# Setup LogAlert service
if (-not (Get-Service -Name "logalert" -ErrorAction SilentlyContinue)) {
    & \$logAlertPath\logalertd.exe install
}

# Stop Osquery then LogAlert because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)
Stop-Service osqueryd
Stop-Service logalert

# Parse local history to get back dropped metrics and events
if ((Test-Path "\$osqueryPath\log\osqueryd.snapshots.log") -And (Test-Path "\$osqueryPath\log\osqueryd.results.log")) {
    Get-Content "\$osqueryPath\log\osqueryd.snapshots.log", "\$osqueryPath\log\osqueryd.results.log" `
        | Set-Content -Path "\$cywisePath\osquery.jsonl" -Encoding ASCII

    # Explicitly load the System.Net.Http assembly
    Add-Type -AssemblyName "System.Net.Http"

    # Step 1: Compress the file into .gz
    if (Test-Path "\$cywisePath\osquery.jsonl.gz") {
        Remove-Item "\$cywisePath\osquery.jsonl.gz" -Force
    }

    # Open input and output streams
    \$fileStream = [System.IO.File]::OpenRead("\$cywisePath\osquery.jsonl")
    \$outFileStream = [System.IO.File]::Create("\$cywisePath\osquery.jsonl.gz")
    \$gzipStream = New-Object System.IO.Compression.GzipStream(\$outFileStream, [System.IO.Compression.CompressionMode]::Compress)

    # Copy data to the compressed file
    \$fileStream.CopyTo(\$gzipStream)

    # Close streams
    \$gzipStream.Dispose()
    \$fileStream.Dispose()
    \$outFileStream.Dispose()

    # Step 2: Prepare and send the POST request
    \$fileStream = [System.IO.File]::OpenRead("\$cywisePath\osquery.jsonl.gz")
    \$httpContent = [System.Net.Http.MultipartFormDataContent]::new()

    # Add the file to the form
    \$fileContent = [System.Net.Http.StreamContent]::new(\$fileStream)
    \$fileContent.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::new("application/gzip")
    \$httpContent.Add(\$fileContent, "data", (Get-Item "\$cywisePath\osquery.jsonl.gz").Name)

    # Add a User-Agent header to avoid server-related issues
    \$client = [System.Net.Http.HttpClient]::new()
    \$client.DefaultRequestHeaders.Add("User-Agent", "PowerShellCywise/1.0")

    # Send the POST request
    \$response = \$client.PostAsync("{$url}/logparser/{$server->secret}", \$httpContent).Result

    # Cleanup
    \$fileStream.Dispose()
    \$client.Dispose()

    Remove-Item "\$cywisePath\osquery.jsonl"
    Remove-Item "\$cywisePath\osquery.jsonl.gz"
}

# Update LogAlert configuration
Invoke-WebRequest -Uri "{$url}/logalert/{$server->secret}" -OutFile "\$logAlertPath\config2.json"

if (Test-Path "\$logAlertPath\config2.json") {
    # Check if the file is a valid JSON
    try {
        \$config2 = Get-Content "\$logAlertPath\config2.json" | ConvertFrom-Json
        if (\$null -ne \$config2) {
            # Replace config.json with config2.json
            Copy-Item "\$logAlertPath\config2.json" "\$logAlertPath\config.json" -Force
            Remove-Item "\$logAlertPath\config2.json" -Force            
        }
    } catch {
        Write-Output "Erreur lors de la conversion du fichier config2.json en JSON."
    }
}

$updatePerformaConfig

# Update Osquery configuration
Invoke-WebRequest -Uri "{$url}/osquery/{$server->secret}" -OutFile "\$osqueryPath\osquery2.conf"

if (Test-Path "\$osqueryPath\osquery2.conf") {
    # Check if the file is a valid JSON
    try {
        \$osquery2 = Get-Content "\$osqueryPath\osquery2.conf" | ConvertFrom-Json
        if (\$null -ne \$osquery2) {
            # Replace osquery.conf with osquery2.conf
            Copy-Item "\$osqueryPath\osquery2.conf" "\$osqueryPath\osquery.conf" -Force
            Remove-Item "\$osqueryPath\osquery2.conf" -Force            
        }
    } catch {
        Write-Output "Erreur lors de la conversion du fichier osquery2.json en JSON."
    }
}

# Update LogParser
Invoke-WebRequest -Uri "{$url}/logparser/{$server->secret}" -OutFile "\$cywisePath\logparser2.ps1" -ErrorAction SilentlyContinue

if (Test-Path "\$cywisePath\logparser2.ps1") {
    # Remplacer logparser.ps1 par logparser2.ps1
    Copy-Item "\$cywisePath\logparser2.ps1" "\$cywisePath\logparser.ps1" -Force
}

# Update localMetrics
Invoke-WebRequest -Uri "{$url}/localmetrics/{$server->secret}" -OutFile "\$cywisePath\localMetrics2.ps1" -ErrorAction SilentlyContinue

if (Test-Path "\$cywisePath\localMetrics2.ps1") {
    # Remplacer logparser.ps1 par localMetrics2.ps1
    Copy-Item "\$cywisePath\localMetrics2.ps1" "\$cywisePath\localMetrics.ps1" -Force
}

# Set Osquery flags
\$osquery_flags = @"
--disable_events=false
--enable_file_events=true
--audit_allow_config=true
--audit_allow_sockets
--audit_persist=true
--disable_audit=false
--events_expiry=1
--events_max=500000
--logger_min_status=1
--logger_plugin=filesystem
--watchdog_memory_limit=350
--watchdog_utilization_limit=130
"@
\$osquery_flags | Set-Content -Path "\$osqueryPath\osquery.flags"

# Start LogAlert then Osquery because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)
Start-Service logalert
Start-Service osqueryd

# Parse web logs every hour
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-File ""\$cywisePath\logparser.ps1"""  -TaskName "LogParser" -ExecutionType Custom -RepeatInterval 3600

# Drop Osquery daemon's output every sunday at 01:11 am
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-Command ""& { if (Test-Path '\$osqueryPath\log\osqueryd.results.log') { Remove-Item -Path '\$osqueryPath\log\osqueryd.results.log' -Force }; if (Test-Path '\$osqueryPath\log\osqueryd.snapshots.log') { Remove-Item -Path '\$osqueryPath\log\osqueryd.snapshots.log' -Force } }""" -TaskName "DeleteOsqueryLogFiles" -ExecutionType "Weekly" -DayOfWeek 0 -TimeOfWeek "1:11"

# Drop LogAlert's logs every day at 02:22 am
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-Command ""& { Remove-Item -Path '\$logAlertPath\LogAlert\log.txt' -Force }""" -TaskName "DeleteLogAlertLogFile" -ExecutionType "Daily" -TimeOfDay "2:22"

# Auto-update the server every day at 03:33 am
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-Command ""& { Invoke-WebRequest -Uri '{$url}/update/{$server->secret}' -UseBasicParsing | Invoke-Expression }""" -TaskName "AutoUpdate" -ExecutionType "Daily" -TimeOfDay "3:33"

# Collect CPU, memory and disks metrics every 5 minutes
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-File ""\$cywisePath\localMetrics.ps1"""  -TaskName "LocalMetrics" -ExecutionType Custom -RepeatInterval 300
$updatePerformaScheduledTask
EOT;
    }

    public static function monitorLocalMetricsWindows(YnhServer $server): string
    {
        $url = app_url();
        return <<<EOT
function Get-CpuMetrics() {
    # Retrieve data (first point)
    \$objService = Get-WmiObject -Class Win32_PerfRawData_PerfOS_Processor -Filter "Name='_Total'"
    \$userTime1 = \$objService.PercentUserTime
    \$systemTime1 = \$objService.PercentPrivilegedTime
    \$time1 = \$objService.TimeStamp_Sys100NS

    # Wait
    Start-Sleep -Seconds 1

    # Retrieve data (second point)
    \$objService = Get-WmiObject -Class Win32_PerfRawData_PerfOS_Processor -Filter "Name='_Total'"
    \$userTime2 = \$objService.PercentUserTime
    \$systemTime2 = \$objService.PercentPrivilegedTime
    \$time2 = \$objService.TimeStamp_Sys100NS

    # Calculate CPU usage
    \$PercentUserTime = [math]::Round(((\$userTime2 - \$userTime1) / (\$time2 - \$time1)) * 100, 2)
    \$PercentSystemTime = [math]::Round(((\$systemTime2 - \$systemTime1) / (\$time2 - \$time1)) * 100, 2)
    \$PercentIdleTime = 100 - \$PercentUserTime - \$PercentSystemTime

    return @{
        time_spent_idle_pct                = \$PercentIdleTime.ToString()
        time_spent_on_system_workloads_pct = \$PercentSystemTime.ToString()
        time_spent_on_user_workloads_pct   = \$PercentUserTime.ToString()
    }
}

function Get-DiskMetrics() {
    # Retrieve disk information
    \$disks = Get-WmiObject -Class Win32_LogicalDisk -Filter "DriveType=3"

    # Initialize variables
    \$total_space_gb = 0
    \$space_left_gb = 0

    # Loop through disks
    foreach (\$disk in \$disks) {
        # Calculate total size in GB
        \$total_space_gb += [math]::Round(\$disk.Size / 1GB, 2)

        # Calculate free space in GB
        \$space_left_gb += [math]::Round(\$disk.FreeSpace / 1GB, 2)
    }

    # Calculate others metrics
    \$used_space_gb = [math]::Round(\$total_space_gb - \$space_left_gb, 2)
    \$percent_available = [math]::Round((\$space_left_gb / \$total_space_gb) * 100, 1)
    \$percent_used = [math]::Round(100 - \$percent_available, 1)

    return @{
        '%_available'  = \$percent_available.ToString()
        '%_used'       = \$percent_used.ToString()
        space_left_gb  = \$space_left_gb.ToString()
        total_space_gb = \$total_space_gb.ToString()
        used_space_gb  = \$used_space_gb.ToString()
    }
}

function Get-MemoryMetrics() {
    \$total_space_gb = [math]::round(\$(Get-WmiObject -Class Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 2)
    \$space_left_gb = [math]::round((\$(Get-WmiObject -Class Win32_PerfFormattedData_PerfOS_Memory).AvailableBytes) / 1GB, 2)
    \$used_space_gb = [math]::round(\$total_space_gb - \$space_left_gb, 2)
    \$pct_available = [math]::round((\$space_left_gb / \$total_space_gb) * 100, 1)
    \$pct_used = [math]::round((\$used_space_gb / \$total_space_gb) * 100, 1)

    return @{
        '%_available'  = \$pct_available.ToString()
        '%_used'       = \$pct_used.ToString()
        space_left_gb  = \$space_left_gb.ToString()
        total_space_gb = \$total_space_gb.ToString()
        used_space_gb  = \$used_space_gb.ToString()
    }
}

function Generate-OsqueryJson {
    param (
        [string]\$Name,
        [hashtable]\$Columns
    )

    \$currentDate = Get-Date -Format "ddd MMM  d HH:mm:ss yyyy UTC"
    \$unixTime = [int][double]::Parse((Get-Date -UFormat %s).ToString())

    \$data = @{
        name           = \$Name
        hostIdentifier = \$env:COMPUTERNAME
        calendarTime   = \$currentDate
        unixTime       = \$unixTime
        epoch          = 0
        counter        = 0
        numerics       = \$false
        columns        = \$Columns
        action         = "snapshot"
    }

    return \$data | ConvertTo-Json -Compress
}

Generate-OsqueryJson -Name "processor_available_snapshot" -Columns \$(Get-CpuMetrics) | Out-File -Append -Encoding utf8 "C:\Program Files\osquery\log\osqueryd.snapshots.log"
Generate-OsqueryJson -Name "disk_available_snapshot" -Columns \$(Get-DiskMetrics) | Out-File -Append -Encoding utf8 "C:\Program Files\osquery\log\osqueryd.snapshots.log"
Generate-OsqueryJson -Name "memory_available_snapshot" -Columns \$(Get-MemoryMetrics) | Out-File -Append -Encoding utf8 "C:\Program Files\osquery\log\osqueryd.snapshots.log"

EOT;
    }

    /** @deprecated */
    public static function osInfos(Collection $servers): Collection
    {
        // {
        //      "arch":"x86_64",
        //      "build":null,
        //      "codename":"bullseye",
        //      "major":"11",
        //      "minor":"0",
        //      "name":"Debian GNU\/Linux",
        //      "patch":"0",
        //      "platform":"debian",
        //      "platform_like":null,
        //      "version":"11 (bullseye)"
        // }
        return $servers->isEmpty() ? collect() : collect(DB::select("
            SELECT DISTINCT 
                ynh_osquery.ynh_server_id,
                ynh_servers.name AS ynh_server_name,
                TIMESTAMP(ynh_osquery.calendar_time - SECOND(ynh_osquery.calendar_time)) AS `timestamp`,
                json_unquote(json_extract(ynh_osquery.columns, '$.arch')) AS architecture,
                json_unquote(json_extract(ynh_osquery.columns, '$.codename')) AS codename,
                CAST(json_unquote(json_extract(ynh_osquery.columns, '$.major')) AS INTEGER) AS major_version,
                CAST(json_unquote(json_extract(ynh_osquery.columns, '$.minor')) AS INTEGER) AS minor_version,
                json_unquote(json_extract(ynh_osquery.columns, '$.platform')) AS os,
                CASE
                  WHEN json_unquote(json_extract(ynh_osquery.columns, '$.patch')) = 'null' THEN NULL
                  ELSE CAST(json_unquote(json_extract(ynh_osquery.columns, '$.patch')) AS INTEGER)
                END AS patch_version
            FROM ynh_osquery
            INNER JOIN (
              SELECT 
                ynh_server_id, MAX(calendar_time) AS calendar_time 
              FROM ynh_osquery 
              WHERE name = 'os_version'
              GROUP BY ynh_server_id
            ) AS t ON t.ynh_server_id = ynh_osquery.ynh_server_id AND t.calendar_time = ynh_osquery.calendar_time
            INNER JOIN ynh_servers ON ynh_servers.id = ynh_osquery.ynh_server_id
            WHERE ynh_osquery.name = 'os_version'
            AND ynh_osquery.ynh_server_id IN ({$servers->pluck('id')->join(',')})
            ORDER BY timestamp DESC
        "));
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(YnhOsqueryRule::class, 'ynh_osquery_rule_id', 'id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(YnhServer::class, 'ynh_server_id', 'id');
    }

    public function isAdded(): bool
    {
        return $this->action === 'added';
    }

    public function isRemoved(): bool
    {
        return $this->action === 'removed';
    }

    public function logLine(): string
    {
        // Attributes server_name, server_ip_address, comments and score are loaded by EventsProcedure::list
        $criticality = $this->score ?? 0;
        $time = $this->calendar_time->utc()->format('Y-m-d H:i:s');
        $message = $this->message();
        return empty($message) ? '' : "{$time} - {$this->server_name} (ip address: {$this->server_ip_address}) - {$message} (criticality: {$criticality})";
    }

    public function message(): string
    {
        $msg = '';
        if ($this->score > 0) { // IoC
            $msg = $this->comments;
        } else { // Standard security event
            if ($this->name === 'last') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $msg = "L'utilisateur {$username} s'est connecté au serveur.";
                }
                if ($this->isRemoved()) {
                    $msg = "L'utilisateur {$username} s'est déconnecté du serveur.";
                }
            } else if ($this->name === 'shell_history') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $msg = "L'utilisateur {$username} a lancé la commande {$this->columns['command']}.";
                }
            } else if ($this->name === 'users') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $home = empty($this->columns['directory']) ? "" : " ({$this->columns['directory']})";
                    $msg = "L'utilisateur {$username}{$home} a été créé.";
                }
                if ($this->isRemoved()) {
                    $home = empty($this->columns['directory']) ? "" : " ({$this->columns['directory']})";
                    $msg = "L'utilisateur {$username}{$home} a été supprimé.";
                }
            } else if ($this->name === 'groups') {
                if ($this->isAdded()) {
                    $msg = "Le groupe {$this->columns['groupname']} a été créé.";
                }
                if ($this->isRemoved()) {
                    $msg = "Le groupe {$this->columns['groupname']} a été supprimé.";
                }
            } else if ($this->name === 'authorized_keys') {
                if ($this->isAdded()) {
                    $msg = "Une clef SSH a été ajoutée au trousseau {$this->columns['key_file']}.";
                }
                if ($this->isRemoved()) {
                    $msg = "Une clef SSH a été supprimée du trousseau {$this->columns['key_file']}.";
                }
            } else if ($this->name === 'user_ssh_keys') {
                $username = $this->columns['username'] === 'null' ? null : $this->columns['username'] ?? null;
                if ($this->isAdded()) {
                    $msg = "L'utilisateur {$username} a créé une clef SSH ({$this->columns['path']}).";
                }
                if ($this->isRemoved()) {
                    $msg = "L'utilisateur {$username} a supprimé une clef SSH ({$this->columns['path']}).";
                }
            } else if ($this->name === 'win_packages' ||
                $this->name === 'deb_packages' ||
                $this->name === 'portage_packages' ||
                $this->name === 'npm_packages' ||
                $this->name === 'python_packages' ||
                $this->name === 'rpm_packages' ||
                $this->name === 'homebrew_packages' ||
                $this->name === 'chocolatey_packages') {
                $type = match ($this->name) {
                    'win_packages' => 'win',
                    'deb_packages' => 'deb',
                    'portage_packages' => 'portage',
                    'npm_packages' => 'npm',
                    'python_packages' => 'python',
                    'rpm_packages' => 'rpm',
                    'homebrew_packages' => 'homebrew',
                    'chocolatey_packages' => 'chocolatey',
                    default => 'unknown',
                };
                if ($this->isAdded()) {
                    try {
                        $os = $this->operatingSystem($this->ynh_server_id);
                    } catch (\Exception $e) {
                        $os = null;
                    }
                    if (!$os) {
                        $cves = '';
                    } else {
                        $cves = YnhCve::appCves($os->os, $os->codename, $this->columns['name'], $this->columns['version'])
                            ->pluck('cve')
                            ->unique()
                            ->join(', ');
                    }
                    $warning = empty($cves) ? '' : "Attention, ce paquet est vulnérable: {$cves}.";
                    $msg = "Le paquet {$this->columns['name']} {$this->columns['version']} ({$type}) a été installé. {$warning}";
                }
                if ($this->isRemoved()) {
                    $msg = "Le paquet {$this->columns['name']} {$this->columns['version']} ({$type}) a été désinstallé.";
                }
            }
        }
        return $msg;
    }

    private function operatingSystem(int $serverId): ?object
    {
        return \Cache::remember('os_infos_' . $serverId, now()->addHours(24), function () use ($serverId) {
            return collect(DB::select("
                SELECT DISTINCT 
                    ynh_osquery.ynh_server_id,
                    ynh_servers.name AS ynh_server_name,
                    TIMESTAMP(ynh_osquery.calendar_time - SECOND(ynh_osquery.calendar_time)) AS `timestamp`,
                    json_unquote(json_extract(ynh_osquery.columns, '$.arch')) AS architecture,
                    json_unquote(json_extract(ynh_osquery.columns, '$.codename')) AS codename,
                    CAST(json_unquote(json_extract(ynh_osquery.columns, '$.major')) AS INTEGER) AS major_version,
                    CAST(json_unquote(json_extract(ynh_osquery.columns, '$.minor')) AS INTEGER) AS minor_version,
                    json_unquote(json_extract(ynh_osquery.columns, '$.platform')) AS os,
                    CASE
                      WHEN json_unquote(json_extract(ynh_osquery.columns, '$.patch')) = 'null' THEN NULL
                      ELSE CAST(json_unquote(json_extract(ynh_osquery.columns, '$.patch')) AS INTEGER)
                    END AS patch_version
                FROM ynh_osquery
                INNER JOIN (
                  SELECT 
                    ynh_server_id, MAX(calendar_time) AS calendar_time 
                  FROM ynh_osquery 
                  WHERE name = 'os_version'
                  GROUP BY ynh_server_id
                ) AS t ON t.ynh_server_id = ynh_osquery.ynh_server_id AND t.calendar_time = ynh_osquery.calendar_time
                INNER JOIN ynh_servers ON ynh_servers.id = ynh_osquery.ynh_server_id
                WHERE ynh_osquery.name = 'os_version'
                AND ynh_osquery.ynh_server_id = {$serverId}
                ORDER BY timestamp DESC
            "))->firstOrFail();
        });
    }
}
