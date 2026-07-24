if ($PSVersionTable.PSVersion.Major -lt 6) {
    Write-Host "Ce script doit être exécuté avec PowerShell 6 ou supérieur." -ForegroundColor Yellow
    Write-Host "Suivez ce lien vers la documentation officielle pour installer la dernière version de PowerShell :"
    Write-Host "  https://learn.microsoft.com/fr-fr/powershell/scripting/install/installing-powershell-on-windows#msi"
    exit 1
}
 
# Define a global variable to store the exceptions list
$global:ExceptionList = @()

# Function to add an exception and a message in the list
function Add-Exception {
  param (
    [Parameter(Mandatory = $true)]
    [System.Exception]$Exception,
    [Parameter(Mandatory = $true)]
    [string]$Message
  )
  $global:ExceptionList += [PSCustomObject]@{
    Exception = $Exception
    Message   = $Message
  }
}

# Function to get the exceptions list
function Get-ExceptionList {
  return $global:ExceptionList
}

# Function to remove all exceptions from the list
function Clear-ExceptionList {
  $global:ExceptionList = @()
}

function DirectoryExists {
  param (
    [string]$directoryPath
  )

  return Test-Path -Path $directoryPath -PathType Container
}

function FileExists {
  param (
    [string]$filePath
  )

  return Test-Path -Path $filePath -PathType Leaf
}

function ListFiles {
  param (
    [string]$Path
  )

  try {
    Get-ChildItem -Path $Path -ErrorAction Stop | Select-Object -ExpandProperty FullName
  }
  catch {
    throw $_
  }
}

function FetchFile {
  param (
    [string]$file
  )

  try {
    Get-Content -Path $file -ErrorAction Stop
  }
  catch {
    throw $_
  }
}

function InvokeRuleCommand {
  param(
    [string]$command
  )
  
  try {
    $output = Invoke-Expression -Command "$command 2>&1"
    return $output -split "`n"
  }
  catch {
    Add-Exception `
      -Message "Erreur lors de l'exÃ©cution de la commande '$command'." `
      -Exception $_.Exception
    return $null
  }
}

function Convert-RegistryKey {
  param (
      [string]$Key
  )

  # Dictionnaire des remplacements
  $replacements = @{
      "HKEY_LOCAL_MACHINE\" = "HKLM:\"
      "HKEY_CURRENT_USER\" = "HKCU:\"
      "HKEY_CLASSES_ROOT\" = "HKCR:\"
      "HKEY_USERS\" = "HKU:\"
      "HKEY_CURRENT_CONFIG\" = "HKCC:\"
  }

  foreach ($fullKey in $replacements.Keys) {
      if ($Key -like "$fullKey*") {
          return $Key -replace [regex]::Escape($fullKey), $replacements[$fullKey]
      }
  }

  # Retourner la clÃ© inchangÃ©e si aucun remplacement n'est trouvÃ©
  return $Key
}

function RegistryEntryExists {
  param (
    [string]$entry
  )

  $convertedPath = Convert-RegistryKey -Key $entry

  return Test-Path -Path $convertedPath
}

function FetchRegistryKeys {
  param (
    [string]$entry
  )

  try {
    $convertedPath = Convert-RegistryKey -Key $entry
    $keys = (Get-Item -Path $convertedPath -ErrorAction Stop).Property
    return $keys
  }
  catch {
    throw $_
  }
}

function FetchRegistryValue {
  param (
    [string]$entry,
    [string]$propertyName
  )
  
  try {
    $convertedPath = Convert-RegistryKey -Key $entry
    Get-ItemPropertyValue -Path $convertedPath -Name $propertyName -ErrorAction Stop
  }
  catch {
    throw $_
  }
}

# DÃ©finition des constantes de couleur ANSI
#$ANSI_BLACK = "`e[30m"
#$ANSI_RED = "`e[31m"
$ANSI_GREEN = "`e[32m"
$ANSI_YELLOW = "`e[33m"
#$ANSI_BLUE = "`e[34m"
#$ANSI_MAGENTA = "`e[35m"
#$ANSI_CYAN = "`e[36m"
#$ANSI_WHITE = "`e[37m"
#$ANSI_BRIGHT_BLACK = "`e[90m"
$ANSI_BRIGHT_RED = "`e[91m"
$ANSI_BRIGHT_GREEN = "`e[92m"
$ANSI_BRIGHT_YELLOW = "`e[93m"
$ANSI_BRIGHT_BLUE = "`e[94m"
$ANSI_BRIGHT_MAGENTA = "`e[95m"
#$ANSI_BRIGHT_CYAN = "`e[96m"
#$ANSI_BRIGHT_WHITE = "`e[97m"
$ANSI_RESET = "`e[0m"

