# Send metric to performa every minute
CreateOrUpdate-ScheduledTask -Executable "$performaPath\performa-satellite-win-x64.exe" -Arguments "--hostname {name}"  -TaskName "performa-satellite" -ExecutionType Custom -RepeatInterval 60
