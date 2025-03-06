<?php

namespace App\Repository\VersionProviders;

trait FilePathToolsTrait
{
    protected function getFilePath(string $filename, string $workingDirectory): string
    {
        return str_starts_with($filename, DIRECTORY_SEPARATOR) ? $filename : $workingDirectory . DIRECTORY_SEPARATOR . $filename;
    }
}
