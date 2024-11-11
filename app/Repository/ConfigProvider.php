<?php

namespace App\Repository;

use App\Exceptions\ConfigProviderException;
use App\Exceptions\VersionProviderException;
use App\Repository\Config\AppConfig;
use App\Repository\Config\GitConfig;
use App\Repository\Config\GitFlowConfig;

class ConfigProvider
{
    public function __construct(protected VersionProviderFactory $versionProviderFactory)
    {
    }

    /**
     * @throws ConfigProviderException|VersionProviderException
     */
    public function load(string $workingDirectory): AppConfig
    {
        $workingDirectory = realpath($workingDirectory);
        if ($workingDirectory === false) {
            throw new ConfigProviderException('Working directory does not exist');
        }

        do {
            $configFile = $workingDirectory . DIRECTORY_SEPARATOR . '.git-flow-tool.json';
            if (file_exists($configFile)) {
                return $this->loadConfigFile($configFile);
            }
            $workingDirectory = dirname($workingDirectory);
        } while ($workingDirectory !== '/');

        throw new ConfigProviderException('Config file not found!');
    }

    /**
     * @throws ConfigProviderException|VersionProviderException
     */
    private function loadConfigFile(string $configFile): AppConfig
    {
        $config = json_decode(file_get_contents($configFile), true);
        if ($config === null) {
            throw new ConfigProviderException('Invalid config file!');
        }

        if (!isset($config['versionProvider'])) {
            throw new ConfigProviderException('Missing versionProvider in config!');
        }

        parse_str($config['gitFlow'] ?? '', $gitFlow);
        parse_str($config['git'] ?? '', $git);

        return new AppConfig(
            workingDirectory: dirname($configFile),
            configFile: $configFile,

            versionProviderConfiguration: $config['versionProvider'],
            versionProvider: $this->versionProviderFactory->getProviderFor($config['versionProvider'], dirname($configFile)),

            gitFlowConfiguration: $config['gitFlow'],
            gitFlow: new GitFlowConfig(
                masterBranch: $gitFlow['branch']['master'] ?? 'master',
                developBranch: $gitFlow['branch']['develop'] ?? 'develop',
                featurePrefix: $gitFlow['prefix']['feature'] ?? 'feature/',
                releasePrefix: $gitFlow['prefix']['release'] ?? 'release/',
                hotfixPrefix: $gitFlow['prefix']['hotfix'] ?? 'hotfix/',
                supportPrefix: $gitFlow['prefix']['support'] ?? 'support/',
                versionTagPrefix: $gitFlow['prefix']['versionTag'] ?? 'v',
                versionTagSuffix: $gitFlow['suffix']['versionTag'] ?? '',
            ),

            gitConfiguration: $config['git'] ?? '',
            git: new GitConfig(
                authorName: $git['author']['name'] ?? '',
                authorEmail: $git['author']['email'] ?? '',
                remoteName: $git['remoteName'] ?? 'origin',
            ),
        );
    }
}
