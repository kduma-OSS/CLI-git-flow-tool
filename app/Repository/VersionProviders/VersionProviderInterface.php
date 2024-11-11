<?php

namespace App\Repository\VersionProviders;

interface VersionProviderInterface
{
    public function getVersion(): string;
    public function setVersion(string $version): void;
}
