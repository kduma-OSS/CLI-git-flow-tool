<?php

namespace App\Repository\Git;

use App\Repository\Config\AppConfig;
use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\Repository;

class GitRepository
{
    public function __construct(public Repository $repository, public AppConfig $config)
    {
    }

    public function getCurrentBranchName(): ?string
    {
        $branch = $this->repository->getHead();

       if ($branch instanceof Branch) {
            return $branch->getName();
        }

        throw new \RuntimeException('Could not determine current branch');
    }

    public function getBranches(): array
    {
        return collect(explode("\n", $this->repository->run('branch', ['--format=%(refname:short)'])))
            ->map(fn($branch) => trim($branch))
            ->filter(fn($branch) => $branch !== '')
            ->toArray();
    }

    public function isDirty(): bool
    {
        return !! $this->repository->run('status', ['--porcelain', '--untracked-files=all', '-z']);
    }

    public function isTracking(): bool
    {
        try {
            $this->repository->run('rev-parse', ['@{u}']);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isUpToDate(): bool
    {
        if (!$this->isTracking()) {
            throw new \RuntimeException('Current branch is not tracking remote');
        }

        $mergeBase = $this->repository->run('merge-base', ['@', '@{u}']);
        $remoteSha = $this->repository->run('rev-parse', ['@{u}']);;

        return $mergeBase === $remoteSha;
    }

    public function commit(string $message, array $files = ['.']): void
    {
        $this->repository->run('add', $files);
        $this->repository->run('commit', [
            '--message', $message
        ]);
    }

    public function commitAs(string $message, array $files = ['.']): void
    {
        $this->setAuthor();

        $this->commit($message, $files);

        $this->clearAuthor();
    }

    public function checkout(string $branch): void
    {
        $this->repository->run('checkout', [$branch]);
    }

    public function checkoutNew(string $branch): void
    {
        $this->repository->run('checkout', ['-b', $branch]);
    }

    public function deleteBranch(string $branch): void
    {
        $this->repository->run('branch', ['--delete', $branch]);
    }

    public function push(string $branch, string $remote = 'origin'): void
    {
        $this->repository->run('push', [$remote, $branch]);
    }
    public function pushTag(string $tag, string $remote = 'origin'): void
    {
        $this->repository->run('push', [$remote, $tag]);
    }

    public function pull(string $branch, string $remote = 'origin'): void
    {
        $this->repository->run('pull', [$remote, $branch]);
    }

    public function fetch(string $remote = 'origin'): void
    {
        $this->repository->run('fetch', [$remote]);
    }

    public function merge(string $branch, string $strategy = '--no-ff'): void
    {
        $this->repository->run('merge', [$branch, $strategy]);
    }

    public function mergeAs(string $branch, string $strategy = '--no-ff'): void
    {
        $this->setAuthor();

        $this->merge($branch, $strategy);

        $this->clearAuthor();
    }

    public function tag(string $tag, string $message = null): void
    {
        $this->repository->run('tag', [
            $tag,
            '-m',
            $message ?? $tag
        ]);
    }

    protected function setAuthor(): void
    {
        if(!$this->config->git->authorEmail) {
            return;
        }

        $this->repository->run('config', ['user.name', $this->config->git->authorName ?? $this->config->git->authorEmail]);
        $this->repository->run('config', ['user.email', $this->config->git->authorEmail]);

        $this->repository->run('config', ['author.name', $this->config->git->authorName ?? $this->config->git->authorEmail]);
        $this->repository->run('config', ['author.email', $this->config->git->authorEmail]);
    }

    protected function clearAuthor(): void
    {
        if(!$this->config->git->authorEmail) {
            return;
        }

        $this->repository->run('config', ['--remove-section', 'author']);
        $this->repository->run('config', ['--remove-section', 'user']);
    }
}
