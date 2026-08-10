BeforeAll {
  . "$PSScriptRoot/Process.ps1"
  . "$PSScriptRoot/ExceptionList.ps1"
  . "$PSScriptRoot/../Display.ps1"
}

Describe 'Process library' {
  Describe "InvokeRuleCommand Tests" {

    It "Should return output as array of strings" {
      $command = "echo 'Hello, World!'"
      $result = InvokeRuleCommand -command $command
      $result | Should -Be @('Hello, World!')
    }

    It "Should handle multiple lines of output" {
      $command = "echo 'Line1'; echo 'Line2'"
      $result = InvokeRuleCommand -command $command
      $result | Should -Be @('Line1', 'Line2')
    }

    It "Should return empty array for no output" {
      $command = "echo 'Dummy' | Out-Null"
      $result = InvokeRuleCommand -command $command
      $result | Should -Be @()
    }

    It "Should execute complex command" {
      $command = "Get-Process | Select-Object -First 1 | Format-Table -HideTableHeaders"
      $result = InvokeRuleCommand -command $command
      $result.Length | Should -BeGreaterThan 0
    }

    It "Should add exception to list for unknown command" {
      $command = "UnknowCommand"
      $result = InvokeRuleCommand -command $command

      $exceptions = Get-ExceptionList
      $exceptions.Count | Should -Be 1
      $exceptions[0].Message | Should -BeLike '*Erreur*'
      $exceptions[0].Message | Should -BeLike '*exécution de la commande*'
      $exceptions[0].Message | Should -BeLike "*$command*"
      $exceptions[0].Exception | Should -Not -BeNullOrEmpty
    }

    It "Should not throw exception for unknown command" {
      $command = "UnknowCommand"
      { InvokeRuleCommand -command $command } | Should -Not -Throw
    }
  }

  Describe "Test-RuleCommand Tests" {
    BeforeAll {
      $successCommand = if ($IsWindows) { 'cmd /c exit 0' } else { 'sh -c "exit 0"' }
      $failureCommand = if ($IsWindows) { 'cmd /c exit 1' } else { 'sh -c "exit 1"' }
    }

    It "Should return true when a command exits with zero" {
      Test-RuleCommand -command $successCommand | Should -Be $true
    }

    It "Should return false when a command exits with a non-zero status" {
      Test-RuleCommand -command $failureCommand | Should -Be $false
    }

    It "Should report an unknown command as an evaluation error" {
      Clear-ExceptionList

      Test-RuleCommand -command "UnknowCommand" | Should -Be $false

      $exceptions = Get-ExceptionList
      $exceptions.Count | Should -Be 1
      $exceptions[0].Message | Should -BeLike '*exécution de la commande*'
    }
  }
}
