@echo off
setlocal EnableExtensions EnableDelayedExpansion

set "TARGET=C:\GreyWolfWebsite"
set "REPO_URL=https://github.com/wms365com-dev/greywolf3pl_web_usa.git"
set "BRANCH=main"
set "DEFAULT_COMMIT=Update Grey Wolf site for Railway deployment"

echo.
echo Grey Wolf GitHub Push Helper
echo ============================
echo Source folder: %TARGET%
echo Remote repo  : %REPO_URL%
echo Branch       : %BRANCH%
echo.

where git >nul 2>&1
if errorlevel 1 (
  echo ERROR: Git is not installed or not available on PATH.
  echo Install Git for Windows, then run this file again.
  exit /b 1
)

if not exist "%TARGET%" (
  echo ERROR: The folder "%TARGET%" does not exist.
  exit /b 1
)

if not exist "%TARGET%\.git" (
  echo No Git repository found in %TARGET%.
  echo Initializing a new repository...
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
    echo Expected origin remote:
    echo   %REPO_URL%
    choice /M "Replace the current origin remote with the GitHub repo above"
    if errorlevel 2 (
      echo Cancelled. No changes made to the remote.
      exit /b 1
    )
    git -C "%TARGET%" remote set-url origin "%REPO_URL%"
    if errorlevel 1 goto :gitfail
  )
)

echo.
echo Staging files...
git -C "%TARGET%" add .
if errorlevel 1 goto :gitfail

git -C "%TARGET%" diff --cached --quiet
if errorlevel 1 (
  echo.
  set "COMMIT_MSG=%DEFAULT_COMMIT%"
  set "USER_COMMIT="
  set /p "USER_COMMIT=Commit message [%DEFAULT_COMMIT%]: "
  if not "!USER_COMMIT!"=="" set "COMMIT_MSG=!USER_COMMIT!"
  git -C "%TARGET%" commit -m "!COMMIT_MSG!"
  if errorlevel 1 goto :gitfail
) else (
  echo No new staged changes were found.
)

echo.
echo Pushing to GitHub...
git -C "%TARGET%" push -u origin %BRANCH%
if errorlevel 1 (
  echo.
  echo Push failed.
  echo If the GitHub repo already has commits, you may need to run:
  echo   git -C "%TARGET%" fetch origin
  echo   git -C "%TARGET%" pull origin %BRANCH% --allow-unrelated-histories
  echo Then run this batch file again.
  exit /b 1
)

echo.
echo Push complete.
exit /b 0

:gitfail
echo.
echo ERROR: A Git command failed before the push completed.
exit /b 1
