param([Parameter(Mandatory=$true)][string]$PhpPath)
$ErrorActionPreference = 'Stop'
try {
    $php = (Resolve-Path -LiteralPath $PhpPath).Path
    $worker = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot 'mail-worker.php')).Path
    $taskName = 'CyberWatch Gmail Delivery'
    $existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($existing) {
        Write-Output 'Existing CyberWatch Gmail Delivery task preserved.'
        exit 0
    }
    $launcher = 'C:\xampp\cybersecurity-private\mail-worker.vbs'
    $command = '"' + $php + '" "' + $worker + '"'
    $script = 'CreateObject("WScript.Shell").Run "' + $command.Replace('"','""') + '", 0, True'
    [System.IO.File]::WriteAllText($launcher, $script, [System.Text.Encoding]::Unicode)
    $action = New-ScheduledTaskAction -Execute "$env:WINDIR\System32\wscript.exe" -Argument ('"' + $launcher + '"')
    $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
    $principal = New-ScheduledTaskPrincipal -UserId ([System.Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Send Cybersecurity incident and account emails every minute while signed in.' | Out-Null
    Write-Output 'Email worker scheduled every minute while this Windows user is signed in.'
} catch {
    Write-Output ('Email task setup failed: ' + $_.Exception.Message)
    exit 1
}
