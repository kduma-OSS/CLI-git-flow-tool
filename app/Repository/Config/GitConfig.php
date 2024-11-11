<?php

namespace App\Repository\Config;

/**
 * @example author[name]=BOT&author[email]=bot@localhost
 */
readonly class GitConfig
{
    public function __construct(
        public ?string $authorName,
        public ?string $authorEmail,
        public string $remoteName
    )
    {
    }
}
