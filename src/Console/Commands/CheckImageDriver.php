<?php

namespace Carone\Media\Console\Commands;

use Illuminate\Console\Command;

class MediaInfo extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'media:info';

    /**
     * The console command description.
     */
    protected $description = 'Display media package configuration and system information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('           Media Package Information');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->displayImageDriver();
        $this->newLine();
        $this->displayMemorySettings();
        $this->newLine();
        $this->displayConfiguration();

        return self::SUCCESS;
    }

    /**
     * Display image driver information
     */
    private function displayImageDriver(): void
    {
        $this->line('<fg=cyan>Image Processing Driver:</>');
        $this->line(str_repeat('─', 50));

        $configuredDriver = config('media.image_driver', 'imagick');
        $imagickLoaded = extension_loaded('imagick');
        $gdLoaded = extension_loaded('gd');

        // Determine active driver
        $activeDriver = 'none';
        if ($configuredDriver === 'imagick' && $imagickLoaded) {
            $activeDriver = 'imagick';
        } elseif ($configuredDriver === 'imagick' && !$imagickLoaded && $gdLoaded) {
            $activeDriver = 'gd (fallback)';
        } elseif ($configuredDriver === 'gd' && $gdLoaded) {
            $activeDriver = 'gd';
        }

        $this->line("  Configured: <fg=yellow>{$configuredDriver}</>");
        $this->line("  Active: <fg=green>{$activeDriver}</>");
        $this->newLine();

        // Imagick status
        if ($imagickLoaded) {
            $this->line('  ✅ Imagick: <fg=green>Available</>');
            if (class_exists(\Imagick::class)) {
                $imagick = new \Imagick();
                $version = $imagick->getVersion()['versionString'] ?? 'Unknown';
                $this->line("     Version: {$version}");
            }
        } else {
            $this->line('  ❌ Imagick: <fg=red>Not installed</>');
            if ($configuredDriver === 'imagick') {
                $this->line('     <fg=yellow>System will fall back to GD</>');
            }
        }

        // GD status
        if ($gdLoaded) {
            $this->line('  ✅ GD: <fg=green>Available</>');
            if (function_exists('gd_info')) {
                $gdInfo = gd_info();
                $this->line("     Version: {$gdInfo['GD Version']}");
            }
        } else {
            $this->line('  ❌ GD: <fg=red>Not installed</>');
        }

        if ($activeDriver === 'none') {
            $this->newLine();
            $this->error('  ⚠️  No image processing driver available!');
            $this->line('     Install Imagick or GD to enable image processing.');
        }
    }

    /**
     * Display memory settings and recommendations
     */
    private function displayMemorySettings(): void
    {
        $this->line('<fg=cyan>Memory Configuration:</>');
        $this->line(str_repeat('─', 50));

        $memoryLimit = ini_get('memory_limit');
        $memoryInMB = $this->parseMemoryLimit($memoryLimit);

        $this->line("  Current memory_limit: <fg=yellow>{$memoryLimit}</>");

        if ($memoryInMB < 256) {
            $this->newLine();
            $this->warn('  ⚠️  Memory limit is below recommended 256M');
            $this->line('     For handling large images (e.g., 4000×4000), increase to:');
            $this->line('     • <fg=green>256M</> - Recommended minimum');
            $this->line('     • <fg=green>512M</> - For very large images or high volumes');
            $this->newLine();
            $this->line('     Update in php.ini: <fg=cyan>memory_limit = 256M</>');
        } else {
            $this->line('  ✅ <fg=green>Memory limit is adequate for large images</>');
        }
    }

    /**
     * Display general configuration settings
     */
    private function displayConfiguration(): void
    {
        $this->line('<fg=cyan>General Configuration:</>');
        $this->line(str_repeat('─', 50));

        // Storage settings
        $disk = config('media.disk', 'public');
        $storagePath = config('media.storage_path', 'media/{path}');
        $this->line("  Storage disk: <fg=yellow>{$disk}</>");
        $this->line("  Storage path: <fg=yellow>{$storagePath}</>");
        $this->newLine();

        // Enabled types
        $enabledTypes = config('media.enabled_types', []);
        $this->line('  Enabled media types:');
        foreach ($enabledTypes as $type) {
            $this->line("    • {$type}");
        }
        $this->newLine();

        // Image processing settings
        $scaleOversized = config('media.processing.image.scale_oversized_images', true);
        $maxDimension = config('media.processing.image.max_dimension_before_encode', 3000);
        $scaledDimension = config('media.processing.image.scaled_max_dimension', 2560);
        $quality = config('media.processing.image.quality', 85);

        $this->line('  Image processing:');
        $this->line("    Quality: <fg=yellow>{$quality}</>%");
        $this->line("    Scale oversized images: <fg=yellow>" . ($scaleOversized ? 'Enabled' : 'Disabled') . '</>');
        if ($scaleOversized) {
            $this->line("      Threshold: {$maxDimension}px");
            $this->line("      Target size: {$scaledDimension}px");
        }
        $this->newLine();

        // Thumbnail settings
        $thumbnailsEnabled = config('media.thumbnails.enabled', true);
        $autoGenerate = config('media.thumbnails.auto_generate_for_images', false);
        $this->line('  Thumbnails:');
        $this->line("    Enabled: <fg=yellow>" . ($thumbnailsEnabled ? 'Yes' : 'No') . '</>');
        $this->line("    Auto-generate for images: <fg=yellow>" . ($autoGenerate ? 'Yes' : 'No') . '</>');
    }

    /**
     * Parse memory limit string to MB
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match ($last) {
            'g' => $value * 1024,
            'm' => $value,
            'k' => (int) ($value / 1024),
            default => (int) ($value / 1024 / 1024),
        };
    }
}
