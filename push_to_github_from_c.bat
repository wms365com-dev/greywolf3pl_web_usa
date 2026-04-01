@echo off
setlocal EnableExtensions EnableDelayedExpansion

set "SOURCE=E:\GreyWolfWebsite"
set "TARGET=C:\GreyWolfWebsite"
set "REPO_URL=https://github.com/wms365com-dev/greywolf3pl_web_usa.git"
set "BRANCH=main"
set "DEFAULT_COMMIT=Sync Grey Wolf site for Railway deployment"

echo.
echo Grey Wolf GitHub Sync
echo Source : %SOURCE%
echo Target : %TARGET%
echo Remote : %REPO_URL%
echo.

if not exist "%SOURCE%" (
  echo ERROR: Source folder does not exist.
  echo %SOURCE%
  exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
  echo ERROR: Git is not installed or not on PATH.
  echo Install Git for Windows first, then run this script again.
  exit /b 1
)

if not exist "%TARGET%" (
  mkdir "%TARGET%"
  if errorlevel 1 (
    echo ERROR: Could not create target folder.
    exit /b 1
  )
)

echo This will mirror files from:
echo   %SOURCE%
echo into:
echo   %TARGET%
echo.
echo Files not present in the source may be removed from the target.
choice /M "Continue with file sync"
if errorlevel 2 (
  echo Cancelled.
  exit /b 0
)

robocopy "%SOURCE%" "%TARGET%" /MIR /R:1 /W:1 /XD ".git" "node_modules" "form_submissions"
set "ROBOCODE=%ERRORLEVEL%"
if %ROBOCODE% GEQ 8 (
  echo ERROR: Robocopy failed with exit code %ROBOCODE%.
  exit /b %ROBOCODE%
)

echo.
if not exist "%TARGET%\.git" (
  echo Initializing git repository in %TARGET%
  git -C "%TARGET%" init
  if errorlevel 1 goto :gitfail
)

git -C "%TARGET%" branch -M %BRANCH%
if errorlevel 1 goto :gitfail

git -C "%TARGET%" remote get-url origin >nul 2>&1
if errorlevel 1 (
  echo Adding origin remote...
  git -C "%TARGET%" remote add origin "%REPO_URL%"
  if errorlevel 1 goto :gitfail
) else (
  for /f "delims=" %%R in ('git -C "%TARGET%" remote get-url origin') do set "CURRENT_REMOTE=%%R"
  if /I not "!CURRENT_REMOTE!"=="%REPO_URL%" (
    echo Existing origin remote:
    echo   !CURRENT_REMOTE!
    echo New origin remote:
    echo   %REPO_URL%
    choice /M "Replace origin remote"
    if errorlevel 2 (
      echo Cancelled.
      exit /b 1
    )
    git -C "%TARGET%" remote set-url origin "%REPO_URL%"
    if errorlevel 1 goto :gitfail
  )
)

echo.
git -C "%TARGET%" add .
if errorlevel 1 goto :gitfail

git -C "%TARGET%" diff --cached --quiet
if errorlevel 1 (
  set "COMMIT_MSG=%DEFAULT_COMMIT%"
  set "USER_COMMIT="
  set /p "USER_COMMIT=Commit message [%DEFAULT_COMMIT%]: "
  if not "!USER_COMMIT!"=="" set "COMMIT_MSG=!USER_COMMIT!"
  git -C "%TARGET%" commit -m "!COMMIT_MSG!"
  if errorlevel 1 goto :gitfail
) else (
  echo No staged changes to commit.
)

echo.
echo Pushing to GitHub...
git -C "%TARGET%" push -u origin %BRANCH%
if errorlevel 1 (
  echo.
  echo Push failed.
  echo If the GitHub repo already has commits, pull/merge first, then run this script again.
  exit /b 1
)

echo.
echo Success.
echo Files were synced to %TARGET% and pushed to GitHub.
exit /b 0

:gitfail
echo.
echo ERROR: A git command failed.
exit /b 1
