function Get-CpuMetrics() {
    # Retrieve data (first point)
    $objService = Get-WmiObject -Class Win32_PerfRawData_PerfOS_Processor -Filter "Name='_Total'"
    $userTime1 = $objService.PercentUserTime
    $systemTime1 = $objService.PercentPrivilegedTime
    $time1 = $objService.TimeStamp_Sys100NS

    # Wait
    Start-Sleep -Seconds 1

    # Retrieve data (second point)
    $objService = Get-WmiObject -Class Win32_PerfRawData_PerfOS_Processor -Filter "Name='_Total'"
    $userTime2 = $objService.PercentUserTime
    $systemTime2 = $objService.PercentPrivilegedTime
    $time2 = $objService.TimeStamp_Sys100NS

    # Calculate CPU usage
    $PercentUserTime = [math]::Round((($userTime2 - $userTime1) / ($time2 - $time1)) * 100, 2)
    $PercentSystemTime = [math]::Round((($systemTime2 - $systemTime1) / ($time2 - $time1)) * 100, 2)
    $PercentIdleTime = 100 - $PercentUserTime - $PercentSystemTime

    return @{
        time_spent_idle_pct                = $PercentIdleTime.ToString()
        time_spent_on_system_workloads_pct = $PercentSystemTime.ToString()
        time_spent_on_user_workloads_pct   = $PercentUserTime.ToString()
    }
}

function Get-DiskMetrics() {
    # Retrieve disk information
    $disks = Get-WmiObject -Class Win32_LogicalDisk -Filter "DriveType=3"

    # Initialize variables
    $total_space_gb = 0
    $space_left_gb = 0

    # Loop through disks
    foreach ($disk in $disks) {
        # Calculate total size in GB
        $total_space_gb += [math]::Round($disk.Size / 1GB, 2)

        # Calculate free space in GB
        $space_left_gb += [math]::Round($disk.FreeSpace / 1GB, 2)
    }

    # Calculate others metrics
    $used_space_gb = [math]::Round($total_space_gb - $space_left_gb, 2)
    $percent_available = [math]::Round(($space_left_gb / $total_space_gb) * 100, 1)
    $percent_used = [math]::Round(100 - $percent_available, 1)

    return @{
        '%_available'  = $percent_available.ToString()
        '%_used'       = $percent_used.ToString()
        space_left_gb  = $space_left_gb.ToString()
        total_space_gb = $total_space_gb.ToString()
        used_space_gb  = $used_space_gb.ToString()
    }
}

function Get-MemoryMetrics() {
    $total_space_gb = [math]::round($(Get-WmiObject -Class Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 2)
    $space_left_gb = [math]::round(($(Get-WmiObject -Class Win32_PerfFormattedData_PerfOS_Memory).AvailableBytes) / 1GB, 2)
    $used_space_gb = [math]::round($total_space_gb - $space_left_gb, 2)
    $pct_available = [math]::round(($space_left_gb / $total_space_gb) * 100, 1)
    $pct_used = [math]::round(($used_space_gb / $total_space_gb) * 100, 1)

    return @{
        '%_available'  = $pct_available.ToString()
        '%_used'       = $pct_used.ToString()
        space_left_gb  = $space_left_gb.ToString()
        total_space_gb = $total_space_gb.ToString()
        used_space_gb  = $used_space_gb.ToString()
    }
}

function Generate-OsqueryJson {
    param (
        [string]$Name,
        [hashtable]$Columns
    )

    $currentDate = Get-Date -Format "ddd MMM  d HH:mm:ss yyyy UTC"
    $unixTime = [int][double]::Parse((Get-Date -UFormat %s).ToString())

    $data = @{
        name           = $Name
        hostIdentifier = $env:COMPUTERNAME
        calendarTime   = $currentDate
        unixTime       = $unixTime
        epoch          = 0
        counter        = 0
        numerics       = $false
        columns        = $Columns
        action         = "snapshot"
    }

    return $data | ConvertTo-Json -Compress
}

Generate-OsqueryJson -Name "processor_available_snapshot" -Columns $(Get-CpuMetrics) | Out-File -Append -Encoding utf8 "C:\Program Files\osquery\log\osqueryd.snapshots.log"
Generate-OsqueryJson -Name "disk_available_snapshot" -Columns $(Get-DiskMetrics) | Out-File -Append -Encoding utf8 "C:\Program Files\osquery\log\osqueryd.snapshots.log"
Generate-OsqueryJson -Name "memory_available_snapshot" -Columns $(Get-MemoryMetrics) | Out-File -Append -Encoding utf8 "C:\Program Files\osquery\log\osqueryd.snapshots.log"
