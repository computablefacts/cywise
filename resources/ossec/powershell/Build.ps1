$sourceScript = Get-Content -Path '.\src\Test-OssecRules.ps1' -Raw
$outputPath = '.\build\Test-OssecRules.ps1'
$utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)

$pattern = '^\. "\$PSScriptRoot/(.+)"$'
$scriptParts = $sourceScript -split '\r?\n' | ForEach-Object {
    if ($_ -match $pattern) {
        $importPath = Join-Path './src' $Matches[1]
        (Get-Content $importPath -Raw) -replace "`r`n", "`n"
    } else {
        $_
    }
}
$script = $scriptParts -join "`n"
[System.IO.File]::WriteAllText((Join-Path $PWD $outputPath), $script, $utf8WithoutBom)

$outputPathWithoutRules = '.\Test-OssecRules.ps1'
$startIndex = $script.IndexOf("@'") + 3
$endIndex = $script.IndexOf("'@") - 1
$script = $script.Substring(0, $startIndex) + "__PUT_RULES_HERE__" + $script.Substring($endIndex)
[System.IO.File]::WriteAllText((Join-Path $PWD $outputPathWithoutRules), $script, $utf8WithoutBom)
