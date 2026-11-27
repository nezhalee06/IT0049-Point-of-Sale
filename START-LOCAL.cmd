@echo off
cd /d "%~dp0"
set "POS_PHP=C:\xampp\php\php.exe"
if not exist "%POS_PHP%" set "POS_PHP=php"
if not exist "writable\tmp" mkdir "writable\tmp"
"%POS_PHP%" -d extension=gd -d extension=sqlite3 spark migrate --all
if errorlevel 1 goto fail
"%POS_PHP%" -d extension=gd -d extension=sqlite3 spark db:seed PosSeeder
if errorlevel 1 goto fail
start "" http://localhost:8085
"%POS_PHP%" -d extension=gd -d extension=sqlite3 -d "upload_tmp_dir=%CD%\writable\tmp" -S localhost:8085 -t public vendor/codeigniter4/framework/system/rewrite.php
goto :eof
:fail
echo Setup failed. See the error above and README.md.
pause

