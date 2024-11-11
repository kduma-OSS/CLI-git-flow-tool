# GitFlow Tool
Useful helper for using gitflow-like branching model in a project.
It is a simple shell script that helps you to create, finish and publish branches in a git repository.
It supports automatically bumping version tag and pushing from and to correct branches.

## Installation

in your project install the tool by running:
```bash
composer require kduma/git-flow-tool
```
and then use it by running:
```bash
vendor/bin/git-flow-tool
```

## Configuration
In root directory of your project create `.git-flow-tool.json` file with following content:
```json
{
    "versionProvider": "php-array?filename=config/app.php&key=version",
    "gitFlow": "branch[develop]=develop&branch[master]=main&prefix[feature]=feature/&prefix[release]=release/&prefix[hotfix]=hotfix/&prefix[support]=support/&prefix[versionTag]=v&suffix[versionTag]=-src",
    "git": "author[name]=BOT&author[email]=bot@localhost"
}
```
Update it to match your project configuration.

## Usage
```bash
vendor/bin/git-flow-tool release
```

or

```bash
vendor/bin/git-flow-tool hotfix
```
