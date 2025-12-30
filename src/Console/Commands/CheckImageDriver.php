<?php

namespace Carone\Media\Console\Commands;

use Illuminate\Console\Command;

class CheckImageDriver extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'media:check-driver';

    /**
     * The console command description.
     */
    protected $description = 'Check the configured image processing driver and its status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $configuredDriver = config('media.image_driver', 'imagick');
        $imagickLoaded = extension_loaded('imagick');
        $gdLoaded = extension_loaded('gd');

        $this->info('Image Processing Driver Status');
        $this->info('==============================');
        $this->newLine();

        // Show configured driver
        $this->line("Configured driver: <fg=cyan>{$configuredDriver}</>");
        $this->newLine();

        // Check Imagick
        if ($imagickLoaded) {
            $this->line('✅ Imagick extension: <fg=green>INSTALLED</>');
            if (class_exists(\Imagick::class)) {
                $imagick = new \Imagick();
                $version = $imagick->getVersion()['versionString'] ?? 'Unknown';
                $this->line("   Version: {$version}");
            }
        } else {
            $this->line('❌ Imagick extension: <fg=red>NOT INSTALLED</>');

            if ($configuredDriver === 'imagick') {
                $this->newLine();
                $this->warn('⚠️  Imagick is configured but not installed!');
                $this->warn('   System will fall back to GD driver.');
                $this->newLine();
                $this->comment('To install Imagick:');
                $this->line('  • Ubuntu/Debian: <fg=yellow>sudo apt-get install php-imagick</>');
                $this->line('  • Windows: Enable in php.ini: <fg=yellow>extension=imagick</>');
                $this->line('  • macOS: <fg=yellow>brew install imagemagick && pecl install imagick</>');
            }
        }

        $this->newLine();

        // Check GD
        if ($gdLoaded) {
            $this->line('✅ GD extension: <fg=green>INSTALLED</>');
            if (function_exists('gd_info')) {
                $gdInfo = gd_info();
                $this->line("   Version: {$gdInfo['GD Version']}");
            }
        } else {
            $this->line('❌ GD extension: <fg=red>NOT INSTALLED</>');
        }

        $this->newLine();

        // Show active driver
        $activeDriver = 'none';
        if ($configuredDriver === 'imagick' && $imagickLoaded) {
            $activeDriver = 'imagick';
        } elseif ($configuredDriver === 'imagick' && !$imagickLoaded && $gdLoaded) {
            $activeDriver = 'gd (fallback)';
        } elseif ($configuredDriver === 'gd' && $gdLoaded) {
            $activeDriver = 'gd';
        }

        $this->info("Active driver: <fg=cyan>{$activeDriver}</>");
        $this->newLine();

        // Memory recommendations
        $this->info('Memory Recommendations:');
        $this->line('----------------------');
        if ($activeDriver === 'imagick') {
            $this->line('✅ <fg=green>Imagick is memory efficient</>');
            $this->line('   Can handle 4000×4000+ images with 256MB memory limit');
            $this->line('   Streams image data instead of loading everything into RAM');
        } elseif ($activeDriver === 'gd' || $activeDriver === 'gd (fallback)') {
            $this->line('⚠️  <fg=yellow>GD has high memory requirements</>');
            $this->line('   A 4000×4000 image needs ~64MB just to load');
            $this->line('   Processing requires 2-3x that amount');
            $this->line('   Recommended: Switch to Imagick or increase memory_limit to 512M+');
            $this->newLine();
            $currentLimit = ini_get('memory_limit');
            $this->line("   Current PHP memory_limit: <fg=cyan>{$currentLimit}</>");
        } else {
            $this->error('❌ No image processing driver available!');
            $this->error('   Please install either Imagick (recommended) or GD extension.');
        }

        return self::SUCCESS;
    }
}
