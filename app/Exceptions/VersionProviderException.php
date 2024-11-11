<?php

namespace App\Exceptions;

use Throwable;

class VersionProviderException extends \Exception
{
    public function __construct(protected string $provider, string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
