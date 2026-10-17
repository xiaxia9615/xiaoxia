<?php

declare(strict_types=1);

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class ModuleBoundaryTest extends TestCase
{
    private const MODULES_ROOT = 'app/Modules';

    public function test_shell_models_extend_module_models(): void
    {
        $expect = [
            'app/Models/User.php'    => 'App\\Modules\\User\\Models\\User',
            'app/Models/Product.php' => 'App\\Modules\\Product\\Models\\Product',
            'app/Models/ApiKey.php'  => 'App\\Modules\\ApiKey\\Models\\ApiKey',
        ];
        foreach ($expect as $shell => $target) {
            $this->assertFileExists(base_path($shell), "壳文件缺失: {$shell}");
            $content = file_get_contents(base_path($shell));
            $this->assertTrue(
                $this->extendsTarget($content, $target),
                "壳文件 {$shell} 未继承 {$target}",
            );
        }
    }

    public function test_module_files_have_correct_namespace(): void
    {
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $module = $this->moduleOf($file);
            if ($module === '') continue;
            $content = file_get_contents(base_path($file));
            if (! preg_match('/^namespace\s+([^;]+);/m', $content, $m)) {
                $violations[] = "{$file} 无 namespace";
                continue;
            }
            $ns = trim($m[1]);
            if (! str_starts_with($ns, "App\\Modules\\{$module}")) {
                $violations[] = "{$file} 命名空间 {$ns} 与模块 {$module} 不符";
            }
        }
        $this->assertSame([], $violations, "命名空间违规：\n" . implode("\n", $violations));
    }

    public function test_config_modules_have_providers(): void
    {
        $modules = config('modules.list', []);
        $pattern = config('modules.provider_pattern');
        $missing = [];
        foreach ($modules as $module) {
            $class = sprintf($pattern, $module, $module);
            if (! class_exists($class)) {
                $missing[] = "{$module} → {$class}";
            }
        }
        $this->assertSame([], $missing, "缺失 Provider：\n" . implode("\n", $missing));
    }

    public function test_bootstrap_providers_has_only_two_entries(): void
    {
        $providers = require base_path('bootstrap/providers.php');
        $this->assertCount(2, $providers, 'providers.php 应只有 2 项');
        $this->assertContains('App\\Providers\\AppServiceProvider', $providers);
        $this->assertContains('App\\Providers\\ModuleServiceProvider', $providers);
    }

    public function test_cross_module_references_are_whitelisted(): void
    {
        $whitelist = config('modules.dependencies', []);
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $current = $this->moduleOf($file);
            if ($current === '') continue;
            $allowed = $whitelist[$current] ?? [];
            if (in_array('*', $allowed, true)) continue;
            foreach ($this->usedModules($file) as $other) {
                if ($other === $current) continue;
                if (! in_array($other, $allowed, true)) {
                    $violations[] = "{$file} 引用 [{$other}]，未在 dependencies[{$current}] 声明";
                }
            }
        }
        $this->assertSame([], $violations, "未声明的跨模块引用：\n" . implode("\n", $violations));
    }

    public function test_module_dependencies_have_no_cycles(): void
    {
        $deps = config('modules.dependencies', []);
        $cycles = [];
        foreach ($deps as $module => $list) {
            foreach ($list as $dep) {
                if (in_array($module, $deps[$dep] ?? [], true)) {
                    $pair = [$module, $dep];
                    sort($pair);
                    $cycles[] = implode(' ↔ ', $pair);
                }
            }
        }
        $this->assertSame([], array_unique($cycles), "循环依赖：\n" . implode("\n", $cycles));
    }

    public function test_module_files_declare_strict_types(): void
    {
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $content = file_get_contents(base_path($file));
            if (! str_contains($content, 'declare(strict_types=1);')) {
                $violations[] = $file;
            }
        }
        $this->assertSame([], $violations, "缺 declare(strict_types=1)：\n" . implode("\n", $violations));
    }

    private function moduleFiles(): array
    {
        $root = base_path(self::MODULES_ROOT);
        if (! is_dir($root)) return [];
        $files = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = str_replace(base_path() . '/', '', $file->getPathname());
                // Routes 文件是 Laravel 路由定义，不需要 namespace
                if (str_contains($path, '/Routes/')) continue;
                $files[] = $path;
            }
        }
        sort($files);
        return $files;
    }

    private function moduleOf(string $relativePath): string
    {
        if (preg_match('#^app/Modules/([A-Za-z]+)/#', $relativePath, $m)) return $m[1];
        return '';
    }

    private function extendsTarget(string $content, string $target): bool
    {
        if (preg_match('/extends\s+\\\\?' . preg_quote($target, '/') . '\b/', $content)) return true;
        if (preg_match('/use\s+\\\\?' . preg_quote($target, '/') . '\s+as\s+(\w+)\s*;/', $content, $m)) {
            if (preg_match('/extends\s+' . preg_quote($m[1], '/') . '\b/', $content)) return true;
        }
        if (preg_match('/use\s+\\\\?' . preg_quote($target, '/') . '\s*;/', $content)) {
            $short = substr($target, strrpos($target, '\\') + 1);
            if (preg_match('/extends\s+' . preg_quote($short, '/') . '\b/', $content)) return true;
        }
        return false;
    }

    public function test_cross_module_db_access_is_whitelisted(): void
    {
        $whitelist = config('modules.dependencies', []);
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $current = $this->moduleOf($file);
            if ($current === '') continue;
            $allowed = $whitelist[$current] ?? [];
            if (in_array('*', $allowed, true)) continue;
            foreach ($this->dbAccessedModules($file) as $other) {
                if ($other === $current) continue;
                if (! in_array($other, $allowed, true)) {
                    $violations[] = "{$file} 跨模块 DB 访问 [{$other}]，未在 dependencies[{$current}] 声明";
                }
            }
        }
        $this->assertSame([], $violations, "未声明的跨模块 DB 访问：\n" . implode("\n", $violations));
    }

    public function test_no_direct_cross_module_model_instantiation(): void
    {
        $whitelist = config('modules.dependencies', []);
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $current = $this->moduleOf($file);
            if ($current === '') continue;
            $allowed = $whitelist[$current] ?? [];
            if (in_array('*', $allowed, true)) continue;
            $content = file_get_contents(base_path($file));
            // 检测直接实例化或静态调用其他模块 Model（非壳文件路径）
            if (preg_match_all('/\\bApp\\\\Modules\\\\([A-Za-z]+)\\\\Models\\\\([A-Za-z]+)\b/', $content, $m)) {
                foreach ($m[1] as $i => $mod) {
                    if ($mod === $current) continue;
                    if (! in_array($mod, $allowed, true)) {
                        $violations[] = "{$file} 直接引用 App\\Modules\\{$mod}\\Models\\{$m[2][$i]}，未在 dependencies[{$current}] 声明";
                    }
                }
            }
        }
        $this->assertSame([], $violations, "未声明的直接跨模块 Model 引用：\n" . implode("\n", $violations));
    }

    public function test_cross_module_table_access_is_whitelisted(): void
    {
        $whitelist = config('modules.dependencies', []);
        $moduleTables = $this->moduleTableMap();
        $violations = [];
        foreach ($this->moduleFiles() as $file) {
            $current = $this->moduleOf($file);
            if ($current === '') continue;
            $allowed = $whitelist[$current] ?? [];
            if (in_array('*', $allowed, true)) continue;
            $content = file_get_contents(base_path($file));
            // 检测 DB::table()、Schema:: 等直接表名引用
            if (preg_match_all('/(?:DB::table\(|Schema::(?:table|create|drop|rename)\()\s*[\'"](([a-z_]+))[\'"]/', $content, $m)) {
                foreach ($m[1] as $table) {
                    $owner = $this->tableOwner($table, $moduleTables);
                    if ($owner !== null && $owner !== $current && ! in_array($owner, $allowed, true)) {
                        $violations[] = "{$file} 直接访问表 [{$table}]（属于模块 [{$owner}]），未在 dependencies[{$current}] 声明";
                    }
                }
            }
        }
        $this->assertSame([], $violations, "未声明的跨模块表访问：\n" . implode("\n", $violations));
    }

    private function usedModules(string $relativePath): array
    {
        $content = file_get_contents(base_path($relativePath));
        $modules = [];
        if (preg_match_all('/^use\s+App\\\\Modules\\\\([A-Za-z]+)\\\\/m', $content, $m)) {
            foreach ($m[1] as $mod) $modules[] = $mod;
        }
        if (preg_match_all('/^use\s+App\\\\Models\\\\([A-Za-z]+)\b/m', $content, $m)) {
            foreach ($m[1] as $model) $modules[] = $model;
        }
        return array_values(array_unique($modules));
    }

    private function dbAccessedModules(string $relativePath): array
    {
        $content = file_get_contents(base_path($relativePath));
        $modules = [];
        // 检测 Eloquent 关系方法中的跨模块引用：belongsTo / hasMany / hasOne / belongsToMany / morphTo / morphMany 等
        $relationPatterns = [
            '/->(?:belongsTo|hasMany|hasOne|belongsToMany|morphTo|morphMany|morphOne|morphToMany|morphedByMany)\s*\(\s*([A-Za-z_][A-Za-z0-9_]*)::class/',
        ];
        foreach ($relationPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $m)) {
                foreach ($m[1] as $className) {
                    $module = $this->resolveModelModule($className, $content);
                    if ($module !== '') $modules[] = $module;
                }
            }
        }
        // 检测通过壳文件 App\Models\* 的引用
        if (preg_match_all('/^use\s+App\\\\Models\\\\([A-Za-z]+)\b/m', $content, $m)) {
            foreach ($m[1] as $model) $modules[] = $model;
        }
        return array_values(array_unique($modules));
    }

    private function resolveModelModule(string $shortName, string $fileContent): string
    {
        // 先检查是否通过 use 导入了 App\Modules\*\Models\*
        if (preg_match('/^use\s+App\\\\Modules\\\\([A-Za-z]+)\\\\Models\\\\(\1)\s*;/m', $fileContent, $m)) {
            return $m[1];
        }
        // 检查是否通过 use 导入了 App\Models\*（壳文件）
        if (preg_match('/^use\s+App\\\\Models\\\\([A-Za-z]+)\s*;/m', $fileContent, $m)) {
            return $m[1];
        }
        // 检查是否通过 use ... as 别名导入
        if (preg_match('/^use\s+App\\\\Modules\\\\([A-Za-z]+)\\\\Models\\\\([A-Za-z]+)\s+as\s+(\w+)\s*;/m', $fileContent, $m)) {
            if ($m[3] === $shortName) return $m[1];
        }
        if (preg_match('/^use\s+App\\\\Models\\\\([A-Za-z]+)\s+as\s+(\w+)\s*;/m', $fileContent, $m)) {
            if ($m[2] === $shortName) return $m[1];
        }
        return '';
    }

    private function moduleTableMap(): array
    {
        $map = [];
        foreach ($this->moduleFiles() as $file) {
            $module = $this->moduleOf($file);
            if ($module === '') continue;
            if (! str_contains($file, '/Models/')) continue;
            $content = file_get_contents(base_path($file));
            if (preg_match('/protected\s+\$table\s*=\s*[\'"](([a-z_]+))[\'"]/', $content, $m)) {
                $map[$m[1]] = $module;
            }
        }
        return $map;
    }

    private function tableOwner(string $table, array $moduleTables): ?string
    {
        return $moduleTables[$table] ?? null;
    }
}
