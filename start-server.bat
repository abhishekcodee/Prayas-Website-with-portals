@echo off
title PRAYAS Web & Recruitment Portal Server
cd /d "%~dp0"
echo ========================================================
echo   PRAYAS PLATFORM - LOCAL SERVER (PHP + MYSQL)
echo ========================================================
echo.
echo Starting local web server on http://localhost:8000 ...
echo.
echo   Website:         http://localhost:8000/index.html
echo   Student Portal:  http://localhost:8000/student-portal/
echo   Manpower Portal: http://localhost:8000/manpower-portal/
echo   Admin Panel:     http://localhost:8000/admin/login.php
echo.
echo   Press Ctrl+C to stop the server anytime.
echo ========================================================
start http://localhost:8000/index.html
"C:\xampp\php\php.exe" -S 0.0.0.0:8000
pause
