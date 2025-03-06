<?php

namespace App\Repository\VersionProviders;

/***
 * @example flat-file?filename=version.txt
 */
readonly class FlatFileVersionProvider implements VersionProviderInterface
{
    use FilePathToolsTrait;

    public string $filename;

    public function __construct(string $filename, string $workingDirectory)
    {
        $this->filename = $this->getFilePath($filename, $workingDirectory);
    }

    public function getVersion(): string
    {
        if (!file_exists($this->filename)) {
            return '0.0.0';
        }

        return file_get_contents($this->filename);
    }

    public function setVersion(string $version): void
    {
        file_put_contents($this->filename, $version);
    }
}
