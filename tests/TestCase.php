<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');

        if (in_array($database, ['app', 'production', 'prod'], true)) {
            $this->fail("危险：测试连接到了疑似生产数据库 [{$database}]，检查 phpunit.xml 和 .env.testing");
        }
    }
}
