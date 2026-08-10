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
      -Message "Erreur lors de l'exécution de la commande '$command'." `
      -Exception $_.Exception
    return $null
  }
}

function Test-RuleCommand {
  param(
    [string]$command
  )

  try {
    $global:LASTEXITCODE = 0
    $null = Invoke-Expression -Command "$command 2>&1"
    $commandSucceeded = $?
    return $commandSucceeded -and $global:LASTEXITCODE -eq 0
  }
  catch {
    Add-Exception `
      -Message "Erreur lors de l'exécution de la commande '$command'." `
      -Exception $_.Exception
    return $false
  }
}
