<?php

namespace App\Repository\VersionProviders;


use App\Exceptions\VersionProviderException;

/***
 * @example php-array?filename=version.php&key=number
 */
readonly class PhpArrayVersionProvider implements VersionProviderInterface
{
    public string $filename;

    public function __construct(string $filename, public string $key, string $workingDirectory)
    {
        $this->filename = $this->getFilePath($filename, $workingDirectory);
    }

    /**
     * @throws VersionProviderException
     */
    public function getVersion(): string
    {
        if (!file_exists($this->filename)) {
            throw new VersionProviderException('php-array', 'File does not exist');
        }

        $result = preg_match(
            pattern: sprintf("/'%s'\\s*=>\\s*'([^']+)'\\s*,/u", $this->key),
            subject: file_get_contents($this->filename),
            matches: $matches
        );

        if ($result === false || !isset($matches[1])) {
            throw new VersionProviderException('php-array', 'Could not read version number');
        }

        return $matches[1];
    }

    /**
     * @throws VersionProviderException
     */
    public function setVersion(string $version): void
    {
        if (!file_exists($this->filename)) {
            throw new VersionProviderException('php-array', 'File does not exist');
        }

        $result = preg_replace(
            pattern: sprintf("/'%s'\\s*=>\\s*'([^']+)'\\s*,/u", $this->key),
            replacement: sprintf("'%s' => '%s',", $this->key, $version),
            subject: file_get_contents($this->filename)
        );

        file_put_contents($this->filename, $result);
    }

    protected function getFilePath(string $filename, string $workingDirectory): string
    {
        return str_starts_with($filename, DIRECTORY_SEPARATOR) ? $filename : $workingDirectory . DIRECTORY_SEPARATOR . $filename;
    }
}
