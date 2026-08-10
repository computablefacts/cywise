BeforeAll {
    . "$PSScriptRoot/Evaluate.ps1"
}
  
Describe 'Match function' {
  
    Context 'type command' {
        BeforeAll {
            function command_return_expected_value {
                param([string]$command)
                if ($command -eq $expectedCommand) {
                    $expectedReturnValue 
                }
                else { 
                    $wrongReturnValue 
                }
            }
            function command_return_wrong_value {
                param([string]$command)
                if ($command -eq $expectedCommand) {
                    $wrongReturnValue
                }
                else { 
                    $expectedReturnValue 
                }
            }
        }        

        It 'should have correct mock functions' {
            # Arrange
            $expectedCommand = "echo 'Hello' && echo 'world!'"
            $expectedReturnValue = @('Hello', 'world!')
            $wrongReturnValue = @('Wrong!')
      
            # Assert
            command_return_expected_value $expectedCommand | Should -Be $expectedReturnValue
            command_return_expected_value 'AnOtherCommand' | Should -Be $wrongReturnValue
      
            command_return_wrong_value $expectedCommand | Should -Be $wrongReturnValue
            command_return_wrong_value 'AnOtherCommand' | Should -Be $expectedReturnValue
        }

        Context 'c:net.exe accounts -> n:Lockout duration \(minutes\):\s+(\d+) compare >= 15' {
            BeforeAll {
                $expectedCommand = 'net.exe accounts'
                $expectedReturnValue = @(
                    'Lockout threshold:                                    Never',
                    'Lockout duration (minutes):                           30',
                    'Lockout observation window (minutes):                 30'
                )
                $wrongReturnValue = @(
                    'Lockout threshold:                                    Never',
                    'Lockout duration (minutes):                           5',
                    'Lockout observation window (minutes):                 30'
                )
                    
                $rule = @{
                    'match_type' = 'all'
                    'rules'      = @(
                        @{ 
                            'negate' = $false
                            'type'   = 'command'
                            'cmd'    = 'net.exe accounts'
                            'expr'   = @(
                                'n:Lockout duration \(minutes\):\s+(\d+) compare >= 15'
                            )
                        }
                    )
                }
                # Avoid warning: "The variable 'xxx' is assigned but never used."
                $expectedCommand | Out-Null
                $expectedReturnValue | Out-Null
                $wrongReturnValue | Out-Null
                $rule | Out-Null
            }

            It 'should return false when command result does NOT match' {
                # Arrange
                $ctx = @{
                    'execute' = { command_return_wrong_value -command $args[0] }
                }
      
                # Act
                $result = Evaluate $ctx $rule
      
                # Assert
                $result | Should -Be $false
            }

            It 'should return true when command result matches' {
                # Arrange
                $ctx = @{
                    'execute' = { command_return_expected_value -command $args[0] }
                }
      
                # Act
                $result = Evaluate $ctx $rule
      
                # Assert
                $result | Should -Be $true
            }

        }

        Context 'c:net.exe accounts' {
            BeforeAll {
                $expectedCommand = 'net.exe accounts'
                $rule = @{
                    'match_type' = 'all'
                    'rules'      = @(
                        @{ 
                            'negate' = $false
                            'type'   = 'command'
                            'cmd'    = 'net.exe accounts'
                            'expr'   = $null
                        }
                    )
                }
                # Avoid warning: "The variable 'xxx' is assigned but never used."
                $expectedCommand | Out-Null
                $rule | Out-Null
            }

            It 'should return true when a command without expression succeeds' {
                # Arrange
                $ctx = @{
                    'execute_success' = { $args[0] -eq $expectedCommand }
                }
      
                # Act
                $result = Evaluate $ctx $rule
      
                # Assert
                $result | Should -Be $true
            }

            It 'should return false when a command without expression fails' {
                # Arrange
                $ctx = @{
                    'execute_success' = { $false }
                }
      
                # Act
                $result = Evaluate $ctx $rule
      
                # Assert
                $result | Should -Be $false
            }

            It 'should apply negate to the exit status of a command without expression' {
                # Arrange
                $ctx = @{
                    'execute_success' = { $false }
                }
                $negatedRule = @{
                    'match_type' = 'all'
                    'rules'      = @(
                        @{
                            'negate' = $true
                            'type'   = 'command'
                            'cmd'    = 'test -x /etc/passwd'
                            'expr'   = $null
                        }
                    )
                }

                # Act
                $result = Evaluate $ctx $negatedRule

                # Assert
                $result | Should -Be $true
            }

        }
    }
}
