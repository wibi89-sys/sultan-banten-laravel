' SULTAN Banten - Silent Windows Launcher
' Menjalankan run-telram.bat tanpa memunculkan jendela konsol hitam (CMD)
Set WshShell = CreateObject("WScript.Shell")
strPath = WshShell.CurrentDirectory
WshShell.Run Chr(34) & strPath & "\launcher\run-telram.bat" & Chr(34), 0, False
Set WshShell = Nothing
