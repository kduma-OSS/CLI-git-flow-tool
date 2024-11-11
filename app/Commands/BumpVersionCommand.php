<?php

namespace App\Commands;

use App\Exceptions\ConfigProviderException;
use App\Exceptions\VersionProviderException;
use App\Repository\ConfigProvider;
use App\Repository\VersionProviders\VersionUpdaterService;
use Illuminate\Console\ConfirmableTrait;
use LaravelZero\Framework\Commands\Command;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version;

use function Laravel\Prompts\error;

class BumpVersionCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'SIG'
        version
            {--increment-pre-release : Increments the pre-release version}
            {--increment-patch : Increments the patch version}
            {--increment-minor : Increments the minor version}
            {--increment-major : Increments the major version}

            {--set-build= : Sets the build number}
            {--clear-build : Clears the build number}

            {--set-pre-release= : Sets the pre-release version}
            {--clear-pre-release : Clears the pre-release version}

            {--p|pretend : Only return modified version}
            {--o|output=string : Output format}
            {--f|force : Force the operation to run when in production}
        SIG;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increments app version number';

    /**
     * Execute the console command.
     */
    public function handle(ConfigProvider $configProvider, VersionUpdaterService $service): int
    {
        try {
            $config = $configProvider->load(getcwd());

            if (! $this->confirmToProceed(callback: true)) {
                return -1;
            }

            $version = $service->load($config);
            if($this->option('increment-pre-release')) {
                $version->increment('pre-release');
            }
            if($this->option('increment-patch')) {
                $version->increment('patch');
            }
            if($this->option('increment-minor')) {
                $version->increment('minor');
            }
            if($this->option('increment-major')) {
                $version->increment('major');
            }

            if ($this->option('clear-build')) {
                $version->setBuild(null);
            }
            if ($this->option('set-build')) {
                $version->setBuild($this->option('set-build'));
            }

            if ($this->option('clear-pre-release')) {
                $version->setPreRelease(null);
            }
            if ($this->option('set-pre-release')) {
                $version->setPreRelease($this->option('set-pre-release'));
            }

            if (! $this->option('pretend')) {
                $version->save();
            }

            switch ($this->option('output')) {
                case 'json':
                    $this->line(
                        json_encode([
                            'major' => $version->get()->major,
                            'minor' => $version->get()->minor,
                            'patch' => $version->get()->patch,
                            'preRelease' => $version->get()->preRelease,
                            'build' => $version->get()->build,
                            'string' => $version->get(),
                        ], JSON_PRETTY_PRINT)
                    );
                    break;

                case 'string':
                default:
                    $this->line($version->get());
            }

            return 0;
        } catch (ConfigProviderException|InvalidVersionException $e) {
            error($e->getMessage());
            return -1;
        }catch (VersionProviderException $e) {
            error($e->getProvider().': '.$e->getMessage());
            return -1;
        }
    }
}
