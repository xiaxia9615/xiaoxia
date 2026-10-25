<?php

declare(strict_types=1);

namespace App\Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AdminSystemService
{
    public function health(): array
    {
        return [
            'db'      => rescue(fn () => DB::select('SELECT 1') !== null, false),
            'cache'   => rescue(fn () => Cache::put('probe', 1, 5) !== false, false),
            'nginx'   => $this->isServiceActive('nginx'),
            'php-fpm' => $this->isServiceActive('php8.5-fpm'),
            'worker'  => $this->isServiceActive('supervisor'),
        ];
    }

    private function isServiceActive(string $service): bool
    {
        $disabled = (string) ini_get('disable_functions');
        if (!str_contains($disabled, 'shell_exec')) {
            $result = @shell_exec('systemctl is-active ' . escapeshellarg($service) . ' 2>/dev/null');
            if (is_string($result) && trim($result) !== '') {
                return trim($result) === 'active';
            }
        }

        $pidFiles = [
            'nginx'      => '/run/nginx.pid',
            'php8.5-fpm' => '/run/php/php8.5-fpm.pid',
            'supervisor' => '/run/supervisord.pid',
        ];

        return isset($pidFiles[$service]) && is_file($pidFiles[$service]);
    }

    public function backups(string $dir = '/home/ubuntu/backups/mysql'): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir . '/app-*.sql.gz') ?: [] as $path) {
            $files[] = [
                'name'  => basename($path),
                'size'  => filesize($path),
                'mtime' => date('Y-m-d H:i:s', filemtime($path)),
            ];
        }
        usort($files, fn ($a, $b) => strcmp($b['mtime'], $a['mtime']));

        return $files;
    }

    public function tailLog(int $lines = 100): array
    {
        $path = $this->resolveLogPath();
        if ($path === null || !is_file($path) || !is_readable($path)) {
            return [];
        }

        $disabled = (string) ini_get('disable_functions');
        if (str_contains($disabled, 'shell_exec')) {
            $content = @file_get_contents($path);
            if ($content === false || $content === '') {
                return [];
            }
            $all = explode("\n", rtrim($content));

            return array_values(array_filter(array_slice($all, -$lines), fn ($l) => $l !== ''));
        }

        $out = @shell_exec('tail -n ' . (int) $lines . ' ' . escapeshellarg($path) . ' 2>/dev/null');
        if (!is_string($out) || $out === '') {
            return [];
        }

        return array_values(array_filter(explode("\n", rtrim($out)), fn ($l) => $l !== ''));
    }

    private function resolveLogPath(): ?string
    {
        $daily = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
        if (is_file($daily)) {
            return $daily;
        }

        $default = storage_path('logs/laravel.log');
        if (is_file($default)) {
            return $default;
        }

        return null;
    }
}
