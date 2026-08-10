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
            return $ctx['execute_success'].Invoke($_)
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
        -Message "Erreur: expression régulière invalide '$pattern'." `
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
