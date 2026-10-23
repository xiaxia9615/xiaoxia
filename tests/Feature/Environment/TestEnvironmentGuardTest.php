<?php

namespace Tests\Feature\Environment;

use Tests\TestCase;

class TestEnvironmentGuardTest extends TestCase
{
    public function test_test_connection_is_sqlite(): void
    {
        $this->assertSame(
            'sqlite',
            config('database.default'),
            '测试运行时 database.default 必须是 sqlite，当前是：'.config('database.default')
        );
    }

    public function test_database_is_not_production(): void
    {
        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');

        $this->assertNotContains(
            $database,
            ['app', 'production', 'prod'],
            "测试运行时数据库不能是疑似生产库，当前是：{$database}"
        );
    }

    public function test_deploy_check_script_exists_and_is_executable(): void
    {
        $script = base_path('scripts/deploy-check.sh');

        $this->assertFileExists($script, 'scripts/deploy-check.sh 必须存在');
        $this->assertTrue(
            is_executable($script),
            'scripts/deploy-check.sh 必须可执行（chmod +x）'
        );
    }
}
