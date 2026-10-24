<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class NoUnscopedQueryTest extends TestCase
{
    /** 规则1: Controller 不许直接 Model::find/findOrFail, 必须走 Service */
    public function test_controllers_do_not_call_find_directly(): void
    {
        $files = $this->findPhpFiles(
            __DIR__ . '/../../app/Modules',
            '/Http/Controllers/'
        );
        $this->assertNotEmpty($files, '未找到 Controller 文件, 检查路径');

        $violations = [];
        foreach ($files as $f) {
            $code = file_get_contents($f);
            if (preg_match('/::(find|findOrFail)\(/', $code, $m, PREG_OFFSET_CAPTURE)) {
                $line = substr_count(substr($code, 0, $m[0][1]), "\n") + 1;
                $violations[] = basename($f) . ':' . $line;
            }
        }

        $this->assertEmpty(
            $violations,
            'Controller 直接调 find/findOrFail, 应走 Service: ' . implode(', ', $violations)
        );
    }

    /** 规则2: 每个 Http:: 调用后 500 字符窗口内必须有 timeout */
    public function test_http_calls_have_timeout(): void
    {
        $files = $this->findPhpFiles(__DIR__ . '/../../app');

        $violations = [];
        foreach ($files as $f) {
            $code = file_get_contents($f);
            if (!str_contains($code, 'Http::')) {
                continue;
            }
            preg_match_all('/Http::/', $code, $m, PREG_OFFSET_CAPTURE);
            foreach ($m[0] as $match) {
                $offset = $match[1];
                $window = substr($code, $offset, 500);
                if (!preg_match('/timeout|connectTimeout/', $window)) {
                    $line = substr_count(substr($code, 0, $offset), "\n") + 1;
                    $violations[] = basename($f) . ':' . $line;
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Http:: 调用附近(500字符内)无 timeout: ' . implode(', ', $violations)
        );
    }

    private function findPhpFiles(string $dir, string $pathFilter = ''): array
    {
        $result = [];
        if (!is_dir($dir)) {
            return $result;
        }
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (!str_ends_with($path, '.php')) {
                continue;
            }
            if ($pathFilter && !str_contains($path, $pathFilter)) {
                continue;
            }
            $result[] = $path;
        }
        return $result;
    }
}
