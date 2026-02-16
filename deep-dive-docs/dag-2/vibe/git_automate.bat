@echo off

:: Git Automation Script for Windows
:: Automates: git add, git commit, git push
:: Optionally provides merge/pull request URLs

:: Configuration
set GIT_PLATFORM=github  :: Options: github, gitlab, bitbucket
set REPO_OWNER=your_username  :: Your GitHub/GitLab username or org
set REPO_NAME=your_repo  :: Your repository name
set BASE_BRANCH=main  :: Branch to merge into (usually main or master)

:: Get current branch name
for /f "delims=" %%a in ('git symbolic-ref --short HEAD 2^>nul') do set CURRENT_BRANCH=%%a

:: Check if we're in a git repository
if "%CURRENT_BRANCH%"=="" (
    echo Error: Not in a git repository or no branch checked out
    exit /b 1
)

:: Check if current branch is the base branch
if "%CURRENT_BRANCH%"=="%BASE_BRANCH%" (
    echo Error: You're on the %BASE_BRANCH% branch. Please create a feature branch first.
    exit /b 1
)

:: Get commit message from user
set /p COMMIT_MESSAGE="Enter commit message: "

if "%COMMIT_MESSAGE%"=="" (
    echo Error: Commit message cannot be empty
    exit /b 1
)

:: Git operations
echo Adding all changes...
git add .

echo Committing changes...
git commit -m "%COMMIT_MESSAGE%"

echo Pushing to remote...
git push origin "%CURRENT_BRANCH%"

:: Merge/Pull Request creation
echo.
set /p CREATE_MR="Do you want to create a merge/pull request? (y/n): "

if /i "%CREATE_MR%"=="y" (
    if "%GIT_PLATFORM%"=="github" (
        echo Creating Pull Request on GitHub...
        echo Please visit: https://github.com/%REPO_OWNER%/%REPO_NAME%/compare/%BASE_BRANCH%...%CURRENT_BRANCH%
        echo Or use GitHub CLI if installed: gh pr create --base %BASE_BRANCH% --head %CURRENT_BRANCH% --title "%COMMIT_MESSAGE%"
    ) else if "%GIT_PLATFORM%"=="gitlab" (
        echo Creating Merge Request on GitLab...
        echo Please visit: https://gitlab.com/%REPO_OWNER%/%REPO_NAME%/-/merge_requests/new?merge_request%%5Bsource_branch%%5D=%CURRENT_BRANCH%&merge_request%%5Btarget_branch%%5D=%BASE_BRANCH%
    ) else if "%GIT_PLATFORM%"=="bitbucket" (
        echo Creating Pull Request on Bitbucket...
        echo Please visit: https://bitbucket.org/%REPO_OWNER%/%REPO_NAME%/pull-requests/new?source=%CURRENT_BRANCH%&dest=%BASE_BRANCH%
    ) else (
        echo Unsupported platform: %GIT_PLATFORM%
        echo Please manually create a merge/pull request
    )
) else (
    echo Merge/pull request creation skipped.
)

echo.
echo Git automation complete!
echo Branch: %CURRENT_BRANCH%
echo Commit: %COMMIT_MESSAGE%