function Show-RuleResult {
  param (
    [bool]$testResult,
    [hashtable]$rule,
    [array]$exceptions = @()
  )

  if ($exceptions.Count -gt 0) {
    Write-Output "${ANSI_BRIGHT_YELLOW}âœ˜ $($rule['rule_name'])${ANSI_RESET}"
    foreach ($exception in $exceptions) {
      Write-Output "${ANSI_BRIGHT_YELLOW}$($exception.Message)${ANSI_RESET}"
      Write-Output "$($exception.Exception.Message)"
    }
  }
  else {
    if ($testResult) {
      Write-Output "${ANSI_GREEN}âœ” $($rule['rule_name'])${ANSI_RESET}"
    }
    else {
      Write-Output "${ANSI_BRIGHT_RED}âœ˜ $($rule['rule_name'])${ANSI_RESET}"
    }
  }

  if ($rule.ContainsKey('cywise_link')) {
    Write-Output "  -> $($rule['cywise_link'])"
  }
}

function Show-TestResult {
  param(
    [int]$PassedCount,
    [int]$FailedCount,
    [int]$ErrorCount = 0
  )

  $TotalCount = $PassedCount + $FailedCount + $ErrorCount
  if ($TotalCount -eq 0) {
    Write-Output "${ANSI_YELLOW}Aucun test lancÃ©.${ANSI_RESET}"
    return
  }

  $Percentage = [math]::Round(($PassedCount / $TotalCount) * 100)
  $Color = $ANSI_GREEN
  if ($Percentage -lt 25) {
    $Level = "Critique"
    $Color = $ANSI_BRIGHT_RED
  }
  elseif ($Percentage -lt 50) {
    $Level = "MÃ©diocre"
    $Color = $ANSI_BRIGHT_MAGENTA
  }
  elseif ($Percentage -lt 75) {
    $Level = "Acceptable"
    $Color = $ANSI_BRIGHT_YELLOW
  }
  elseif ($Percentage -lt 100) {
    $Level = "Bon"
    $Color = $ANSI_GREEN
  }
  elseif ($Percentage -eq 100) {
    $Level = "Excellent"
    $Color = $ANSI_BRIGHT_GREEN
  }

  Write-Output "Tests ${ANSI_GREEN}rÃ©ussis: $PassedCount, ${ANSI_BRIGHT_RED}Ã©chouÃ©s: $FailedCount, ${ANSI_BRIGHT_YELLOW}erreurs: $ErrorCount${ANSI_RESET}"
  Write-Output "${Color}Score: $Percentage/100 (${Level})${ANSI_RESET}"
}

function Evaluate {
  param (
    [hashtable]$ctx,
    [hashtable]$rule
  )

  $matchType = $rule['match_type']
  foreach ($r in $rule['rules']) {
    $match_result = Match $ctx $r
    $isOk = if ($r['negate']) { -not $match_result } else { $match_result }
    if (($matchType -eq 'all' -and -not $isOk) -or ($matchType -eq 'none' -and $isOk)) {
      return $false
    }
    if ($matchType -eq 'any' -and $isOk) {
      return $true
    }
  }
  return ($matchType -eq 'all') -or ($matchType -eq 'none')
}

