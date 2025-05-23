<?php

declare(strict_types=1);

namespace Tests;

use Arkhas\InertiaDatatable\InertiaDatatableServiceProvider;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            InertiaDatatableServiceProvider::class,
            PowerJoinsServiceProvider::class,
            ExcelServiceProvider::class,
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
