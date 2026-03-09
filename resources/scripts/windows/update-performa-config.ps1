# Update performa-satellite configuration
Invoke-WebRequest -Uri "{url}/performa/{secret}" -OutFile "$performaPath\config2.json"

if (Test-Path "$performaPath\config2.json") {
    # Check if the file is a valid JSON
    try {
        $config2 = Get-Content "$performaPath\config2.json" | ConvertFrom-Json
        if ($null -ne $config2) {
            # Replace config.json with config2.json
            Copy-Item "$performaPath\config2.json" "$performaPath\config.json" -Force
            Remove-Item "$performaPath\config2.json" -Force
        }
    } catch {
        Write-Output "Erreur lors de la conversion du fichier config2.json en JSON."
    }
}

# Collect CPU, memory and disks metrics every 5 minutes
CreateOrUpdate-ScheduledTask -Executable "powershell.exe" -Arguments "-File ""$cywisePath\localMetrics.ps1"""  -TaskName "LocalMetrics" -ExecutionType Custom -RepeatInterval 300
