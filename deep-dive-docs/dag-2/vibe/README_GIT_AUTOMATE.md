# Git Automation Script

A simple Windows batch script to automate common Git operations and assist with merge/pull request creation.

## Features

- **Automated Git workflow**: Add, commit, and push in one command
- **Safety checks**: Prevents accidental commits to main/master branches
- **Merge request assistance**: Provides direct URLs for creating merge/pull requests
- **Multi-platform support**: Works with GitHub, GitLab, and Bitbucket

## Requirements

- Windows operating system
- Git installed and configured
- GitHub/GitLab/Bitbucket account (for merge request URLs)

## Installation

1. Download or copy the `git_automate.bat` file
2. Place it in your project directory or add it to your system PATH

## Configuration

Edit the configuration section at the top of `git_automate.bat`:

```batch
set GIT_PLATFORM=github  :: Options: github, gitlab, bitbucket
set REPO_OWNER=your_username  :: Your GitHub/GitLab username or org
set REPO_NAME=your_repo  :: Your repository name
set BASE_BRANCH=main  :: Branch to merge into (usually main or master)
```

## Usage

1. Navigate to your Git repository in Command Prompt
2. Run the script: `git_automate.bat`
3. Follow the prompts:
   - Enter your commit message
   - Choose whether to get merge request URLs

## Example Workflow

```
C:\my-project> git_automate.bat
Enter commit message: Fix login bug
Adding all changes...
Committing changes...
Pushing to remote...

Do you want to create a merge/pull request? (y/n): y
Creating Pull Request on GitHub...
Please visit: https://github.com/your_username/your_repo/compare/main...feature-branch

Git automation complete!
Branch: feature-branch
Commit: Fix login bug
```

## Safety Features

- **Branch protection**: Won't allow commits directly to the base branch
- **Repository check**: Verifies you're in a Git repository
- **Empty message prevention**: Requires a commit message

## Customization

You can modify the script to:
- Change default commit message format
- Add pre-commit hooks
- Include additional Git commands
- Support other Git platforms

## Notes

- The script provides merge request URLs but doesn't automatically create them
- For full automation, consider using GitHub CLI (`gh`) or GitLab CLI tools
- Always review your changes before committing

## License

This script is provided as-is for educational and productivity purposes. Feel free to modify and distribute.
