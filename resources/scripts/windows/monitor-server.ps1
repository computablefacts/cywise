# Create cywise directory
$cywisePath = "C:\Cywise"
if (-not (Test-Path "$cywisePath")) {
    New-Item -Path $cywisePath -ItemType Directory -Force
}

function CreateOrUpdate-ScheduledTask {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        [string]$TaskName,

        [Parameter(Mandatory = $true)]
        [string]$Executable,

        [Parameter(Mandatory = $false)]
        [string]$Arguments = "",

        [Parameter(Mandatory = $true)]
        [ValidateSet("Custom", "Daily", "Weekly")]
        [string]$ExecutionType,

        [Parameter(Mandatory = $false, ParameterSetName = "Custom")]
        [int]$RepeatInterval = 3600,

        [Parameter(Mandatory = $true, ParameterSetName = "Daily")]
        [string]$TimeOfDay,

        [Parameter(Mandatory = $true, ParameterSetName = "Weekly")]
        [int]$DayOfWeek,

        [Parameter(Mandatory = $true, ParameterSetName = "Weekly")]
        [string]$TimeOfWeek
    )

    # Create an object to define the scheduled task parameters
    if ([string]::IsNullOrEmpty($Arguments)) {
        $Action = New-ScheduledTaskAction -Execute $Executable
    } else {
        $Action = New-ScheduledTaskAction -Execute $Executable -Argument $Arguments
    }
    $Settings = New-ScheduledTaskSettingsSet
    $Principal = New-ScheduledTaskPrincipal -UserId "NT AUTHORITY\SYSTEM" -LogonType ServiceAccount

    # Define the trigger based on the execution type
    switch ($ExecutionType) {
        "Custom" {
            $TimeOfDay = [DateTime]::Parse("00:00")
            $Trigger = New-ScheduledTaskTrigger -Once -At $TimeOfDay -RepetitionInterval (New-TimeSpan -Seconds $RepeatInterval) -RepetitionDuration (New-TimeSpan -Days 3650)
        }
        "Daily" {
            $TimeOfDay = [DateTime]::Parse($TimeOfDay)
            $Trigger = New-ScheduledTaskTrigger -Daily -At $TimeOfDay
        }
        "Weekly" {
            $TimeOfWeek = [DateTime]::Parse($TimeOfWeek)
            $Trigger = New-ScheduledTaskTrigger -Weekly -At $TimeOfWeek -DaysOfWeek $DayOfWeek
        }
    }

    # Check if the task already exists
    if ($null -ne (Get-ScheduledTask -TaskPath "\Cywise\" -TaskName $TaskName -ErrorAction SilentlyContinue)) {
        # Update existing task
        Set-ScheduledTask -TaskPath "\Cywise\" -TaskName $TaskName -Action $Action -Principal $Principal -Trigger $Trigger -Settings $Settings
    } else {
        # Create new task
        Register-ScheduledTask -TaskPath "\Cywise\" -TaskName $TaskName -Action $Action -Principal $Principal -Trigger $Trigger -Settings $Settings
    }
}

# Install Osquery
# NOTA: the MSI package creates the osqueryd Windows Service as well
$osqueryPath = "C:\Program Files\osquery"
if (-not (Test-Path "$osqueryPath\osquery.conf")) {
    Invoke-WebRequest -Uri "https://pkg.osquery.io/windows/osquery-5.11.0.msi" -OutFile "$cywisePath\osquery.msi"
    Start-Process msiexec.exe -ArgumentList "/i $cywisePath\osquery.msi /quiet" -Wait
}

{install_performa}

# Install LogAlert
$logAlertPath = "$cywisePath\LogAlert"
if (-not (Test-Path "$logAlertPath\config.json")) {
    New-Item -Path $logAlertPath -ItemType Directory -Force
    Invoke-WebRequest -Uri "https://github.com/jhuckaby/logalert/releases/download/v1.0.4/logalert-win-x64.exe" -OutFile "$logAlertPath\logalert.exe"
}

# Install a tool to create a service for LogAlert
# See: https://github.com/winsw/winsw/tree/v2.12.0
if (-not (Test-Path "$logAlertPath\logalertd.exe")) {
    Invoke-WebRequest -Uri "https://github.com/winsw/winsw/releases/download/v2.12.0/WinSW-x64.exe" -OutFile "$logAlertPath\logalertd.exe"
}

# Setup LogAlert service configuration
$logalertd_conf = @"
id: logalert
name: LogAlert
description: Cywise LogAlert Service
executable: $logAlertPath\logalert.exe
startmode: Automatic
logmode: EventLog
onFailure:
  - action: restart
"@
$logalertd_conf | Set-Content -Path "$logAlertPath\logalertd.yml"

# Setup LogAlert service
if (-not (Get-Service -Name "logalert" -ErrorAction SilentlyContinue)) {
    & $logAlertPath\logalertd.exe install
}

# Stop Osquery then LogAlert because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)
Stop-Service osqueryd
Stop-Service logalert

