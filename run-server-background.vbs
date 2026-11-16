' Permanent Silent Background Server Launcher for PRAYAS Website
' Organization: PRAYAS – The Automation Academy
' Port: 8000 | Document Root: C:\prayas-website

Set objShell = CreateObject("WScript.Shell")
Set objFSO = CreateObject("Scripting.FileSystemObject")

' Check if port 8000 is already in use
Set objExec = objShell.Exec("cmd.exe /c netstat -ano | findstr :8000 | findstr LISTENING")
strOutput = objExec.StdOut.ReadAll()

If InStr(strOutput, "LISTENING") = 0 Then
    ' Port 8000 is free, launch PHP development server completely hidden (0 = hide window)
    phpPath = "C:\xampp\php\php.exe"
    docRoot = "C:\prayas-website"
    cmd = """" & phpPath & """ -S 0.0.0.0:8000 -t """ & docRoot & """"
    objShell.Run cmd, 0, False
End If
