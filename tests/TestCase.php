<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * 终极防线：config:cache 会直接加载 bootstrap/cache/config.php，
     * 无视 phpunit.xml 的 <env force="true"> 覆盖。
     * 在应用创建后立即强制 sqlite 内存库，任何缓存都无法阻挡。
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        // config:cache 下环境已在 bootstrap 阶段固化为 production，
        // migrate 的 confirmToProceed 检查 $app->environment()，必须直接重设环境实例
        $app['env'] = 'testing';

        config([
            'app.env'                              => 'testing',
            'database.default'                     => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        return $app;
    }

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
