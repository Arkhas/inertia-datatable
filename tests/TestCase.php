<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \Arkhas\InertiaDatatable\InertiaDatatableServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup if needed
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
