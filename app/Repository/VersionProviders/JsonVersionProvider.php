<?php

namespace App\Repository\VersionProviders;


use App\Exceptions\VersionProviderException;
use JsonException;

/***
 * @example json?filename=composer.json&key=extra.version&pretty=true&unescaped_slashes=false
 */
readonly class JsonVersionProvider implements VersionProviderInterface
{
    use FilePathToolsTrait;

    public string $filename;

    public function __construct(
        string $filename,
        public string $key,
        string $workingDirectory,
        public bool $pretty_print = true,
        public bool $unescaped_slashes = false,
    )
    {
        $this->filename = $this->getFilePath($filename, $workingDirectory);
    }

    /**
     * @throws VersionProviderException
     */
    public function getVersion(): string
    {
        if (!file_exists($this->filename)) {
            throw new VersionProviderException('json', 'File does not exist');
        }

        $json = file_get_contents($this->filename);
        try {
            $json = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new VersionProviderException('json', 'File is not a valid json file');
        }
        $key = explode('.', $this->key);

        foreach ($key as $k) {
            if(!isset($json[$k])) {
                throw new VersionProviderException('json', 'JSON does contains the provided key (' . $k . '): '. $this->key);
            }

            $json = $json[$k];
        }

        if(is_array($json)) {
            throw new VersionProviderException('json', 'The provided JSON key, contains an array: '. $this->key);
        }

        if(!is_string($json)) {
            throw new VersionProviderException('json', 'The provided JSON key, is not a string value: '. $this->key);
        }

        return $json;
    }

    /**
     * @throws VersionProviderException
     */
    public function setVersion(string $version): void
    {
        $json = file_get_contents($this->filename);
        try {
            $json = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new VersionProviderException('json', 'File is not a valid json file');
        }

        $json = $this->dot_set($json, $this->key, $version);

        $flags = 0;

        if($this->pretty_print) {
            $flags |= JSON_PRETTY_PRINT;
        }

        if($this->unescaped_slashes) {
            $flags |= JSON_UNESCAPED_SLASHES;
        }

        $json = json_encode($json, $flags);
        file_put_contents($this->filename, $json);
    }

    /**
     * @throws VersionProviderException
     */
    private function dot_set(mixed $json, string $key, string $value): mixed
    {
        if(!is_array($json)) {
            throw new VersionProviderException('json', 'The provided JSON key, does not contains an array: '. $key);
        }

        $k = explode('.', $key);
        $current_key = $k[0];
        if(!array_key_exists($current_key, $json)) {
            throw new VersionProviderException('json', 'The provided JSON key, does not contains an key: '. $current_key);
        }

        if(count($k) > 1) {
            unset($k[0]);
            $json[$current_key] = $this->dot_set($json[$current_key], implode('.', $k), $value);
        } else {
            $json[$current_key] = $value;
        }
        return $json;
    }
}
