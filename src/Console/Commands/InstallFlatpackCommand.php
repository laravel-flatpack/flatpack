<?php

declare(strict_types=1);

namespace Flatpack\Console\Commands;

use Flatpack\Console\Support\InjectUserCanAccessFlatpackService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'flatpack:install')]
final class InstallFlatpackCommand extends Command
{
    protected $signature = 'flatpack:install {--force : Overwrite published files}';

    protected $description = 'Publish Flatpack config and assets, with optional User access setup';

    private bool $userAccessReminder = false;

    private bool $reinstallConfirmed = false;

    public function handle(InjectUserCanAccessFlatpackService $userAccess): int
    {
        $this->publishFlatpackAssets();
        $this->publishAiSkillIfConfirmed();
        $this->handleUserAccessInjection($userAccess);
        $this->printNextSteps();

        return self::SUCCESS;
    }

    private function publishFlatpackAssets(): void
    {
        if (! $this->shouldPublishFlatpackAssets()) {
            return;
        }

        $this->call('vendor:publish', [
            '--tag' => 'flatpack',
            '--force' => $this->shouldOverwriteExistingPublish(),
        ]);
    }

    private function shouldPublishFlatpackAssets(): bool
    {
        if (! $this->isAlreadyInstalled()) {
            return true;
        }

        if ($this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        $this->reinstallConfirmed = confirm(
            label: 'Flatpack looks already installed. Would you like to re-install? This will overwrite existing config files.',
            default: false,
        );

        return $this->reinstallConfirmed;
    }

    private function shouldOverwriteExistingPublish(): bool
    {
        return $this->option('force') || $this->reinstallConfirmed;
    }

    private function isAlreadyInstalled(): bool
    {
        return is_file(config_path('flatpack.php'))
            && is_dir(public_path('vendor/flatpack'));
    }

    private function publishAiSkillIfConfirmed(): void
    {
        if (! $this->flatpackAiSkillIsPublishable()) {
            return;
        }

        if ($this->input->isInteractive() && ! confirm(
            label: 'Publish Flatpack AI YAML authoring skill?',
            default: false,
        )) {
            return;
        }

        if (! $this->input->isInteractive()) {
            return;
        }

        $this->call('vendor:publish', [
            '--tag' => 'flatpack-ai',
            '--force' => (bool) $this->option('force'),
        ]);
    }

    private function handleUserAccessInjection(InjectUserCanAccessFlatpackService $userAccess): void
    {
        if (! $userAccess->needsInjection()) {
            return;
        }

        if (! $this->input->isInteractive()) {
            $this->userAccessReminder = true;

            return;
        }

        $userClass = $userAccess->resolveUserModelClass();

        if (! confirm(
            label: 'Add canAccessFlatpack() to ' . $userClass . ' now?',
            default: true,
        )) {
            $this->userAccessReminder = true;

            return;
        }

        if (! $userAccess->inject()) {
            $this->components->warn('Could not add canAccessFlatpack() automatically. Add it manually before visiting the panel.');

            return;
        }

        $this->components->info('Added canAccessFlatpack() returning true. Customize it for your authorization rules before production.');
    }

    private function printNextSteps(): void
    {
        $prefix = (string) $this->laravel->make('config')->get('flatpack.http.prefix', 'flatpack');

        $this->newLine();
        $this->components->info('Flatpack is installed.');
        $this->line('Open the panel: /' . trim($prefix, '/'));
        $this->line('Create your first entity: php artisan flatpack:make Post');
        $this->line('Host setup guide: .docs/host-installation.md');

        if ($this->userAccessReminder) {
            $this->line('Add canAccessFlatpack() to your User model before visiting the panel.');
        }
    }

    private function flatpackAiSkillIsPublishable(): bool
    {
        $basePath = dirname(__DIR__, 3);

        return is_dir($basePath . '/resources/boost/skills/flatpack-yaml-authoring');
    }
}
