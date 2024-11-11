<?php

namespace App\Repository\Config;

use App\Repository\Config\GitFlowConfig;
use App\Repository\VersionProviders\VersionProviderInterface;

class AppConfig
{
    public function __construct(
        public string $workingDirectory,
        public string $configFile,

        public string $versionProviderConfiguration,
        public VersionProviderInterface $versionProvider,

        public string $gitFlowConfiguration,
        public GitFlowConfig $gitFlow,

        public string $gitConfiguration,
        public GitConfig $git,
    )
    {

    }
}
