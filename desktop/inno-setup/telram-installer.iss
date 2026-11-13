; SULTAN BANTEN - Inno Setup Script
; Master Offline Installer Generator untuk Kalkulator Telram & Integrasi SDP
; Kantor Wilayah Ditjenpas Banten

#define MyAppName "Kalkulator Telram & Integrasi SDP"
#define MyAppVersion "2.0.0"
#define MyAppPublisher "Kantor Wilayah Ditjenpas Banten"
#define MyAppURL "https://kemenkumham.go.id"
#define MyAppExeName "TelramLauncher.vbs"

[Setup]
AppId={{E5813D8E-7B34-4A1C-9F52-0B872F41893D}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName={autopf}\SultanBanten\KalkulatorTelram
DefaultGroupName=Sultan Banten - Ditjenpas
DisableProgramGroupPage=yes
LicenseFile=
OutputDir=..\dist
OutputBaseFilename=Setup_Kalkulator_Telram_Ditjenpas_Banten
SetupIconFile=..\launcher\app-icon.ico
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=lowest
ArchitecturesInstallIn64BitMode=x64compatible
UninstallDisplayIcon={app}\launcher\app-icon.ico

[Languages]
Name: "indonesian"; MessagesFile: "compiler:Languages\Indonesian.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"

[Files]
; Seluruh berkas aplikasi yang telah dipaketkan di folder build stage
Source: "..\build_stage\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{sys}\wscript.exe"; Parameters: """{app}\launcher\{#MyAppExeName}"""; WorkingDir: "{app}"; IconFilename: "{app}\launcher\app-icon.ico"
Name: "{group}\Hentikan Server Telram"; Filename: "{app}\launcher\stop-telram.bat"; WorkingDir: "{app}"; IconFilename: "{app}\launcher\app-icon.ico"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{sys}\wscript.exe"; Parameters: """{app}\launcher\{#MyAppExeName}"""; WorkingDir: "{app}"; IconFilename: "{app}\launcher\app-icon.ico"; Tasks: desktopicon

[Run]
Filename: "{sys}\wscript.exe"; Parameters: """{app}\launcher\{#MyAppExeName}"""; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: shellexec postinstall skipifsilent

[UninstallRun]
Filename: "{app}\launcher\stop-telram.bat"; Flags: runhidden
