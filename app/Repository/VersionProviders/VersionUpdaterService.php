<?php

namespace App\Repository\VersionProviders;

use App\Repository\Config\AppConfig;

readonly class VersionUpdaterService
{
    public function load(AppConfig $config): AppVersion
    {
        return new AppVersion($config);
    }
}
