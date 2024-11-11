<?php

namespace App\Repository;

use Gitonomy\Git\Admin as GitAdmin;
use Gitonomy\Git\Repository;

class GitProvider
{
    public function fetch($repository, $branch): void
    {
        $repository = new Repository($repository);

        $repository->run('fetch', ['origin']);
    }
}