function Match {
  param (
    [hashtable]$ctx,
    [hashtable]$rule
  )

  switch ($rule['type']) {
    'file' {
      return ($rule['files'] | Where-Object { $ctx['file_exists'].Invoke($_) } | Where-Object {
          if (-not ($rule.ContainsKey('expr') -and $null -ne $rule['expr'])) { 
            return $true 
          }
        ($ctx['fetch_file'].Invoke($_) | Where-Object { MatchExpression $_ $rule['expr'] } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
        } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
    }
    'directory' {
      return ($rule['directories'] | Where-Object { $ctx['directory_exists'].Invoke($_) } | Where-Object {
          if (-not ($rule.ContainsKey('files') -and $null -ne $rule['files'])) { 
            return $true 
          }
        ($ctx['list_files'].Invoke($_) | Where-Object { MatchPattern $_ $rule['files'] } | Where-Object {
            if (-not ($rule.ContainsKey('expr') -and $null -ne $rule['expr'])) { 
              return $true 
            }  
          ($ctx['fetch_file'].Invoke($_) | Where-Object { MatchExpression $_ $rule['expr'] } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
          } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
        } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
    }
    'registry' {
      return (@($rule['entry']) | Where-Object { $ctx['registry_entry_exists'].Invoke($_) } | Where-Object {
          $entry = $_
          if (-not $rule.ContainsKey('key') -or $null -eq $rule['key']) { 
            return $true 
          }
        ($ctx['fetch_registry_keys'].Invoke($_) | Where-Object { MatchPattern $_ $rule['key'] } | Where-Object {
            if (-not $rule.ContainsKey('expr') -or $null -eq $rule['expr']) { 
              return $true 
            }
          ($ctx['fetch_registry_value'].Invoke($entry, $_) | Where-Object { MatchExpression $_ $rule['expr'] } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
          } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
        } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
    }
    'command' {
      return (@($rule['cmd']) | Where-Object {
          if (-not $rule.ContainsKey('expr') -or $null -eq $rule['expr']) { 
            return $true
          }
        ($ctx['execute'].Invoke($_) | Where-Object { MatchExpression $_ $rule['expr'] } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
        } | Measure-Object | Select-Object -ExpandProperty Count) -gt 0
    }
    default {
      return $false
    }
  }
}

function MatchExpression {
  param (
    [string]$line,
    [array]$expr
  )

  foreach ($e in $expr) {
    if (-not (MatchPattern -value $line -pattern $e)) {
      return $false
    }
  }
  return $true
}

function MatchPattern {
  param (
    [string]$value,
    [string]$pattern
  )

  Write-Debug "Matching $value against $pattern"

  # Determine if the match must be negated
  $negate = $false
  if ($pattern.StartsWith('!')) {
    $negate = $true
    $pattern = $pattern.Substring(1)
  }

  # Simple regex match: either it matches or it doesn't!
  if ($pattern.StartsWith('r:')) {
    $pattern = $pattern.Substring(2)
    try {
      $result = [regex]::IsMatch($value, $pattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    }
    catch {
      Add-Exception `
        -Message "Erreur: expression rÃ©guliÃ¨re invalide '$pattern'." `
        -Exception $_.Exception
      $result = $false
    }
    if ($negate) {
      return -not $result
    }
    return $result
  }


  # TODO: Add tests for these 3 operators
  # Simple comparisons
  # Values can be preceded by:
  #  - =: (for equal) - default
  #  - >: (for strcmp greater)
  #  - <: (for strcmp  lower)
  # if ($pattern.StartsWith('<:')) {
  #   $pattern = $pattern.Substring(2)
  #   return if ($negate) { $pattern -ge $value } else { $pattern -lt $value }
  # }
  # if ($pattern.StartsWith('>:')) {
  #   $pattern = $pattern.Substring(2)
  #   return if ($negate) { $pattern -le $value } else { $pattern -gt $value }
  # }
  # if ($pattern.StartsWith('=:')) {
  #   $pattern = $pattern.Substring(2)
  #   return if ($negate) { $pattern -ne $value } else { $pattern -eq $value }
  # }

  # Extract a specific sequence from the input string then compare this sequence against a given value
  $match_result = [regex]::Match($pattern, '^n:(.*)\s+compare\s+([><=!]+)\s*(.*)$', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
  if ($match_result.Success) {
    $pattern = $match_result.Groups[1].Value.Trim()
    $operator = $match_result.Groups[2].Value.Trim()
    $compareValue = [int]$match_result.Groups[3].Value.Trim()
    $match = [regex]::Match($value, $pattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if (-not $match.Success) {
      $isOk = $negate
    }
    else {
      $matchedValue = [int]$match.Groups[1].Value
      switch ($operator) {
        '>' { $isOk = if ($negate) { $matchedValue -le $compareValue } else { $matchedValue -gt $compareValue } }
        '<' { $isOk = if ($negate) { $matchedValue -ge $compareValue } else { $matchedValue -lt $compareValue } }
        '=' { $isOk = if ($negate) { $matchedValue -ne $compareValue } else { $matchedValue -eq $compareValue } }
        '==' { $isOk = if ($negate) { $matchedValue -ne $compareValue } else { $matchedValue -eq $compareValue } }
        '>=' { $isOk = if ($negate) { $matchedValue -lt $compareValue } else { $matchedValue -ge $compareValue } }
        '<=' { $isOk = if ($negate) { $matchedValue -gt $compareValue } else { $matchedValue -le $compareValue } }
        '!=' { $isOk = if ($negate) { $matchedValue -eq $compareValue } else { $matchedValue -ne $compareValue } }
        '<>' { $isOk = if ($negate) { $matchedValue -eq $compareValue } else { $matchedValue -ne $compareValue } }
        default {
          Write-Host "Unknown operation: $pattern $operator $compareValue"
          $isOk = $false
        }
      }
    }
    return $isOk
  }

  if ($negate) { 
    return $value -ne $pattern 
  }
  else { 
    return $value -eq $pattern 
  } 
}


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
__PUT_RULES_HERE__
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
