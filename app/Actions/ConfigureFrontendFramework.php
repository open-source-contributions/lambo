<?php

namespace App\Actions;

use App\ConsoleWriter;
use App\Shell;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ConfigureFrontendFramework
{
    use AbortsCommands;

    protected $shell;
    protected $availableFrontends = ['inertia', 'livewire', 'none'];
    protected $consoleWriter;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        $frontend = $this->getValidatedFrontend();
        
        if ($frontend === 'none') {
            return;
        }
            return;
        }

        $this->consoleWriter->logStep('Configuring frontend scaffolding');

        if (! $this->chooseValidFrontend()) {
            return $this->consoleWriter->verbose()->success('No frontend framework will be installed.', ' OK ');
        }

        $this->ensureJetstreamInstalled();

        $process = $this->shell->execInProject(sprintf(
            "php artisan jetstream:install %s%s%s",
            $this->getFrontend(),
            $this->withTeams(),
            $this->withQuiet()
        ));

        $this->abortIf(! $process->isSuccessful(), "Installation of {$this->getFrontend()} UI scaffolding did not complete successfully.", $process);

        $this->consoleWriter->verbose()->success($this->getFrontend() . ' UI scaffolding installed.');
    }

    public function ensureJetstreamInstalled()
    {
        $composeConfig = json_decode(File::get(config('lambo.store.project_path') . '/composer.json'), true);
        if (Arr::has($composeConfig, 'require.laravel/jetstream')) {
            return;
        }

        $this->consoleWriter->verbose()->note('Installing required composer package laravel/jetstream.');

        $process = $this->shell->execInProject("composer require laravel/jetstream{$this->withQuiet()}");

        $this->abortIf(! $process->isSuccessful(), "Installation of laravel/jetstream did not complete successfully.", $process);

        $this->consoleWriter->verbose()->success('laravel/jetstream installed.');
    }

    private function chooseValidFrontend(): bool
    {
        if (in_array(strtolower($this->getFrontend()), $this->availableFrontends)) {
            return true;
        }

        $configuredFrontend = $this->chooseFrontend();
        if ($configuredFrontend !== 'none') {
            config(['lambo.store.frontend' => $configuredFrontend]);
            $this->consoleWriter->verbose()->success("Using {$configuredFrontend} ui scaffolding.", ' OK ');
            return true;
        }
        return false;
    }

    private function chooseFrontend()
    {
        $this->availableFrontends[] = 'none';
        $preselectedChoice = count($this->availableFrontends) - 1;
        return app('console')->choice("<fg=yellow>I can't install {$this->getFrontend()}</>. Please choose one of the following options", $this->availableFrontends, $preselectedChoice);
    }

    private function withQuiet()
    {
        return config('lambo.store.with_output') ? '' : ' --quiet';
    }

    private function withTeams(): string
    {
        return config('lambo.store.with_teams') ? ' --teams' : '';
    }

    private function getFrontend()
    {
        return config('lambo.store.frontend');
    }
}
