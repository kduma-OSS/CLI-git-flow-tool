<?php

namespace App\Repository;

use App\Repository\VersionProviders\FlatFileVersionProvider;
use App\Repository\VersionProviders\JsonVersionProvider;
use App\Repository\VersionProviders\PhpArrayVersionProvider;
use App\Repository\VersionProviders\VersionProviderInterface;
use App\Exceptions\VersionProviderException;

class VersionProviderFactory
{
    /**
     * @throws VersionProviderException
     */
    public function getProvider(string $type, array $config, string $workingDirectory): VersionProviderInterface
    {
        $workingDirectory = realpath($workingDirectory);
        if ($workingDirectory === false) {
            throw new VersionProviderException($type, 'Working directory does not exist');
        }
        $workingDirectory = rtrim($workingDirectory, DIRECTORY_SEPARATOR);

        switch ($type) {
            case 'flat-file':
                if (!isset($config['filename'])) {
                    throw new VersionProviderException('flat-file', 'Missing filename in config!');
                }

                return new FlatFileVersionProvider($config['filename'], $workingDirectory);

            case 'php-array':
                if (!isset($config['filename'])) {
                    throw new VersionProviderException('php-array', 'Missing filename in config!');
                }

                if (!isset($config['key'])) {
                    throw new VersionProviderException('php-array', 'Missing array key in config!');
                }

                return new PhpArrayVersionProvider($config['filename'], $config['key'], $workingDirectory);

            case 'json':
                if (!isset($config['filename'])) {
                    throw new VersionProviderException('json', 'Missing filename in config!');
                }

                if (!isset($config['key'])) {
                    throw new VersionProviderException('json', 'Missing array key in config!');
                }

                return new JsonVersionProvider(
                    filename: $config['filename'],
                    key: $config['key'],
                    workingDirectory: $workingDirectory,
                    pretty_print: ($config['pretty'] ?? 'true') == 'true',
                    unescaped_slashes: ($config['unescaped_slashes'] ?? 'true') == 'true',
                );

            default:
                throw new VersionProviderException($type, 'Invalid provider type!');
        }
    }

    /**
     * @throws VersionProviderException
     */
    public function getProviderFor(string $uri, string $workingDirectory): VersionProviderInterface
    {
        $provider_type = parse_url($uri, PHP_URL_PATH);
        parse_str(parse_url($uri, PHP_URL_QUERY), $provider_config);

        return $this->getProvider($provider_type, $provider_config, $workingDirectory);
    }


}
