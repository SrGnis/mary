<?php

namespace Mary\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MaryInstallCommand extends Command
{
    protected $signature = 'mary:install';

    protected $description = 'Command description';

    protected $ds = DIRECTORY_SEPARATOR;

    public function handle()
    {
        $this->info("❤️  maryUI installer");

        // Laravel 12+
        $this->checkForLaravelVersion();

        // Install Volt ?
        $shouldInstallVolt = $this->askForVolt();

        //Yarn or Npm or Bun or Pnpm ?
        $packageManagerCommand = $this->askForPackageInstaller();

        // Choose icon package
        $iconConfig = $this->askForIconPackage();

        // Install Livewire/Volt
        $this->installLivewire($shouldInstallVolt);

        // Setup Tailwind and Daisy
        $this->setupTailwindDaisy($packageManagerCommand);

        // Install and configure icon package
        $this->setupIconPackage($iconConfig);

        // Copy stubs if is brand-new project
        $this->copyStubs($shouldInstallVolt);

        // Rename components if Jetstream or Breeze are detected
        $this->renameComponents();

        // Clear view cache
        Artisan::call('view:clear');

        $this->info("\n");
        $this->info("✅  Done!");
        $this->info("❤️  Sponsor: https://github.com/sponsors/robsontenorio");
        $this->info("\n");
    }

    public function installLivewire(string $shouldInstallVolt)
    {
        $this->info("\nInstalling Livewire...\n");

        $extra = $shouldInstallVolt == 'Yes'
            ? ' livewire/volt && php artisan volt:install'
            : '';

        Process::run("composer require livewire/livewire $extra", function (string $type, string $output) {
            echo $output;
        })->throw();
    }

    public function setupTailwindDaisy(string $packageManagerCommand)
    {
        /**
         * Install daisyUI + Tailwind
         */
        $this->info("\nInstalling daisyUI + Tailwind...\n");

        Process::run("$packageManagerCommand daisyui tailwindcss @tailwindcss/vite", function (string $type, string $output) {
            echo $output;
        })->throw();

        /**
         * Setup app.css
         */
        $cssPath = base_path() . "{$this->ds}resources{$this->ds}css{$this->ds}app.css";
        $css = File::get($cssPath);

        $mary = <<<EOT
            \n\n
            /**
                The lines above are intact.
                The lines below were added by maryUI installer.
            */

            /** daisyUI */
            @plugin "daisyui" {
                themes: light --default, dark --prefersdark;
            }

            /* maryUI */
            @source "../../vendor/robsontenorio/mary/src/View/Components/**/*.php";

            /* Theme toggle */
            @custom-variant dark (&:where(.dark, .dark *));

            /**
            * Paginator - Traditional style
            * Because Laravel defaults does not match well the design of daisyUI.
            */

            .mary-table-pagination span[aria-current="page"] > span {
                @apply bg-primary text-base-100
            }

            .mary-table-pagination button {
                @apply cursor-pointer
            }
            EOT;

        $css = str($css)->append($mary);

        File::put($cssPath, $css);
    }

    /**
     * If Jetstream or Breeze are detected we publish config file and add a global prefix to maryUI components,
     * in order to avoid name collision with existing components.
     */
    public function renameComponents()
    {
        $composerJson = File::get(base_path() . "/composer.json");

        collect(['jetstream', 'breeze', 'livewire/flux'])->each(function (string $target) use ($composerJson) {
            if (str($composerJson)->contains($target)) {
                Artisan::call('vendor:publish --force --tag mary.config');

                $path = base_path() . "{$this->ds}config{$this->ds}mary.php";
                $config = File::get($path);
                $contents = str($config)->replace("'prefix' => ''", "'prefix' => 'mary-'");
                File::put($path, $contents);

                $this->warn('---------------------------------------------');
                $this->warn("🚨`$target` was detected.🚨");
                $this->warn('---------------------------------------------');
                $this->warn("A global prefix on maryUI components was added to avoid name collision.");
                $this->warn("\n * Example: x-mary-button, x-mary-card ...");
                $this->warn(" * See config/mary.php");
                $this->warn('---------------------------------------------');
            }
        });
    }

    /**
     * Copy example demo stub if it is a brand-new project.
     */
    public function copyStubs(string $shouldInstallVolt): void
    {
        $composerJson = File::get(base_path() . "/composer.json");
        $hasKit = str($composerJson)->contains('jetstream') || str($composerJson)->contains('breeze') || str($composerJson)->contains('livewire/flux');

        if ($hasKit) {
            $this->warn('---------------------------------------------');
            $this->warn('🚨 Starter kit detected. Skipping demo components. 🚨');
            $this->warn('---------------------------------------------');

            return;
        }

        $this->info("Copying stubs...\n");

        $routes = base_path() . "{$this->ds}routes";
        $appViewComponents = "app{$this->ds}View{$this->ds}Components";
        $livewirePath = "app{$this->ds}Livewire";
        $layoutsPath = "resources{$this->ds}views{$this->ds}components{$this->ds}layouts";
        $livewireBladePath = "resources{$this->ds}views{$this->ds}livewire";

        // Blade Brand component
        $this->createDirectoryIfNotExists($appViewComponents);
        $this->copyFile(__DIR__ . "/../../../stubs/AppBrand.php", "{$appViewComponents}{$this->ds}AppBrand.php");

        // Default app layout
        $this->createDirectoryIfNotExists($layoutsPath);
        $this->copyFile(__DIR__ . "/../../../stubs/app.blade.php", "{$layoutsPath}{$this->ds}app.blade.php");

        // Livewire blade views
        $this->createDirectoryIfNotExists($livewireBladePath);

        // Demo component and its route
        if ($shouldInstallVolt == 'Yes') {
            $this->createDirectoryIfNotExists("$livewireBladePath{$this->ds}users");
            $this->copyFile(__DIR__ . "/../../../stubs/index.blade.php", "$livewireBladePath{$this->ds}users{$this->ds}index.blade.php");
            $this->copyFile(__DIR__ . "/../../../stubs/web-volt.php", "$routes{$this->ds}web.php");
        } else {
            $this->createDirectoryIfNotExists($livewirePath);
            $this->copyFile(__DIR__ . "/../../../stubs/Welcome.php", "{$livewirePath}{$this->ds}Welcome.php");
            $this->copyFile(__DIR__ . "/../../../stubs/welcome.blade.php", "{$livewireBladePath}{$this->ds}welcome.blade.php");
            $this->copyFile(__DIR__ . "/../../../stubs/web.php", "$routes{$this->ds}web.php");
        }
    }

