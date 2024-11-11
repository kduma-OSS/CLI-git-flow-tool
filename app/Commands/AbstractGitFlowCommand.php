<?php

namespace App\Commands;

use App\Repository\Config\AppConfig;
use App\Repository\ConfigProvider;
use App\Repository\Git\GitRepository;
use App\Repository\VersionProviders\VersionUpdaterService;
use Gitonomy\Git\Repository;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;

abstract class AbstractGitFlowCommand extends Command
{
    use ConfirmableTrait;

    public function handle(ConfigProvider $configProvider, VersionUpdaterService $versionUpdaterService): int
    {
        $config = $configProvider->load(getcwd());
        $repository = new GitRepository(new Repository($config->workingDirectory), $config);


        if (! $this->confirmToProceed()) {
            return 0;
        }

        $branch = $repository->getCurrentBranchName();
        if ($branch === $this->getStartingBranch($config)) {
            clear();
            if ($repository->isDirty()) {
                error('Working Directory is in dirty state!');

                return 1;
            }

            if (! $this->option('start') && ! confirm('Do you want to start new '.$this->getKeyword($config).'?', true)) {
                return 0;
            }

            return $this->startNewRelease($config, $repository, $versionUpdaterService);
        }

        if (Str::startsWith($branch, $this->getBranchPrefix($config))) {
            $version = Str::after($branch, $this->getBranchPrefix($config));
            clear();
            if (! $this->option('finish') && ! confirm('Do you want to finish v'.$version.' '.$this->getKeyword($config).'?', false)) {
                return 0;
            }

            return $this->finishRelease($branch, $version, $config, $repository);
        }

        if (in_array($branch, $this->getDestinationBranches($config))) {
            clear();
            if ($repository->isDirty()) {
                error('Working Directory is in dirty state!');

                return 1;
            }

            if (! confirm('You need to start from '.$this->getStartingBranch($config).' branch. Do you want to switch?', true)) {
                return 0;
            }

            $repository->checkout($this->getStartingBranch($config));

            clear();
            if (! $this->option('start') && ! confirm('Do you want to start new '.$this->getKeyword($config).'?', true)) {
                return 0;
            }

            return $this->startNewRelease($config, $repository, $versionUpdaterService);
        }

        clear();
        error(sprintf('Don\'t know what to do with %s branch!', $branch));

        return 1;
    }


    protected function startNewRelease(AppConfig $config, GitRepository $repository, VersionUpdaterService $versionUpdaterService): int
    {
        spin(
            function () use ($repository, &$isUpToDate) {
                try {
                    $isUpToDate = $repository->isUpToDate();
                } catch (\RuntimeException $e) {
                    $isUpToDate = true;
                }
            },
            'Checking if Working Directory is up to date'
        );

        if (!$isUpToDate) {
            clear();
            error('Working Directory is not up to date!');

            return 2;
        }

        $version_part_to_increment = match ($this->option('type')) {
            'pre-release' => 'pre-release',
            default => 'patch',
            'minor' => 'minor',
            'major' => 'major',
        };

        try {
            $updater = $versionUpdaterService->load($config);
            $updater->increment($version_part_to_increment);
        } catch (\Exception|\RuntimeException $e) {
            clear();
            error('Cannot get next version number!');

            return 3;
        }

        $version = trim($updater->get());

        $branch = $this->getBranchPrefix($config).$version;

        if (collect($repository->getBranches())->filter(fn ($v) => $v === $branch)->count() !== 0) {
            clear();
            error(ucfirst($this->getKeyword($config)).' branch for this version number already exists!');

            return 4;
        }

        $repository->checkoutNew($branch);

        try {
            $updater->save();
        } catch (\Exception|\RuntimeException $e) {
            clear();
            error('Cannot bump version number!');

            return 4;
        }

        $repository->commitAs('Bump app version to v'.$version);

        clear();
        if (! $this->option('finish') && ! confirm('Do you want to finish v'.$version.' '.$this->getKeyword($config).' ?', false)) {
            return 0;
        }

        return $this->finishRelease($branch, $version, $config, $repository);
    }

    protected function finishRelease(string $branch, string $version, AppConfig $config, GitRepository $repository): int
    {
        if ($repository->isDirty()) {
            clear();
            $this->line($repository->repository->run('status'));

            if (! confirm('Working Directory is in dirty state. Do you want to commit it?', false)) {
                return 0;
            }

            if (! ($message = $this->ask('Commit message'))) {
                return 0;
            }

            $repository->commit($message);
        }

        $tag_name = sprintf('%s%s%s', $config->gitFlow->versionTagPrefix, $version, $config->gitFlow->versionTagSuffix);
        $repository->tag($tag_name);

        foreach ($this->getDestinationBranches($config) as $destination) {
            spin(
                function () use ($repository, $destination, $branch, $config) {
                    $repository->checkout($destination);
                    $repository->mergeAs($branch);
                    $repository->push($destination, $config->git->remoteName);
                },
                'Merging '.$branch.' into '.$destination.' branch'
            );

        }

        spin(
            fn () => $repository->pushTag($tag_name, $config->git->remoteName),
            'Pushing tag '.$tag_name
        );

        $repository->deleteBranch($branch);

        return 0;
    }

    abstract protected function getDestinationBranches(AppConfig $config): array;

    abstract protected function getStartingBranch(AppConfig $config): string;

    abstract protected function getBranchPrefix(AppConfig $config): string;

    abstract protected function getKeyword(AppConfig $config): string;
}
