<?php

namespace App\Repository\VersionProviders;

use App\Repository\Config\AppConfig;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version;

readonly class AppVersion
{
    protected Version $version;

    public function __construct(protected AppConfig $config)
    {
        try {
            $this->version = new Version($config->versionProvider->getVersion() ?? '0.0.0');
        } catch (InvalidVersionException $e) {
            throw new \RuntimeException('Invalid version provided by version provider');
        }
    }

    public function save(): void
    {
        $this->config->versionProvider->setVersion($this->version);
    }

    public function get(): Version
    {
        return $this->version;
    }

    public function increment(string $part): void
    {
        match ($part) {
            'pre-release' => $this->version->incrementPreRelease(),
            'major' => $this->version->incrementMajor(),
            'minor' => $this->version->incrementMinor(),
            'patch' => $this->version->incrementPatch(),
            default => throw new \RuntimeException('Invalid version part'),
        };
    }

    public function setBuild(?string $build): void
    {
        $this->version->setBuild($build);
    }

    public function setPreRelease(?string $preRelease): void
    {
        $this->version->setPreRelease($preRelease);
    }


}
