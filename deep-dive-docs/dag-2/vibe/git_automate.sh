#!/bin/bash

# Git Automation Script
# Automates: git add, git commit, git push
# Optionally creates merge/pull requests

# Configuration
GIT_PLATFORM="github"  # Options: github, gitlab, bitbucket
REPO_OWNER="your_username"  # Your GitHub/GitLab username or org
REPO_NAME="your_repo"  # Your repository name
BASE_BRANCH="main"  # Branch to merge into (usually main or master)

# Get current branch name
CURRENT_BRANCH=$(git symbolic-ref --short HEAD 2>/dev/null)

# Check if we're in a git repository
if [ -z "$CURRENT_BRANCH" ]; then
    echo "Error: Not in a git repository or no branch checked out"
    exit 1
fi

# Check if current branch is the base branch
if [ "$CURRENT_BRANCH" = "$BASE_BRANCH" ]; then
    echo "Error: You're on the $BASE_BRANCH branch. Please create a feature branch first."
    exit 1
fi

# Get commit message from user
read -p "Enter commit message: " COMMIT_MESSAGE

if [ -z "$COMMIT_MESSAGE" ]; then
    echo "Error: Commit message cannot be empty"
    exit 1
fi

# Git operations
echo "Adding all changes..."
git add .

echo "Committing changes..."
git commit -m "$COMMIT_MESSAGE"

echo "Pushing to remote..."
git push origin "$CURRENT_BRANCH"

# Merge/Pull Request creation
echo ""
read -p "Do you want to create a merge/pull request? (y/n): " CREATE_MR

if [ "$CREATE_MR" = "y" ] || [ "$CREATE_MR" = "Y" ]; then
    case "$GIT_PLATFORM" in
        "github")
            echo "Creating Pull Request on GitHub..."
            echo "Please visit: https://github.com/$REPO_OWNER/$REPO_NAME/compare/$BASE_BRANCH...$CURRENT_BRANCH"
            echo "Or use GitHub CLI if installed: gh pr create --base $BASE_BRANCH --head $CURRENT_BRANCH --title '$COMMIT_MESSAGE'"
            ;;
        "gitlab")
            echo "Creating Merge Request on GitLab..."
            echo "Please visit: https://gitlab.com/$REPO_OWNER/$REPO_NAME/-/merge_requests/new?merge_request%5Bsource_branch%5D=$CURRENT_BRANCH&merge_request%5Btarget_branch%5D=$BASE_BRANCH"
            ;;
        "bitbucket")
            echo "Creating Pull Request on Bitbucket..."
            echo "Please visit: https://bitbucket.org/$REPO_OWNER/$REPO_NAME/pull-requests/new?source=$CURRENT_BRANCH&dest=$BASE_BRANCH"
            ;;
        *)
            echo "Unsupported platform: $GIT_PLATFORM"
            echo "Please manually create a merge/pull request"
            ;;
    esac
else
    echo "Merge/pull request creation skipped."
fi

echo ""
echo "Git automation complete!"
echo "Branch: $CURRENT_BRANCH"
echo "Commit: $COMMIT_MESSAGE"
