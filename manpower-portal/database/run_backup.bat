@echo off
set TIMESTAMP=%date:~10,4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=%~dp0backups
echo Running safe database backup to %BACKUP_DIR%...
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases itistudents > "%BACKUP_DIR%\itistudents_%TIMESTAMP%.sql"
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases prayas_iti_dashboard > "%BACKUP_DIR%\prayas_iti_%TIMESTAMP%.sql"
"C:\xampp\mysql\bin\mysqldump.exe" -u root --databases manpower_portal_db > "%BACKUP_DIR%\manpower_portal_%TIMESTAMP%.sql" 2>nul
echo Backup completed successfully at %TIMESTAMP%.