    public function askForPackageInstaller(): string
    {
        $os = PHP_OS;
        $findCommand = stripos($os, 'WIN') === 0 ? 'where' : 'which';

        $yarn = Process::run($findCommand . ' yarn')->output();
        $npm = Process::run($findCommand . ' npm')->output();
        $bun = Process::run($findCommand . ' bun')->output();
        $pnpm = Process::run($findCommand . ' pnpm')->output();

        $options = [];

        if (Str::of($yarn)->isNotEmpty()) {
            $options = array_merge($options, ['yarn add -D' => 'yarn']);
        }

        if (Str::of($npm)->isNotEmpty()) {
            $options = array_merge($options, ['npm install --save-dev' => 'npm']);
        }

        if (Str::of($bun)->isNotEmpty()) {
            $options = array_merge($options, ['bun i -D' => 'bun']);
        }

        if (Str::of($pnpm)->isNotEmpty()) {
            $options = array_merge($options, ['pnpm i -D' => 'pnpm']);
        }

        if (count($options) == 0) {
            $this->error("You need yarn or npm or bun or pnpm installed.");

            exit;
        }

        return select(
            label: 'Install with ...',
            options: $options
        );
    }

    /**
     * Also install Volt?
     */
    public function askForVolt(): string
    {
        return select(
            'Also install `livewire/volt` ?',
            ['Yes', 'No'],
            hint: 'No matter what is your choice, it always installs `livewire/livewire`'
        );
    }

    public function checkForLaravelVersion(): void
    {
        if (version_compare(app()->version(), '12.0', '<')) {
            $this->error("❌  Laravel 12 or above required.");

            exit;
        }
    }

    private function createDirectoryIfNotExists(string $path): void
    {
        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function copyFile(string $source, string $destination): void
    {
        $source = str_replace('/', DIRECTORY_SEPARATOR, $source);
        $destination = str_replace('/', DIRECTORY_SEPARATOR, $destination);

        if (! copy($source, $destination)) {
            throw new RuntimeException("Failed to copy {$source} to {$destination}");
        }
    }

    /**
     * Ask user to choose their preferred icon package
     */
    public function askForIconPackage(): array
    {
        $iconPackage = text(
            'Enter the icon package name (leave empty for Heroicons):',
            placeholder: 'blade-ui-kit/blade-heroicons',
            hint: 'Full Composer package name. Leave empty to use Heroicons as default.',
            required: false
        );

        // Default to Heroicons if empty
        if (empty($iconPackage)) {
            $iconPackage = 'blade-ui-kit/blade-heroicons';
            $iconPrefix = 'heroicon';
        } else {
            $iconPrefix = text(
                'Enter your icon prefix:',
                placeholder: 'e.g., heroicon, lucide, phosphor',
                hint: 'This will be prepended to icon names (e.g., "heroicon-home")',
                required: true
            );
        }

        return [
            'package' => $iconPackage,
            'prefix' => $iconPrefix
        ];
    }

    /**
     * Install the chosen icon package and update configuration
     */
    public function setupIconPackage(array $iconConfig): void
    {
        $package = $iconConfig['package'];
        $prefix = $iconConfig['prefix'];

        // Install the icon package
        if (str_contains($package, '/')) {
            $this->info("\nInstalling {$package} icon package...\n");

            try {
                Process::run("composer require {$package}", function (string $type, string $output) {
                    echo $output;
                })->throw();
            } catch (\Exception $e) {
                $this->warn("⚠️  Could not install {$package}. You may need to install it manually.");
                $this->warn("Error: " . $e->getMessage());
            }
        }

        // Publish and update Mary config
        $this->info("Configuring icon settings...\n");

        // Publish config if it doesn't exist
        if (!File::exists(base_path() . "{$this->ds}config{$this->ds}mary.php")) {
            Artisan::call('vendor:publish --force --tag mary.config');
        }

        // Update the config file with icon settings
        $configPath = base_path() . "{$this->ds}config{$this->ds}mary.php";
        $config = File::get($configPath);

        // Update icon package
        $config = str($config)->replace(
            "'package' => 'blade-ui-kit/blade-heroicons',",
            "'package' => '{$package}',"
        );

        // Update icon prefix
        $config = str($config)->replace(
            "'prefix' => 'heroicon',",
            "'prefix' => '{$prefix}',"
        );

        File::put($configPath, $config);

        $this->info("✅ Icon package configured: {$package} with prefix '{$prefix}'");

        if ($package !== 'custom/no-package') {
            $this->info("💡 Make sure to clear your view cache: php artisan view:clear");
        }
    }
}
