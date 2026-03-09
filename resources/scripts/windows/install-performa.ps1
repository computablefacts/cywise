# Install or update performa-satellite
$performaPath = "$cywisePath\performa"
if (-not (Test-Path "$performaPath\performa-satellite-win-x64.exe")) {
    New-Item -Path $performaPath -ItemType Directory -Force
    Invoke-WebRequest -Uri "{url}/bin/performa-satellite-win-x64.exe" -OutFile "$performaPath\performa-satellite-win-x64.exe"
}
