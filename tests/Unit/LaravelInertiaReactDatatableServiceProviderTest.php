<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Arkhas\InertiaDatatable\InertiaDatatable;

class LaravelInertiaReactDatatableServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered()
    {
        $this->assertInstanceOf(
            ServiceProvider::class,
            $this->app->getProvider(\Arkhas\InertiaDatatable\InertiaDatatableServiceProvider::class)
        );
    }

    public function test_config_is_published()
    {
        $filesystem = new Filesystem();
        $this->artisan('vendor:publish', ['--tag' => 'config'])
            ->assertExitCode(0);

        $this->assertTrue($filesystem->exists(config_path('inertia-datatable.php')));
    }

    public function test_configuration_is_merged()
    {
        $this->assertArrayHasKey('inertia-datatable', config()->all());
    }

    public function test_translations_are_published()
    {
        $filesystem = new Filesystem();
        $this->artisan('vendor:publish', ['--tag' => 'translations'])
            ->assertExitCode(0);

        $this->assertTrue($filesystem->exists(lang_path('vendor/inertia-datatable')));
    }
}
