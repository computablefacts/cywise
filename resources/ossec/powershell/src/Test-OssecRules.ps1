if ($PSVersionTable.PSVersion.Major -lt 6) {
    Write-Host "Ce script doit être exécuté avec PowerShell 6 ou supérieur." -ForegroundColor Yellow
    Write-Host "Suivez ce lien vers la documentation officielle pour installer la dernière version de PowerShell :"
    Write-Host "  https://learn.microsoft.com/fr-fr/powershell/scripting/install/installing-powershell-on-windows#msi"
    exit 1
}
 
. "$PSScriptRoot/lib/ExceptionList.ps1"
. "$PSScriptRoot/lib/Files.ps1"
. "$PSScriptRoot/lib/Process.ps1"
. "$PSScriptRoot/lib/Registry.ps1"
. "$PSScriptRoot/Display.ps1"
. "$PSScriptRoot/Evaluate.ps1"

function Test-RulesList {
    param(
        [string[]]$RulesList,
        [switch]$Json
    )

    $ctx = @{
        'file_exists'           = { FileExists -filePath $args[0] }
        'directory_exists'      = { DirectoryExists -directoryPath $args[0] }
        'registry_entry_exists' = { RegistryEntryExists -entry $args[0] }
        'fetch_file'            = { FetchFile -file $args[0] }
        'list_files'            = { ListFiles -Path $args[0] }
        'fetch_registry_keys'   = { FetchRegistryKeys -entry $args[0] }
        'fetch_registry_value'  = { FetchRegistryValue -entry $args[0] -propertyName $args[1] }
        'execute'               = { InvokeRuleCommand -command $args[0] }
    }
    
    $failedCount = 0
    $passedCount = 0
    $errorCount = 0
    foreach ($rule in $rulesList) {
        $ruleObject = $rule | ConvertFrom-Json -AsHashtable
        Clear-ExceptionList
        $result = Evaluate $ctx $ruleObject
        $exceptions = Get-ExceptionList
        if ($exceptions.Count -gt 0) {
            $errorCount++
            $status = 'error'
        }
        else {
            if ($result) {
                $passedCount++
                $status = 'passed'
            }
            else {
                $failedCount++
                $status = 'failed'
            }
        }

        if ($Json) {
            $errors = @($exceptions | ForEach-Object {
                [ordered]@{
                    message = $_.Message
                    detail  = $_.Exception.Message
                }
            })
            [ordered]@{
                rule_name = $ruleObject['rule_name']
                status    = $status
                errors    = $errors
            } | ConvertTo-Json -Compress -Depth 5
        }
        else {
            Show-RuleResult $result $ruleObject $exceptions
        }
    }

    if (-not $Json) {
        Show-TestResult $passedCount $failedCount $errorCount
    }
}

function Test-OssecRules {
    param(
        [string]$RulesFile,
        [switch]$Json
    )

    # Déclaration de la variable $rules par défaut
    $rules = @'
{"rule_name":"Ensure 'Account lockout threshold' is set to '5 or fewer invalid logon attempt(s), but not 0'.","match_type":"all","references":["https:\/\/www.cisecurity.org\/white-papers\/cis-password-policy-guide\/"],"rules":[{"type":"command","cmd":"net.exe accounts","expr":["n:Lockout threshold:\\s+(\\d+) compare > 0"],"negate":false},{"type":"command","cmd":"net.exe accounts","expr":["n:Lockout threshold:\\s+(\\d+) compare <= 5"],"negate":false}]}
{"rule_name":"Ensure 'Audit Logon' is set to 'Success and Failure'.","match_type":"all","references":[],"rules":[{"type":"command","cmd":"auditpol.exe \/get \/subcategory:\"Logon\"","expr":["r:Success and Failure"],"negate":false}]}
'@
    
    if ($RulesFile) {
        $rulesList = Get-Content $RulesFile | Where-Object { $_ -match '\S' }
    }
    else {
        $rulesList = $rules -split "`n" | Where-Object { $_ -match '\S' }
    }
    
    Test-RulesList $rulesList -Json:$Json
}

# Appel de la fonction si le script est exécuté directement
if ($MyInvocation.InvocationName -ne '.') {
    Test-OssecRules @args
}
