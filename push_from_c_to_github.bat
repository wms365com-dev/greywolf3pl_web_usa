@echo off
setlocal EnableExtensions EnableDelayedExpansion

set "TARGET=C:\GreyWolfWebsite"
set "REPO_URL=https://github.com/wms365com-dev/greywolf3pl_web_usa.git"
set "BRANCH=main"
set "DEFAULT_COMMIT=Update Grey Wolf site"

echo.
echo Grey Wolf GitHub Push
echo Folder : %TARGET%
echo Remote : %REPO_URL%
echo Branch : %BRANCH%
echo.

if not exist "%TARGET%" (
  echo ERROR: Target folder does not exist.
  echo %TARGET%
  exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
  echo ERROR: Git is not installed or not on PATH.
  exit /b 1
)

if not exist "%TARGET%\.git" (
  echo No git repository found in %TARGET%.
  choice /M "Initialize a new git repository here"
  if errorlevel 2 (
    echo Cancelled.
    exit /b 1
  )
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
git -C "%TARGET%" status --short
echo.
choice /M "Stage all changes"
if errorlevel 2 (
  echo Cancelled.
  exit /b 0
)

git -C "%TARGET%" add .
if errorlevel 1 goto :gitfail

git -C "%TARGET%" diff --cached --quiet
if errorlevel 1 (
  set "COMMIT_MSG=%DEFAULT_COMMIT%"
  set /p "USER_COMMIT=Commit message [%DEFAULT_COMMIT%]: "
  if not "%USER_COMMIT%"=="" set "COMMIT_MSG=%USER_COMMIT%"
  git -C "%TARGET%" commit -m "%COMMIT_MSG%"
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
  echo If the remote repo already has commits, you may need:
  echo   git -C "%TARGET%" fetch origin
  echo   git -C "%TARGET%" pull origin %BRANCH% --allow-unrelated-histories
  echo Then run this script again.
  exit /b 1
)

echo.
echo Success. Changes from %TARGET% were pushed to GitHub.
exit /b 0

:gitfail
echo.
echo ERROR: A git command failed.
exit /b 1