# Parse local history to get back dropped metrics and events
if ((Test-Path "$osqueryPath\log\osqueryd.snapshots.log") -And (Test-Path "$osqueryPath\log\osqueryd.results.log")) {
    Get-Content "$osqueryPath\log\osqueryd.snapshots.log", "$osqueryPath\log\osqueryd.results.log" `
        | Set-Content -Path "$cywisePath\osquery.jsonl" -Encoding ASCII

    # Explicitly load the System.Net.Http assembly
    Add-Type -AssemblyName "System.Net.Http"

    # Step 1: Compress the file into .gz
    if (Test-Path "$cywisePath\osquery.jsonl.gz") {
        Remove-Item "$cywisePath\osquery.jsonl.gz" -Force
    }

    # Open input and output streams
    $fileStream = [System.IO.File]::OpenRead("$cywisePath\osquery.jsonl")
    $outFileStream = [System.IO.File]::Create("$cywisePath\osquery.jsonl.gz")
    $gzipStream = New-Object System.IO.Compression.GzipStream($outFileStream, [System.IO.Compression.CompressionMode]::Compress)

    # Copy data to the compressed file
    $fileStream.CopyTo($gzipStream)

    # Close streams
    $gzipStream.Dispose()
    $fileStream.Dispose()
    $outFileStream.Dispose()

    # Step 2: Prepare and send the POST request
    $fileStream = [System.IO.File]::OpenRead("$cywisePath\osquery.jsonl.gz")
    $httpContent = [System.Net.Http.MultipartFormDataContent]::new()

    # Add the file to the form
    $fileContent = [System.Net.Http.StreamContent]::new($fileStream)
    $fileContent.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::new("application/gzip")
    $httpContent.Add($fileContent, "data", (Get-Item "$cywisePath\osquery.jsonl.gz").Name)

    # Add a User-Agent header to avoid server-related issues
    $client = [System.Net.Http.HttpClient]::new()
    $client.DefaultRequestHeaders.Add("User-Agent", "PowerShellCywise/1.0")

    # Send the POST request
    $response = $client.PostAsync("{url}/logparser/{secret}", $httpContent).Result

    # Cleanup
    $fileStream.Dispose()
    $client.Dispose()

    Remove-Item "$cywisePath\osquery.jsonl"
    Remove-Item "$cywisePath\osquery.jsonl.gz"
}

# Update LogAlert configuration
Invoke-WebRequest -Uri "{url}/logalert/{secret}" -OutFile "$logAlertPath\config2.json"

if (Test-Path "$logAlertPath\config2.json") {
    # Check if the file is a valid JSON
    try {
        $config2 = Get-Content "$logAlertPath\config2.json" | ConvertFrom-Json
        if ($null -ne $config2) {
            # Replace config.json with config2.json
            Copy-Item "$logAlertPath\config2.json" "$logAlertPath\config.json" -Force
            Remove-Item "$logAlertPath\config2.json" -Force            
        }
    } catch {
        Write-Output "Erreur lors de la conversion du fichier config2.json en JSON."
    }
}

{update_performa_config}

# Update Osquery configuration
Invoke-WebRequest -Uri "{url}/osquery/{secret}" -OutFile "$osqueryPath\osquery2.conf"

if (Test-Path "$osqueryPath\osquery2.conf") {
    # Check if the file is a valid JSON
    try {
        $osquery2 = Get-Content "$osqueryPath\osquery2.conf" | ConvertFrom-Json
        if ($null -ne $osquery2) {
            # Replace osquery.conf with osquery2.conf
            Copy-Item "$osqueryPath\osquery2.conf" "$osqueryPath\osquery.conf" -Force
            Remove-Item "$osqueryPath\osquery2.conf" -Force            
        }
    } catch {
        Write-Output "Erreur lors de la conversion du fichier osquery2.json en JSON."
    }
}

# TODO : remove deprecated LogParser script
if (Test-Path "$cywisePath\logparser.ps1") {
    Remove-Item "$cywisePath\logparser.ps1" -Force
}
if (Test-Path "$cywisePath\logparser2.ps1") {
    Remove-Item "$cywisePath\logparser2.ps1" -Force
}

# Update localMetrics
Invoke-WebRequest -Uri "{url}/localmetrics/{secret}" -OutFile "$cywisePath\localMetrics2.ps1" -ErrorAction SilentlyContinue

if (Test-Path "$cywisePath\localMetrics2.ps1") {
    # Remplacer localMetrics2.ps1 par localMetrics.ps1
    Copy-Item "$cywisePath\localMetrics2.ps1" "$cywisePath\localMetrics.ps1" -Force
}

# Set Osquery flags
$osquery_flags = @"
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
$osquery_flags | Set-Content -Path "$osqueryPath\osquery.flags"

# Start LogAlert then Osquery because reloading resets LogAlert internal state (see https://github.com/jhuckaby/logalert for details)
Start-Service logalert
Start-Service osqueryd

# Drop Osquery daemon's output every sunday at 01:11 am
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-Command ""& { if (Test-Path '$osqueryPath\log\osqueryd.results.log') { Remove-Item -Path '$osqueryPath\log\osqueryd.results.log' -Force }; if (Test-Path '$osqueryPath\log\osqueryd.snapshots.log') { Remove-Item -Path '$osqueryPath\log\osqueryd.snapshots.log' -Force } }""" -TaskName "DeleteOsqueryLogFiles" -ExecutionType "Weekly" -DayOfWeek 0 -TimeOfWeek "1:11"

# Drop LogAlert's logs every day at 02:22 am
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-Command ""& { Remove-Item -Path '$logAlertPath\LogAlert\log.txt' -Force }""" -TaskName "DeleteLogAlertLogFile" -ExecutionType "Daily" -TimeOfDay "2:22"

# Auto-update the server every day at 03:33 am
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-Command ""& { Invoke-WebRequest -Uri '{url}/update/{secret}' -UseBasicParsing | Invoke-Expression }""" -TaskName "AutoUpdate" -ExecutionType "Daily" -TimeOfDay "3:33"

# Collect CPU, memory and disks metrics every 5 minutes
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-File ""$cywisePath\localMetrics.ps1"""  -TaskName "LocalMetrics" -ExecutionType Custom -RepeatInterval 300

# Delete entry that parse web logs every hour
Unregister-ScheduledTask -TaskName LogParser -Confirm:$false

{update_performa_scheduled_task}
