<?php

namespace App\Commands;

use App\Repository\Config\AppConfig;

class HotFixCommand extends AbstractGitFlowCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotfix
    {--f|force : Force the operation to run when in production}
    {--s|start : Start new release}
    {--F|finish : Finish current release}
    {--t|type=patch : Release type - patch, minor, major}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and publish hotfix';

    /**
     * @return string[]
     */
    protected function getDestinationBranches(AppConfig $config): array
    {
        return [$config->gitFlow->masterBranch, $config->gitFlow->developBranch];
    }

    protected function getStartingBranch(AppConfig $config): string
    {
        return $config->gitFlow->masterBranch;
    }

    protected function getBranchPrefix(AppConfig $config): string
    {
        return $config->gitFlow->hotfixPrefix;
    }

    protected function getKeyword(AppConfig $config): string
    {
        return 'hotfix';
    }
}
