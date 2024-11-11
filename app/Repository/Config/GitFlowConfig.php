<?php

namespace App\Repository\Config;

/**
 * @example branch[develop]=develop&branch[master]=main&prefix[feature]=feature/&prefix[release]=release/&prefix[hotfix]=hotfix/&prefix[support]=support/&prefix[versionTag]=v&suffix[versionTag]=-src
 */
readonly class GitFlowConfig
{
    public function __construct(
        public string $masterBranch,
        public string $developBranch,
        public string $featurePrefix,
        public string $releasePrefix,
        public string $hotfixPrefix,
        public string $supportPrefix,
        public string $versionTagPrefix,
        public string $versionTagSuffix,
    )
    {
    }

}
